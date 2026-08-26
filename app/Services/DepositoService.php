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
use Illuminate\Support\Facades\Log;
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
    AforoCalculoService $aforoService, WhatsappApiService $whatsappService, HistorialLlenadoRepository $historialRepo, 
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
        // 1. Regla de Validación: Si existe un varillaje registrado y el usuario NO ha confirmado el duplicado, se detiene
        $existeDuplicado = $this->chequeoRepo->existeChequeo($data['id_sede'], $data['fecha'], $data['turno']);
        $confirmado = !empty($data['confirmar_duplicado']);

        if ($existeDuplicado && !$confirmado) {
            throw new Exception("DUPLICADO_DETECTADO");
        }

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

            $this->notificarResumenDisponibilidadSede(
                (int) $data['id_sede'], 
                $data['turno'], 
                auth()->user()->name ?? 'Sistema'
            );

            return $chequeoGuardado;
        });
    }

    protected function notificarTanquesCriticos(array $tanques, string $turno, string $nombreSede)
    {
        //$idDestino = config('services.whatsapp.dev_group_id', 'WHATSAPP_DEV_GROUP_ID');
        $idDestino = config('services.whatsapp.dev_group_id');
        //Log::info("Enviando alerta de WhatsApp para tanques críticos en la sede: {$nombreSede}, turno: {$turno}");
        //Log::info("idDestino: {$idDestino}, Tanques críticos: " . json_encode($tanques));

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

    public function registrarLlenado(int $clienteId, int $idDeposito, float $litros, int $choferClienteId, int $placaVehiculoId, ?string $observaciones = null): int
    {
        return DB::transaction(function () use ($clienteId, $idDeposito, $litros, $choferClienteId, $placaVehiculoId, $observaciones) {
            
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
                'observaciones'       => $observaciones,
            ]);

            // 5. Asentar Salida en el Ledger (Bolsa prepagada)
            $this->combustibleService->registrarDespachoPrepagado([
                'sede_id'             => $deposito->id_sede,
                'tipo_combustible_id' => $deposito->tipo_combustible_id,
                'cantidad_litros'     => -$litros,
                'deposito_id'         => $deposito->id,
                'referencia_id'       => $llenado->id,
                'cliente_id'          => $clienteId,
                'observaciones'       => $observaciones,
            ]);

            return $llenado->id;
        });
    }

    public function notificarResumenDisponibilidadSede(int $sedeId, string $turno = 'N/A', ?string $usuarioNombre = null): void
    {
        $sede = DB::table('sedes')->where('id', $sedeId)->first();
        $nombreSede = $sede ? $sede->nombre : "Sede #{$sedeId}";

        $idMgo    = 1;
        $idDiesel = 2;

        // 3. CONSULTA TANQUE POR TANQUE EN LA SEDE
        $tanques = DB::table('depositos as d')
            ->leftJoin('tipos_combustible as tc', 'd.tipo_combustible_id', '=', 'tc.id')
            ->select('d.id', 'd.serial', 'd.nivel_actual_litros', 'd.tipo_combustible_id', 'tc.nombre as nombre_combustible')
            ->where('d.id_sede', $sedeId)
            ->get();

        $tanquesDieselLitros = $tanques->where('tipo_combustible_id', $idDiesel)->sum('nivel_actual_litros');
        $tanquesMgoLitros    = $tanques->where('tipo_combustible_id', $idMgo)->sum('nivel_actual_litros');

        // 4. CONSULTA VEHÍCULO POR VEHÍCULO PRECARGADO (estatus = 0) EN LA SEDE
        $precargas = DB::table('vehiculos_precargados as vp')
            ->leftJoin('vehiculos as v', 'vp.id_vehiculo', '=', 'v.id')
            ->leftJoin('tipos_combustible as tc', 'vp.id_tipo_combustible', '=', 'tc.id')
            ->select('v.placa', 'v.modelo', 'vp.cantidad_litros', 'vp.id_tipo_combustible', 'tc.nombre as nombre_combustible')
            ->where('vp.id_sede', $sedeId)
            ->where('vp.estatus', 0)
            ->orderBy('vp.fecha_hora_carga', 'desc')
            ->get();

        $precargasDieselLitros = $precargas->where('id_tipo_combustible', $idDiesel)->sum('cantidad_litros');
        $precargasMgoLitros    = $precargas->where('id_tipo_combustible', $idMgo)->sum('cantidad_litros');

        // 5. COMPROMETIDOS (Viajes programados para la sede)
        $queryViajes = DB::table('viajes')
            ->where('status', 'PROGRAMADO')
            ->where('sede_id', $sedeId);

        $comprometidoMgo    = (clone $queryViajes)->where('tipo_planificacion', 2)->sum('litros') ?? 0;
        $comprometidoDiesel = (clone $queryViajes)->where('tipo_planificacion', 1)->sum('litros') ?? 0;

        // 6. CÁLCULOS TOTALES
        $totalFisicoDiesel = $tanquesDieselLitros + $precargasDieselLitros;
        $totalFisicoMgo    = $tanquesMgoLitros + $precargasMgoLitros;

        $disponibleNetoDiesel = $totalFisicoDiesel - $comprometidoDiesel;
        $disponibleNetoMgo    = $totalFisicoMgo - $comprometidoMgo;

        // 7. CONSTRUCCIÓN DEL MENSAJE DE WHATSAPP
        $fechaHora = now()->format('d/m/Y h:i A');

        $msj = "📋 *REPORTE DE DISPONIBILIDAD DE COMBUSTIBLE*\n";
        $msj .= "🏢 *Sede:* {$nombreSede}\n";
        $msj .= "📅 *Fecha/Hora:* {$fechaHora}\n";
        $msj .= "🌅 *Turno:* {$turno}\n";
        if ($usuarioNombre) {
            $msj .= "👤 *Registrado por:* {$usuarioNombre}\n";
        }
        $msj .= "────────────────────────────\n\n";

        // --- SECCIÓN 1: TANQUES ---
        $msj .= "🛢️ *DETALLE DE TANQUES EN SEDE*\n";
        if ($tanques->isEmpty()) {
            $msj .= "_Sin tanques registrados en esta sede._\n";
        } else {
            foreach ($tanques as $t) {
                $serial = $t->serial ?? "Tanque #{$t->id}";
                $comb = $t->nombre_combustible ?? ($t->tipo_combustible_id == $idDiesel ? 'Diesel' : 'MGO');
                $litros = number_format($t->nivel_actual_litros, 2, ',', '.');
                $msj .= "• *{$serial}* ({$comb}): {$litros} Lts\n";
            }
        }
        $msj .= "▫️ *Subtotal Tanques Diesel:* " . number_format($tanquesDieselLitros, 2, ',', '.') . " Lts\n";
        $msj .= "▫️ *Subtotal Tanques MGO:* " . number_format($tanquesMgoLitros, 2, ',', '.') . " Lts\n\n";

        // --- SECCIÓN 2: PRECARGAS ACTIVAS ---
        $msj .= "🚛 *UNIDADES PRECARGADAS ACTIVAS*\n";
        if ($precargas->isEmpty()) {
            $msj .= "_Sin vehículos precargados actualmente._\n";
        } else {
            foreach ($precargas as $p) {
                $placa = $p->placa ?? 'S/P';
                $modelo = $p->modelo ? " ({$p->modelo})" : '';
                $comb = $p->nombre_combustible ?? ($p->id_tipo_combustible == $idDiesel ? 'Diesel' : 'MGO');
                $litros = number_format($p->cantidad_litros, 2, ',', '.');
                $msj .= "• *{$placa}* - {$comb}: {$litros} Lts\n";
            }
        }
        $msj .= "▫️ *Subtotal Precargas Diesel:* " . number_format($precargasDieselLitros, 2, ',', '.') . " Lts\n";
        $msj .= "▫️ *Subtotal Precargas MGO:* " . number_format($precargasMgoLitros, 2, ',', '.') . " Lts\n\n";

        // --- SECCIÓN 3: BALANCE FINAL ---
        $msj .= "📊 *RESUMEN DE BALANCE FINAL*\n";
        
        // Diésel
        $msj .= "⛽ *DIÉSEL*\n";
        $msj .= "  ├ Tanques: " . number_format($tanquesDieselLitros, 2, ',', '.') . " Lts\n";
        $msj .= "  ├ Precargas: " . number_format($precargasDieselLitros, 2, ',', '.') . " Lts\n";
        $msj .= "  ├ *Total Físico:* " . number_format($totalFisicoDiesel, 2, ',', '.') . " Lts\n";
        $msj .= "  ├ *Comprometido:* " . number_format($comprometidoDiesel, 2, ',', '.') . " Lts\n";
        $msj .= "  └ 🟢 *Disponible Neto:* " . number_format($disponibleNetoDiesel, 2, ',', '.') . " Lts\n\n";

        // MGO
        $msj .= "⛽ *MGO*\n";
        $msj .= "  ├ Tanques: " . number_format($tanquesMgoLitros, 2, ',', '.') . " Lts\n";
        $msj .= "  ├ Precargas: " . number_format($precargasMgoLitros, 2, ',', '.') . " Lts\n";
        $msj .= "  ├ *Total Físico:* " . number_format($totalFisicoMgo, 2, ',', '.') . " Lts\n";
        $msj .= "  ├ *Comprometido:* " . number_format($comprometidoMgo, 2, ',', '.') . " Lts\n";
        $msj .= "  └ 🟢 *Disponible Neto:* " . number_format($disponibleNetoMgo, 2, ',', '.') . " Lts\n";

        // 8. ENVÍO VÍA WHATSAPP (Alineado con notificarTanquesCriticos)
        $idDestino = config('services.whatsapp.dev_group_id', 'WHATSAPP_DEV_GROUP_ID');

        try {
            $this->whatsappService->enviarMensaje($msj, $idDestino);
        } catch (Exception $e) {
            logger()->error("No se pudo enviar el reporte de disponibilidad por WhatsApp: " . $e->getMessage());
        }
    }
}