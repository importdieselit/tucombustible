<?php

namespace App\Services;

use App\Repositories\DepositoRepository;
use App\Repositories\ChequeoDepositoRepository;
use App\Services\AforoCalculoService;
use App\Services\WhatsAppApiService;
use App\Models\Deposito;
use Illuminate\Support\Facades\DB;
use Exception;

class DepositoService
{
    protected $depositoRepo;
    protected $chequeoRepo;
    protected $aforoService;
    protected $whatsappService;

    public function __construct(DepositoRepository $depositoRepo, ChequeoDepositoRepository $chequeoRepo, 
    AforoCalculoService $aforoService, WhatsAppApiService $whatsappService
    ) {
        $this->depositoRepo = $depositoRepo;
        $this->chequeoRepo = $chequeoRepo;
        $this->aforoService = $aforoService;
        $this->whatsappService = $whatsappService;
    }

    public function registrarDeposito(array $data): Deposito
    {
        $data['capacidad_litros'] = $data['capacidad_maxima'];
        $data['nivel_alerta_litros'] = (float) $data['capacidad_maxima'] * 0.20;
        
        // Mapeo preventivo por si usas el string legacy 'producto' en tus reportes
        if (isset($data['producto_nombre_legacy'])) {
            $data['producto'] = $data['producto_nombre_legacy'];
        }

        $data['diametro'] = $data['diametro'] ?? 0;
        $data['longitud'] = $data['longitud'] ?? 0;
        $data['ancho']    = $data['ancho'] ?? 0;
        $data['alto']     = $data['alto'] ?? 0;

        return $this->depositoRepo->create($data);
    }

    public function actualizarDeposito($id, array $data)
    {
        // Regla de Negocio: Si cambia la capacidad, se recalcula el 20% de alerta estricto
        $data['nivel_alerta_litros'] = (float) $data['capacidad_maxima'] * 0.20;

        if (isset($data['producto_nombre_legacy'])) {
            $data['producto'] = $data['producto_nombre_legacy'];
        }

        $data['diametro'] = $data['diametro'] ?? 0;
        $data['longitud'] = $data['longitud'] ?? 0;
        $data['ancho']    = $data['ancho'] ?? 0;
        $data['alto']     = $data['alto'] ?? 0;

        return $this->depositoRepo->update($id, $data);
    }

    public function eliminarDeposito($id): bool
    {
        return $this->depositoRepo->delete($id);
    }

    public function obtenerDepositosConUltimaMedicion(int $idSede)
    {
        $depositos = Deposito::where('id_sede', $idSede)
                            ->orderBy('serial', 'asc')
                            ->get();

        foreach ($depositos as $deposito) {
            $deposito->ultima_medicion = DB::table('chequeos_depositos_detalles')
                ->where('id_deposito', $deposito->id)
                ->orderBy('id', 'desc')
                ->first();
        }

        return $depositos;
    }

    public function procesarChequeo(array $data)
    {
        // 1. Regla de Validación: Evitar duplicar auditorías en la misma fecha y turno
        if ($this->chequeoRepo->existeChequeo($data['id_sede'], $data['fecha'], $data['turno'])) {
            throw new Exception("Ya se encuentra registrado un varillaje para esta sede en la fecha y turno especificados.");
        }

        $detallesProcesados = [];
        $tanquesCriticos = []; //Tanques en menos del 20%

        // 2. Procesamiento secuencial del arreglo normalizado enviado por la vista
        foreach ($data['detalles'] as $detalle) {
            $deposito = Deposito::findOrFail($detalle['id_deposito']);
            
            // Invocación al motor analítico de cubicación (AforoCalculoService)
            $litrosCalculados = $this->aforoService->calcularLitros(
                $deposito, 
                (float) $detalle['centimetros_medidos']
            );

            if ($litrosCalculados < $deposito->nivel_alerta_litros) {
                $porcentajeActual = $deposito->capacidad_maxima > 0 
                    ? round(($litrosCalculados / $deposito->capacidad_maxima) * 100, 1) 
                    : 0;

                $tanquesCriticos[] = [
                    'serial' => $deposito->serial,
                    'litros' => round($litrosCalculados, 2),
                    'limite' => round($deposito->nivel_alerta_litros, 2),
                    'porcentaje' => $porcentajeActual
                ];
            }

            $detallesProcesados[] = [
                'id_deposito'         => $detalle['id_deposito'],
                'centimetros_medidos' => $detalle['centimetros_medidos'],
                'litros_calculados'   => $litrosCalculados,
                'id_tipos_combustible' => $detalle['id_tipos_combustible'],
            ];
        }

        // 3. Preparación de los datos de cabecera firmados
        $datosCabecera = [
            'id_sede'       => $data['id_sede'],
            'turno'         => $data['turno'],
            'fecha'         => $data['fecha'],
            'observaciones' => $data['observaciones'] ?? null,
            'id_usuario'    => $data['id_usuario'] ?? auth()->id() ?? 1,
        ];

        // 4. Persistencia mediante el Repositorio usando su transacción atómica original
        $chequeoGuardado = $this->chequeoRepo->guardarChequeoCompleto($datosCabecera, $detallesProcesados);

        // 🆕 SI TODO SE GUARDÓ BIEN Y HAY ALERTAS, ENVIAMOS EL WHATSAPP
        if ($chequeoGuardado && !empty($tanquesCriticos)) {
            $nombreSede = DB::table('sedes')->where('id', $data['id_sede'])->value('nombre') ?? "Sede #{$data['id_sede']}";
            $this->notificarTanquesCriticos($tanquesCriticos, $data['turno'], $nombreSede);
        }

        return $chequeoGuardado;
    }

    protected function notificarTanquesCriticos(array $tanques, string $turno, string $nombreSede)
    {
        $idDestino = config('services.whatsapp.dev_group_id', 'WHATSAPP_DEV_GROUP_ID');

        $mensaje = "⚠️ *ALERTA DE NIVELES CRÍTICOS* ⚠️\n";
        $mensaje .= "Se ha registrado el varillaje del turno: *{$turno}* en la sede *{$nombreSede}*.\n\n";
        $mensaje .= "Los siguientes tanques están por debajo del *20%* de su capacidad:\n\n";

        foreach ($tanques as $tanque) {
        $litrosFormateados     = number_format($tanque['litros'], 2, ',', '.');
        $limiteFormateado      = number_format($tanque['limite'], 2, ',', '.');

            $mensaje .= "🛢️ *Tanque:* {$tanque['serial']}\n";
            $mensaje .= "🔹 *Nivel actual:* {$litrosFormateados} Lts\n";
            $mensaje .= "📉 *Límite de alerta (menos de 20%):* {$limiteFormateado} Lts\n";
            $mensaje .= "----------------------------------\n";
        }
        
        $mensaje .= "\nSe recomienda coordinar la logística de reabastecimiento.";

        try {
            $this->whatsappService->enviarMensaje($idDestino, $mensaje);
        } catch (Exception $e) {
            logger()->error("No se pudo enviar la alerta de WhatsApp: " . $e->getMessage());
        }
    }
}