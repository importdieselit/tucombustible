<?php

namespace App\Services;

use App\Repositories\DepositoRepository;
use App\Repositories\ChequeoDepositoRepository;
use App\Repositories\HistorialLlenadoRepository;
use App\Services\AforoCalculoService;
use App\Services\WhatsAppApiService;
use App\Models\Deposito;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class DepositoService
{
    protected $depositoRepo;
    protected $chequeoRepo;
    protected $aforoService;
    protected $whatsappService;
    protected $historialRepo;

    public function __construct(DepositoRepository $depositoRepo, ChequeoDepositoRepository $chequeoRepo, 
    AforoCalculoService $aforoService, WhatsAppApiService $whatsappService, HistorialLlenadoRepository $historialRepo
    ) {
        $this->depositoRepo = $depositoRepo;
        $this->chequeoRepo = $chequeoRepo;
        $this->aforoService = $aforoService;
        $this->whatsappService = $whatsappService;
        $this->historialRepo = $historialRepo;
    }

    public function registrarDeposito(array $data): Deposito
    {
        $data['capacidad_litros'] = $data['capacidad_maxima'];
        $data['nivel_alerta_litros'] = (float) $data['capacidad_maxima'] * 0.20;
        $data['llena_cupo_prepagado'] = isset($data['llena_cupo_prepagado']) ? 1 : 0;
        
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
        $data['llena_cupo_prepagado'] = isset($data['llena_cupo_prepagado']) ? 1 : 0;

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

    public function registrarLlenado(int $clienteId, int $idDeposito, float $litros): int
    {
        return DB::transaction(function () use ($clienteId, $idDeposito, $litros) {
            
            // 1. Obtener y validar el tanque
            $deposito = Deposito::findOrFail($idDeposito);

            if (!$deposito->llena_cupo_prepagado) {
                throw new Exception("Operación denegada: Este tanque no está autorizado para Llenado Prepagado.");
            }

            // 🚨 CORRECCIÓN: ID 2 es Diésel en la tabla tipos_combustible
            $esDiesel = ($deposito->tipo_combustible_id == 2);

            // 2. Aplicar reglas según el combustible
            if ($esDiesel) {
                $cliente = DB::table('clientes')->where('id', $clienteId)->first();

                if (!$cliente) {
                    throw new Exception("El cliente seleccionado no existe.");
                }

                if ($cliente->disponible < $litros) {
                    throw new Exception("Saldo insuficiente: El cliente solo cuenta con {$cliente->disponible} litros disponibles en su Cupo Gasco.");
                }

                // Descontar del disponible general del cliente
                DB::table('clientes')->where('id', $clienteId)->decrement('disponible', $litros);

                // Acumular en el reporte mensual de Gasco
                $ahora = Carbon::now('America/Caracas');
                DB::table('gasco_cupos_mensuales')
                    ->where('cliente_id', $clienteId)
                    ->where('mes', $ahora->month)
                    ->where('anio', $ahora->year)
                    ->increment('litros_consumidos', $litros);
            }

            // 3. Descontar stock físico del tanque
            if ($deposito->nivel_actual_litros >= $litros) {
                $deposito->decrement('nivel_actual_litros', $litros);
            } else {
                // Salvaguarda operativa por si hay descuadre con el varillaje real
                $deposito->update(['nivel_actual_litros' => 0]);
            }

            // 4. Guardar registro inmutable usando el repositorio
            $llenado = $this->historialRepo->registrar([
                'cliente_id'          => $clienteId,
                'id_sede'             => $deposito->id_sede,
                'id_deposito'         => $deposito->id,
                'tipo_combustible_id' => $deposito->tipo_combustible_id,
                'litros'              => $litros,
            ]);

            return $llenado->id;
        });
    }
}