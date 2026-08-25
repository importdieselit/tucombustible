<?php

namespace App\Services;

use App\Models\Deposito;
use App\Repositories\TransaccionCombustibleRepository;
use App\Repositories\SaldoPendienteClienteRepository;
use App\Repositories\TrasegadoRepository;
use App\Repositories\ConsumoOperativoRepository;
use App\Repositories\ReversoCombustibleRepository;
use App\Repositories\GascoCupoRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class CombustibleService
{
    // Identificador único de la Sede Principal (Caracas)
    public const SEDE_PRINCIPAL_ID = 1;

    protected $ledgerRepo;
    protected $saldoClienteRepo;
    protected $trasegadoRepo;
    protected $consumoRepo;
    protected $reversoRepo;
    protected $gascoCupoRepo;

    public function __construct(
        TransaccionCombustibleRepository $ledgerRepo,
        SaldoPendienteClienteRepository $saldoClienteRepo,
        TrasegadoRepository $trasegadoRepo,
        ConsumoOperativoRepository $consumoRepo,
        ReversoCombustibleRepository $reversoRepo,
        GascoCupoRepository $gascoCupoRepo
    ) {
        $this->ledgerRepo = $ledgerRepo;
        $this->saldoClienteRepo = $saldoClienteRepo;
        $this->trasegadoRepo = $trasegadoRepo;
        $this->consumoRepo = $consumoRepo;
        $this->reversoRepo = $reversoRepo;
        $this->gascoCupoRepo = $gascoCupoRepo;
    }

    public function obtenerDisponibilidadPorSede(int $sedeId, int $tipoCombustibleId): array
    {
        return [
            'general'   => $this->ledgerRepo->getSaldoFisicoGeneral($sedeId, $tipoCombustibleId),
            'prepagado' => $this->ledgerRepo->getDisponibilidadPrepagada($sedeId, $tipoCombustibleId)
        ];
    }

    public function calcularMermasParaChequeo(array &$detallesTanques, int $sedeId): void
    {
        foreach ($detallesTanques as &$detalle) {
            $idDeposito = $detalle['id_deposito'];
            
            $saldoTeoricoSistema = $this->ledgerRepo->getSaldoTeoricoPorDeposito($idDeposito) ?? 0.0;
            $litrosMedidosVara = (float) $detalle['litros_calculados'];
            $merma = $litrosMedidosVara - $saldoTeoricoSistema;

            $detalle['litros_teoricos'] = $saldoTeoricoSistema;
            $detalle['merma_calculada'] = $merma;

            if ($merma != 0) {
                $this->ledgerRepo->registrar([
                    'sede_id'             => $sedeId,
                    'tipo_combustible_id' => $detalle['id_tipos_combustible'],
                    'bolsa_tipo'          => 'general',
                    'tipo_movimiento'     => $merma > 0 ? 'ajuste_positivo' : 'ajuste_negativo',
                    'cantidad_litros'     => $merma,
                    'deposito_id'         => $idDeposito,
                    'user_id'             => auth()->id() ?? 1,
                    'observaciones'       => 'Ajuste automático generado por conciliación de varillaje.'
                ]);
            }
        }
    }

    public function registrarDespachoPrepagado(array $data): void
    {
        $this->ledgerRepo->registrar([
            'sede_id'             => $data['sede_id'],
            'tipo_combustible_id' => $data['tipo_combustible_id'],
            'bolsa_tipo'          => 'prepagado',
            'tipo_movimiento'     => 'despacho_prepagado',
            'cantidad_litros'     => -abs($data['cantidad_litros']),
            'deposito_id'         => $data['deposito_id'],
            'user_id'             => auth()->id() ?? 1,
            'referencia_id'       => $data['referencia_id'] ?? null,
            'cliente_id'          => $data['cliente_id'] ?? null,
        ]);
    }

    public function registrarConsumoOperativo(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $cantidadLitros = (float) ($data['cantidad_litros'] ?? 0);
            $userId = $data['user_id'] ?? (auth()->id() ?? 1);

            if ($cantidadLitros <= 0) {
                throw new Exception("La cantidad de litros para el consumo operativo debe ser mayor a cero.");
            }

            $depositoId = $data['deposito_id'];
            $deposito = Deposito::lockForUpdate()->findOrFail($depositoId);

            $deposito->decrement('nivel_actual_litros', $cantidadLitros);

            $consumo = $this->consumoRepo->create([
                'sede_id'             => $data['id_sede'] ?? $data['sede_id'],
                'deposito_id'         => $depositoId,
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'cantidad_litros'     => $cantidadLitros,
                'vehiculo_id'         => $data['vehiculo_id'] ?? null,
                'equipo_maquinaria'   => strtoupper($data['equipo_maquinaria'] ?? 'GENÉRICO'),
                'user_id'             => $userId,
                'observaciones'       => $data['observaciones'] ?? null,
            ]);

            $consumoId = is_object($consumo) ? $consumo->id : $consumo;

            $this->ledgerRepo->registrar([
                'sede_id'             => $data['id_sede'] ?? $data['sede_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => 'general',
                'tipo_movimiento'     => 'consumo_operativo',
                'cantidad_litros'     => -abs($cantidadLitros),
                'deposito_id'         => $depositoId,
                'user_id'             => $userId,
                'observaciones'       => "Salida por Consumo Operativo #{$consumoId}."
            ]);

            return $consumoId;
        });
    }

    public function registrarReversoCombustible(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $cantidadLitros = (float) ($data['cantidad_litros'] ?? 0);
            $userId = $data['user_id'] ?? (auth()->id() ?? 1);
            $idSede = $data['sede_id'];
            $tipoCombustibleId = $data['tipo_combustible_id'];

            if ($cantidadLitros <= 0) {
                throw new Exception("La cantidad de litros a reversar debe ser mayor a cero.");
            }

            // 1. Obtener tanques generales de la sede coincidentes con el tipo de combustible (excluyendo cupo prepagado)
            $tanques = Deposito::where('id_sede', $idSede)
                ->where('tipo_combustible_id', $tipoCombustibleId)
                ->where(function ($q) {
                    $q->where('llena_cupo_prepagado', false)
                    ->orWhere('llena_cupo_prepagado', 0)
                    ->orWhereNull('llena_cupo_prepagado');
                })
                ->lockForUpdate()
                ->get();

            if ($tanques->isEmpty()) {
                throw new Exception("No hay tanques habilitados de este tipo de combustible para recibir el reverso en esta sede.");
            }

            // 2. Validar capacidad total disponible en los tanques aptos
            $espacioTotal = $tanques->sum(function ($tanque) {
                return max(0, (float)$tanque->capacidad_litros - (float)$tanque->nivel_actual_litros);
            });

            if ($cantidadLitros > $espacioTotal) {
                throw new Exception("No hay capacidad suficiente en los tanques para recibir este reverso de combustible.");
            }

            // 3. Registrar la cabecera del reverso
            $reverso = $this->reversoRepo->create([
                'sede_id'             => $idSede,
                'cliente_id'          => null,
                'tipo_combustible_id' => $tipoCombustibleId,
                'cantidad_litros'     => $cantidadLitros,
                'motivo_reverso'      => $data['motivo_reverso'] ?? 'Retorno de producto a depósitos generales',
                'user_id'             => $userId,
            ]);

            $reversoId = is_object($reverso) ? $reverso->id : $reverso;

            // 4. Algoritmo de distribución equitativa (idéntico a AbastecimientoTanqueService)
            $litrosRestantes = $cantidadLitros;

            while ($litrosRestantes > 0) {
                $tanquesConEspacio = $tanques->filter(function ($tanque) {
                    return ((float)$tanque->capacidad_litros - (float)$tanque->nivel_actual_litros) > 0.0001;
                });

                if ($tanquesConEspacio->isEmpty()) {
                    break;
                }

                $numTanques = $tanquesConEspacio->count();
                $cuotaPorTanque = $litrosRestantes / $numTanques;

                foreach ($tanquesConEspacio as $tanque) {
                    $espacioLibre = (float)$tanque->capacidad_litros - (float)$tanque->nivel_actual_litros;
                    $aAsignar = min($cuotaPorTanque, $espacioLibre);

                    if ($aAsignar > 0) {
                        // Suma litros al depósito manteniendo su tipo_combustible_id intacto
                        $tanque->increment('nivel_actual_litros', $aAsignar);
                        $litrosRestantes -= $aAsignar;

                        // Registro individual en el Ledger por tanque afectado
                        $this->ledgerRepo->registrar([
                            'sede_id'             => $idSede,
                            'deposito_id'         => $tanque->id,
                            'tipo_combustible_id' => $tanque->tipo_combustible_id,
                            'bolsa_tipo'          => 'general',
                            'tipo_movimiento'     => 'reverso',
                            'cantidad_litros'     => $aAsignar,
                            'user_id'             => $userId,
                            'observaciones'       => "Ingreso por reverso #{$reversoId} hacia Depósito {$tanque->serial}",
                        ]);
                    }
                }

                if (round($litrosRestantes, 2) <= 0) {
                    $litrosRestantes = 0;
                    break;
                }
            }

            return $reversoId;
        });
    }

    public function procesarDescuentoSaldosCliente(
        int $clienteId,
        int $tipoCombustibleId,
        float $litrosRequeridos,
        ?int $llenadoPrepagadoId = null,
        ?int $userId = null
    ): array {
        $userId = $userId ?? (auth()->id() ?? 1);
        
        $saldoPendienteDisponible = (float) $this->saldoClienteRepo->getBalancePendiente($clienteId, $tipoCombustibleId);

        $consumidoSaldoPendiente = 0.0;
        $litrosRemanentes = $litrosRequeridos;

        if ($saldoPendienteDisponible > 0) {
            $consumidoSaldoPendiente = min($saldoPendienteDisponible, $litrosRequeridos);
            $litrosRemanentes -= $consumidoSaldoPendiente;

            $this->saldoClienteRepo->registrar([
                'cliente_id'           => $clienteId,
                'tipo_combustible_id'  => $tipoCombustibleId,
                'tipo_accion'          => 'consumido',
                'cantidad_litros'      => $consumidoSaldoPendiente,
                'user_id'              => $userId,
                'observaciones'        => "Consumo de saldo a favor aplicado a despacho/llenado.",
            ]);
        }

        $esDiesel = ($tipoCombustibleId == 2);
        $consumidoCupoGasco = 0.0;

        if ($esDiesel && $litrosRemanentes > 0) {
            $cliente = DB::table('clientes')
                ->where('id', $clienteId)
                ->lockForUpdate()
                ->first();

            if (!$cliente) {
                throw new Exception("El cliente seleccionado no existe.");
            }

            $mes = (int) now()->month;
            $anio = (int) now()->year;

            $cupoMensual = null;
            try {
                $cupoMensual = $this->gascoCupoRepo->getOrCreateMonthlyQuota($clienteId);
            } catch (\Throwable $e) {
                $cupoMensual = null;
            }

            if ($cupoMensual) {
                $this->gascoCupoRepo->updateConsumed($cupoMensual->id, $litrosRemanentes);
            } else {
                $registroExistente = DB::table('gasco_cupos_mensuales')
                    ->where('cliente_id', $clienteId)
                    ->where('mes', $mes)
                    ->where('anio', $anio)
                    ->first();

                if ($registroExistente) {
                    DB::table('gasco_cupos_mensuales')
                        ->where('id', $registroExistente->id)
                        ->increment('litros_consumidos', $litrosRemanentes);
                } else {
                    DB::table('gasco_cupos_mensuales')->insert([
                        'cliente_id'         => $clienteId,
                        'mes'                => $mes,
                        'anio'               => $anio,
                        'litros_autorizados' => (float) ($cliente->cupo ?? 0.00),
                        'litros_consumidos'  => $litrosRemanentes,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }

            $disponibleActual = (float) ($cliente->disponible ?? 0.00);
            DB::table('clientes')
                ->where('id', $clienteId)
                ->update([
                    'disponible' => $disponibleActual - $litrosRemanentes,
                    'updated_at' => now(),
                ]);

            $consumidoCupoGasco = $litrosRemanentes;
            $litrosRemanentes = 0.0;
        }

        return [
            'consumido_saldo'           => $consumidoSaldoPendiente,
            'consumido_saldo_pendiente' => $consumidoSaldoPendiente,
            'consumido_cupo'            => $consumidoCupoGasco,
            'consumido_cupo_gasco'      => $consumidoCupoGasco,
            'remanente_litros'          => $litrosRemanentes,
        ];
    }

    public function obtenerHistorialMermas(array $filtros)
    {
        return $this->ledgerRepo->getHistorialMermas($filtros);
    }

    public function obtenerTotalLitrosMermas(array $filtros): float
    {
        return $this->ledgerRepo->getTotalLitrosMermas($filtros);
    }

    public function obtenerMetricasDashboard(?int $sedeId = null): array
    {
        // 1. Obtención de IDs de tipos de combustible
        $idDiesel = DB::table('tipos_combustible')->where('nombre', 'LIKE', '%DIESEL%')->value('id') ?? 2;
        $idMgo    = DB::table('tipos_combustible')->where('nombre', 'LIKE', '%M.G.O.%')->value('id') ?? 1;

        // 2. DISPONIBILIDAD EN TANQUES (depositos.nivel_actual_litros)
        $tanquesDiesel = DB::table('depositos')
            ->where('tipo_combustible_id', $idDiesel)
            ->when($sedeId, fn($q) => $q->where('id_sede', $sedeId))
            ->sum('nivel_actual_litros') ?? 0;

        $tanquesMgo = DB::table('depositos')
            ->where('tipo_combustible_id', $idMgo)
            ->when($sedeId, fn($q) => $q->where('id_sede', $sedeId))
            ->sum('nivel_actual_litros') ?? 0;

        // 3. DISPONIBILIDAD EN VEHÍCULOS PRECARGADOS (vehiculos_precargados.cantidad_litros con estatus = 0)
        $precargasDiesel = DB::table('vehiculos_precargados')
            ->where('id_tipo_combustible', $idDiesel)
            ->where('estatus', 0)
            ->when($sedeId, fn($q) => $q->where('id_sede', $sedeId))
            ->sum('cantidad_litros') ?? 0;

        $precargasMgo = DB::table('vehiculos_precargados')
            ->where('id_tipo_combustible', $idMgo)
            ->where('estatus', 0)
            ->when($sedeId, fn($q) => $q->where('id_sede', $sedeId))
            ->sum('cantidad_litros') ?? 0;

        // 4. SUMATORIA TOTAL DE DISPONIBILIDAD (Tanques + Precargas Activas)
        $totalDisponibleDiesel = $tanquesDiesel + $precargasDiesel;
        $totalDisponibleMgo    = $tanquesMgo + $precargasMgo;
        $totalDisponibleGeneral = $totalDisponibleDiesel + $totalDisponibleMgo;

        // 5. COMPROMETIDOS (Se mantiene desde la tabla de viajes / transacciones)
        $queryViajes = DB::table('viajes')
            ->where('status', 'PROGRAMADO')
            ->when($sedeId, fn($q) => $q->where('sede_id', $sedeId));

        $comprometidoDiesel = (clone $queryViajes)->where('tipo_planificacion', 1)->sum('litros') ?? 0;
        $comprometidoMgo    = (clone $queryViajes)->where('tipo_planificacion', 2)->sum('litros') ?? 0;
        $totalComprometido  = $comprometidoDiesel + $comprometidoMgo;

        // 6. INFRAESTRUCTURA Y CAPACIDADES INSTALADAS
        $queryDepositos  = Deposito::when($sedeId, fn($q) => $q->where('id_sede', $sedeId));
        $tanquesActivos  = (clone $queryDepositos)->count();
        $capacidadDiesel = (clone $queryDepositos)->where('tipo_combustible_id', $idDiesel)->sum('capacidad_litros');
        $capacidadMgo    = (clone $queryDepositos)->where('tipo_combustible_id', $idMgo)->sum('capacidad_litros');

        // 7. DESGLOSE INDIVIDUAL DE TANQUES PARA LA TABLA
        $disponibilidades = Deposito::when($sedeId, fn($q) => $q->where('id_sede', $sedeId))
            ->with(['sedes', 'tipoCombustible', 'ultimaMedicion'])
            ->paginate(15);

        $ultimaMedicion = Deposito::when($sedeId, fn($q) => $q->where('id_sede', $sedeId))
            ->with('ultimaMedicion')->get()->pluck('ultimaMedicion.created_at')->filter()->max();

        // 8. LISTA DE VEHÍCULOS PRECARGADOS ACTIVOS (estatus = 0)
        $vehiculosPrecargados = DB::table('vehiculos_precargados as vp')
            ->leftJoin('vehiculos as v', 'vp.id_vehiculo', '=', 'v.id')
            ->leftJoin('sedes as s', 'vp.id_sede', '=', 's.id')
            ->leftJoin('tipos_combustible as tc', 'vp.id_tipo_combustible', '=', 'tc.id')
            ->leftJoin('depositos as d', 'vp.id_deposito', '=', 'd.id')
            ->select(
                'vp.*',
                'v.placa',
                'v.modelo',
                's.nombre as nombre_sede',
                'tc.nombre as nombre_combustible',
                'd.serial as tanque_origen'
            )
            ->where('vp.estatus', 0)
            ->when($sedeId, fn($q) => $q->where('vp.id_sede', $sedeId))
            ->orderBy('vp.fecha_hora_carga', 'desc')
            ->get();

        // KPI: Disponibilidad Teórica de MGO (Compras - Despachos)
        // Compras de MGO (ID 1 en tipos_combustible)
        $totalComprasMgoTeorico = DB::table('compras_combustible')
            ->where('tipo', 1)
            ->when($sedeId, fn($q) => $q->where('planta_destino_id', $sedeId)) // Ajustar a 'id_sede' si tu columna se llama así
            ->sum('cantidad_litros') ?? 0;

        // Despachos de MGO (tipo_planificacion = 1 según lo indicado)
        $totalDespachosMgoTeorico = DB::table('viajes')
            ->where('tipo_planificacion', 1)
            ->when($sedeId, fn($q) => $q->where('sede_id', $sedeId))
            ->sum('litros') ?? 0;

        $disponibilidadTeoricaMgo = $totalComprasMgoTeorico - $totalDespachosMgoTeorico;

        return [
            'general_fisico'          => $totalDisponibleGeneral,
            'general_comprometido'    => $totalComprometido,
            'general_venta'           => $totalDisponibleGeneral - $totalComprometido,
            
            // Diésel
            'totalDisponibleDiesel'   => $totalDisponibleDiesel,
            'tanquesDiesel'           => $tanquesDiesel,
            'precargasDiesel'         => $precargasDiesel,
            'porcentajeDiesel'        => $capacidadDiesel > 0 ? ($tanquesDiesel / $capacidadDiesel) * 100 : 0,
            'totalComprometidoDiesel' => $comprometidoDiesel,

            // MGO
            'totalDisponibleMgo'      => $totalDisponibleMgo,
            'tanquesMgo'              => $tanquesMgo,
            'precargasMgo'            => $precargasMgo,
            'porcentajeMgo'           => $capacidadMgo > 0 ? ($tanquesMgo / $capacidadMgo) * 100 : 0,
            'totalComprometidoMgo'     => $comprometidoMgo,
            'disponibilidadTeoricaMgo' => $disponibilidadTeoricaMgo,
            'totalComprasMgoTeorico'   => $totalComprasMgoTeorico,
            'totalDespachosMgoTeorico' => $totalDespachosMgoTeorico,

            // Infraestructura y Tablas
            'tanquesActivos'          => $tanquesActivos,
            'ultimaMedicion'          => $ultimaMedicion,
            'disponibilidades'        => $disponibilidades,
            'vehiculosPrecargados'    => $vehiculosPrecargados,
        ];
    }
}