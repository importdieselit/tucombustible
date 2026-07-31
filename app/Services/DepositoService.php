<?php

namespace App\Services;

use App\Repositories\DepositoRepository;
use App\Repositories\ChequeoDepositoRepository;
use App\Repositories\HistorialLlenadoRepository;
use App\Repositories\GascoCupoRepository;
use App\Services\AforoCalculoService;
use App\Services\WhatsappApiService;
use App\Services\CombustibleService;
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
    protected $gascoCupoRepo;
    protected $combustibleService;

    public function __construct(DepositoRepository $depositoRepo, ChequeoDepositoRepository $chequeoRepo, 
    AforoCalculoService $aforoService, WhatsAppApiService $whatsappService, HistorialLlenadoRepository $historialRepo, 
    GascoCupoRepository $gascoCupoRepo, CombustibleService $combustibleService
    ) {
        $this->depositoRepo = $depositoRepo;
        $this->chequeoRepo = $chequeoRepo;
        $this->aforoService = $aforoService;
        $this->whatsappService = $whatsappService;
        $this->historialRepo = $historialRepo;
        $this->gascoCupoRepo = $gascoCupoRepo;
        $this->combustibleService = $combustibleService;
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

        // Envolvemos todo el proceso en una transacción global para blindar la operación
        return DB::transaction(function () use ($data) {
            
            $detallesProcesados = [];
            $tanquesCriticos = []; 

            // 2. Procesamiento secuencial de cubicación
            foreach ($data['detalles'] as $detalle) {
                $deposito = Deposito::findOrFail($detalle['id_deposito']);
                
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
                        'nivel_actual_litros' => round($litrosCalculados, 2),
                        'nivel_alerta_litros' => round($deposito->nivel_alerta_litros, 2),
                        'porcentaje' => $porcentajeActual
                    ];
                }

                $detallesProcesados[] = [
                    'id_deposito'          => $detalle['id_deposito'],
                    'centimetros_medidos'  => $detalle['centimetros_medidos'],
                    'litros_calculados'    => $litrosCalculados,
                    'id_tipos_combustible' => $detalle['id_tipos_combustible'],
                ];
            }

            // Este método altera el array $detallesProcesados por REFERENCIA inyectando 'litros_teoricos' 
            // y 'merma_calculada', además de registrar inmediatamente el ajuste en transacciones_combustible.
            $this->combustibleService->calcularMermasParaChequeo($detallesProcesados, (int) $data['id_sede']);

            // 3. Preparación de los datos de cabecera firmados
            $datosCabecera = [
                'id_sede'       => $data['id_sede'],
                'turno'         => $data['turno'],
                'fecha'         => $data['fecha'],
                'observaciones' => $data['observaciones'] ?? null,
                'id_usuario'    => $data['id_usuario'] ?? auth()->id() ?? 1,
            ];

            // 4. Persistencia definitiva (el repositorio ya sabe leer las nuevas llaves inyectadas)
            $chequeoGuardado = $this->chequeoRepo->guardarChequeoCompleto($datosCabecera, $detallesProcesados);

            // 5. Notificación automatizada por niveles de fosa críticos
            if ($chequeoGuardado && !empty($tanquesCriticos)) {
                $nombreSede = DB::table('sedes')->where('id', $data['id_sede'])->value('nombre') ?? "Sede #{$data['id_sede']}";
                $this->notificarTanquesCriticos($tanquesCriticos, $data['turno'], $nombreSede);
            }

            return $chequeoGuardado;
        });
    }

    protected function notificarTanquesCriticos(array $tanques, string $turno, string $nombreSede)
    {
        $idDestino = config('services.whatsapp.dev_group_id', 'WHATSAPP_DEV_GROUP_ID');

        $mensaje = "⚠️ *ALERTA DE NIVELES CRÍTICOS* ⚠️\n";
        $mensaje .= "Se ha registrado el varillaje del turno: *{$turno}* en la sede *{$nombreSede}*.\n\n";
        $mensaje .= "Los siguientes tanques están por debajo del *20%* de su capacidad:\n\n";

        foreach ($tanques as $tanque) {
        $litrosFormateados     = number_format($tanque['nivel_actual_litros'], 2, ',', '.');
        $limiteFormateado      = number_format($tanque['nivel_alerta_litros'], 2, ',', '.');

            $mensaje .= "🛢️ *Tanque:* {$tanque['serial']}\n";
            $mensaje .= "🔹 *Nivel actual:* {$litrosFormateados} Lts\n";
            $mensaje .= "📉 *Límite de alerta (menos de 20%):* {$limiteFormateado} Lts\n";
            $mensaje .= "----------------------------------\n";
        }
        
        $mensaje .= "\nSe recomienda coordinar la logística de reabastecimiento.";

        try {
            $this->whatsappService->enviarMensaje($mensaje, $idDestino);
        } catch (Exception $e) {
            logger()->error("No se pudo enviar la alerta de WhatsApp: " . $e->getMessage());
        }
    }

    public function registrarLlenado(int $clienteId, int $idDeposito, float $litros, int $choferClienteId, int $placaVehiculoId): int
    {
        return DB::transaction(function () use ($clienteId, $idDeposito, $litros, $choferClienteId, $placaVehiculoId) {
            
            // 1. Validar el tanque seleccionado
            $deposito = Deposito::findOrFail($idDeposito);

            if (!$deposito->llena_cupo_prepagado) {
                throw new Exception("Operación denegada: Este tanque no está autorizado para Llenado Prepagado.");
            }

            // 2. Procesar la cascada de saldos del cliente (Saldo Pendiente -> Cupo GASCO)
            $resumenCobro = $this->combustibleService->procesarDescuentoSaldosCliente(
                $clienteId,
                $deposito->tipo_combustible_id,
                $litros
            );

            // 3. Descuento físico del inventario del tanque asignado al llenado
            if ($deposito->nivel_actual_litros >= $litros) {
                $deposito->decrement('nivel_actual_litros', $litros);
            } else {
                $deposito->update(['nivel_actual_litros' => 0]);
            }

            // 4. Registro histórico del llenado
            $llenado = $this->historialRepo->registrar([
                'cliente_id'          => $clienteId,
                'id_sede'             => $deposito->id_sede,
                'id_deposito'         => $deposito->id,
                'chofer_cliente_id'   => $choferClienteId,
                'placa_vehiculo_id'   => $placaVehiculoId,
                'tipo_combustible_id' => $deposito->tipo_combustible_id,
                'litros'              => $litros,
            ]);

            // 5. Asentar Salida en el Ledger (Bolsa prepagada)
            $this->combustibleService->registrarDespachoPrepagado([
                'sede_id'             => $deposito->id_sede,
                'tipo_combustible_id' => $deposito->tipo_combustible_id,
                'cantidad_litros'     => -$litros,
                'deposito_id'         => $deposito->id,
                'referencia_id'       => $llenado->id,
                'cliente_id'          => $clienteId,
            ]);

            return $llenado->id;
        });
    }
}