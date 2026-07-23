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
        GascoCupoRepository $gascoCupoRepo,
    ) {
        $this->ledgerRepo = $ledgerRepo;
        $this->saldoClienteRepo = $saldoClienteRepo;
        $this->trasegadoRepo = $trasegadoRepo;
        $this->consumoRepo = $consumoRepo;
        $this->reversoRepo = $reversoRepo;
        $this->gascoCupoRepo = $gascoCupoRepo;
    }

    /**
     * Regla de Negocio: Permite ver disponibilidades globales si es Caracas (ID 1),
     * de lo contrario, restringe estrictamente a la sede correspondiente.
     */
    public function obtenerDisponibilidadPorSede(int $sedeId, int $tipoCombustibleId): array
    {
        // Si es la Sede Principal (Caracas), tiene permisos para ver balances globales
        if ($sedeId === self::SEDE_PRINCIPAL_ID) {
            // Aquí podrás retornar el consolidado de todas las sedes cuando operen las demás
        }

        // Retorno estándar del Ledger para la sede consultada
        return [
            'general'   => $this->ledgerRepo->getSaldoFisicoGeneral($sedeId, $tipoCombustibleId),
            'prepagado' => $this->ledgerRepo->getDisponibilidadPrepagada($sedeId, $tipoCombustibleId)
        ];
    }


    /**
     * CASO DE USO: Interceptar el varillaje, calcular la merma matemática 
     * contra el Ledger y ajustar el saldo del sistema.
     */
    public function calcularMermasParaChequeo(array &$detallesTanques, int $sedeId): void
    {
        foreach ($detallesTanques as &$detalle) {
            $idDeposito = $detalle['id_deposito'];
            
            // 1. Obtener los litros que el sistema calcula que deberían haber en base a transacciones
            $saldoTeoricoSistema = $this->ledgerRepo->getSaldoTeoricoPorDeposito($idDeposito) ?? 0.0;

            // 2. Calcular Merma: Real de la Vara - Teórico del Sistema
            $litrosMedidosVara = (float) $detalle['litros_calculados'];
            $merma = $litrosMedidosVara - $saldoTeoricoSistema;

            // 3. Inyectar al arreglo por referencia para la persistencia del repositorio
            $detalle['litros_teoricos'] = $saldoTeoricoSistema;
            $detalle['merma_calculada'] = $merma;

            // 4. Si hay discrepancia, se asienta el ajuste en el Ledger para calibrar el sistema con la fosa
            if ($merma != 0) {
                $this->ledgerRepo->registrar([
                    'sede_id' => $sedeId,
                    'tipo_combustible_id' => $detalle['id_tipos_combustible'],
                    'bolsa_tipo' => 'general', // Las mermas físicas afectan directamente el inventario base
                    'tipo_movimiento' => $merma > 0 ? 'ajuste_positivo' : 'ajuste_negativo',
                    'cantidad_litros' => $merma, // Negativo resta (pérdida), positivo suma (ganancia térmica)
                    'deposito_id' => $idDeposito,
                    'user_id' => auth()->id() ?? 1,
                    'observaciones' => 'Ajuste automático generado por conciliación de varillaje.'
                ]);
            }
        }
    }

    /**
     * 🆕 Registra el Despacho Prepagado en el Ledger, no en las tablas llenado_cupo_prepagado
     */
    public function registrarDespachoPrepagado(array $data): void
    {
        $this->ledgerRepo->registrar([
            'sede_id'             => $data['sede_id'],
            'tipo_combustible_id' => $data['tipo_combustible_id'],
            'bolsa_tipo'          => 'prepagado',         // Forzado por regla de negocio
            'tipo_movimiento'     => 'despacho_prepagado', // Nombre semántico elegido
            'cantidad_litros'     => -abs($data['cantidad_litros']), // Forzamos negativo por seguridad contable
            'deposito_id'         => $data['deposito_id'],
            'user_id'             => auth()->id() ?? 1,
            'referencia_id'       => $data['referencia_id'] ?? null, // Enlace directo con historial_llenados
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

            // 1. Bloqueamos el tanque para asegurar consistencia en la fosa física
            $depositoId = $data['deposito_id'];
            
            // Usamos directamente el modelo Deposito (puedes importarlo arriba como App\Models\Deposito)
            $deposito = Deposito::lockForUpdate()->findOrFail($depositoId);

            // 2. Descontamos físicamente del tanque sin validar disponibilidad.
            // Si el nivel actual es menor, Laravel decrementará y quedará en números rojos de forma natural.
            $deposito->decrement('nivel_actual_litros', $cantidadLitros);

            // 3. Guardar la causa en la tabla de consumos operativos
            $consumo = $this->consumoRepo->create([
                'sede_id'             => $data['id_sede'] ?? $data['sede_id'], // Manejo flexible de la llave
                'deposito_id'         => $depositoId,
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'cantidad_litros'     => $cantidadLitros,
                'vehiculo_id'         => $data['vehiculo_id'] ?? null,
                'equipo_maquinaria'   => strtoupper($data['equipo_maquinaria'] ?? 'GENÉRICO'),
                'user_id'             => $userId,
                'observaciones'       => $data['observaciones'] ?? null,
            ]);

            $consumoId = is_object($consumo) ? $consumo->id : $consumo;

            // 4. Asentar la salida física en el Ledger (Restar de la fosa en el libro contable)
            $this->ledgerRepo->registrar([
                'sede_id'             => $data['id_sede'] ?? $data['sede_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => 'general',
                'tipo_movimiento'     => 'consumo_operativo',
                'cantidad_litros'     => -abs($cantidadLitros), // (-) Resta
                'deposito_id'         => $depositoId,
                'user_id'             => $userId,
                'observaciones'       => "Salida por Consumo Operativo #{$consumoId}."
            ]);

            return $consumoId;
        });
    }

    /**
     * CASO DE USO: Registrar un Reverso de Combustible (Producto devuelto por el cliente).
     * 1. Registra el reverso logístico.
     * 2. Acumula el saldo a favor del cliente para futuros consumos.
     */
    public function registrarReversoCombustible(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $cantidadLitros = (float) ($data['cantidad_litros'] ?? 0);
            $userId = $data['user_id'] ?? (auth()->id() ?? 1);

            if ($cantidadLitros <= 0) {
                throw new Exception("La cantidad de litros a reversar debe ser mayor a cero.");
            }

            // 1. Insertar soporte en la tabla 'reversos_combustible'
            $reverso = $this->reversoRepo->create([
                'sede_id'            => $data['sede_id'] ?? null,
                'cliente_id'          => $data['cliente_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'cantidad_litros'     => $cantidadLitros,
                'motivo_reverso'      => $data['motivo_reverso'] ?? 'Capacidad excedida en tanque del cliente',
                'user_id'             => $userId,
            ]);

            $reversoId = is_object($reverso) ? $reverso->id : $reverso;

            // 2. Crear el saldo a favor del cliente en 'saldos_pendientes_clientes' (Tipo: acumulado)
            $this->saldoClienteRepo->registrar([
                'cliente_id'           => $data['cliente_id'],
                'tipo_combustible_id'  => $data['tipo_combustible_id'],
                'tipo_accion'          => 'acumulado', // Acumula por reverso
                'cantidad_litros'      => $cantidadLitros,
                'user_id'              => $userId,
                'observaciones'        => "Saldo a favor generado por Reverso #{$reversoId}.",
            ]);

            // 3. Registrar el INGRESO en el Ledger a nivel de Sede (deposito_id = null)
            // Incrementa la disponibilidad general de la sede sin atarse a un tanque físico.
            $this->ledgerRepo->registrar([
                'sede_id'             => $data['sede_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => 'general', 
                'tipo_movimiento'     => 'reverso',
                'cantidad_litros'     => abs($cantidadLitros), // (+) Suma a disponibilidad general
                'deposito_id'         => null, // 💡 Libero el amarre al tanque físico
                'cliente_id'          => $data['cliente_id'],
                'user_id'             => $userId,
                'observaciones'       => "Ingreso por retorno de producto (Reverso #{$reversoId})."
            ]);

            return $reversoId;
        });
    }

    /**
     * REGLA DE NEGOCIO: Procesa el descuento del cliente evaluando la prioridad:
     * Primero Saldo Pendiente (Reversos acumulados) -> Luego Cupo GASCO (Si es Diésel).
     */
    public function procesarDescuentoSaldosCliente(int $clienteId, int $tipoCombustibleId, float $litrosRequeridos,
        ?int $llenadoPrepagadoId = null, ?int $userId = null): array {$userId = $userId ?? (auth()->id() ?? 1);
        
        // 1. Verificar si el cliente tiene saldo a favor acumulado
        $saldoPendienteDisponible = $this->saldoClienteRepo->getBalancePendiente($clienteId, $tipoCombustibleId);

        $consumidoSaldoPendiente = 0.0;
        $litrosRemanentes = $litrosRequeridos;

        // --- PASO 1: Consumir de Saldo Pendiente ---
        if ($saldoPendienteDisponible > 0) {
            // Se consume el máximo posible entre lo que necesita y lo que tiene a favor
            $consumidoSaldoPendiente = min($saldoPendienteDisponible, $litrosRequeridos);
            $litrosRemanentes -= $consumidoSaldoPendiente;

            $this->saldoClienteRepo->registrar([
                'cliente_id'           => $clienteId,
                'tipo_combustible_id'  => $tipoCombustibleId,
                'tipo_accion'          => 'consumido', // 💡 Registra el descuento del saldo a favor
                'cantidad_litros'      => $consumidoSaldoPendiente,
                'user_id'              => $userId,
                'observaciones'        => "Consumo de saldo a favor aplicado a despacho/llenado.",
            ]);
        }

        // --- PASO 2: Si queda remanente y es Diésel (ID 2), descontar de Cupo GASCO ---
        $esDiesel = ($tipoCombustibleId == 2);
        $consumidoCupoGasco = 0.0;

        if ($esDiesel && $litrosRemanentes > 0) {
            $cupoMensual = $this->gascoCupoRepo->getOrCreateMonthlyQuota($clienteId);

            if (!$cupoMensual) {
                throw new Exception("El cliente seleccionado no tiene un Cupo GASCO base configurado en el sistema.");
            }

            $cliente = DB::table('clientes')
                ->where('id', $clienteId)
                ->lockForUpdate()
                ->first();

            if (!$cliente) {
                throw new Exception("El cliente seleccionado no existe.");
            }

            if ($cliente->disponible < $litrosRemanentes) {
                throw new Exception("Saldo insuficiente: El cliente requiere {$litrosRemanentes} Lts de su Cupo GASCO, pero solo cuenta con {$cliente->disponible} Lts disponibles.");
            }

            // Se actualiza el consumo en el cupo mensual
            $this->gascoCupoRepo->updateConsumed($cupoMensual->id, $litrosRemanentes);
            $consumidoCupoGasco = $litrosRemanentes;
        }

        return [
            'consumido_saldo_pendiente' => $consumidoSaldoPendiente,
            'consumido_cupo_gasco'      => $consumidoCupoGasco,
            'remanente_litros'          => $litrosRemanentes,
        ];
    }
}