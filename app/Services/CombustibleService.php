<?php

namespace App\Services;

use App\Repositories\TransaccionCombustibleRepository;
use App\Repositories\SaldoPendienteClienteRepository;
use App\Repositories\TrasegadoRepository;
use App\Repositories\ConsumoOperativoRepository;
use App\Repositories\ReversoCombustibleRepository;
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

    public function __construct(
        TransaccionCombustibleRepository $ledgerRepo,
        SaldoPendienteClienteRepository $saldoClienteRepo,
        TrasegadoRepository $trasegadoRepo,
        ConsumoOperativoRepository $consumoRepo,
        ReversoCombustibleRepository $reversoRepo,
    ) {
        $this->ledgerRepo = $ledgerRepo;
        $this->saldoClienteRepo = $saldoClienteRepo;
        $this->trasegadoRepo = $trasegadoRepo;
        $this->consumoRepo = $consumoRepo;
        $this->reversoRepo = $reversoRepo;
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
            $saldoTeoricoSistema = $this->ledgerRepo->getSaldoTeoricoPorDeposito($idDeposito);

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
                    'tipo_movimiento' => 'ajuste_merma',
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
            'referencia_id'       => $data['referencia_id'] ?? null // Enlace directo con historial_llenados
        ]);
    }

    /**
     * CASO DE USO: Registrar el llenado para Consumo Operativo interno.
     * Descuenta el inventario físico real del tanque de la sede.
     */
    public function registrarConsumoOperativo(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $cantidadLitros = (float) ($data['cantidad_litros'] ?? 0);
            $userId = $data['user_id'] ?? (auth()->id() ?? 1);

            if ($cantidadLitros <= 0) {
                throw new Exception("La cantidad de litros para el consumo operativo debe ser mayor a cero.");
            }

            // 1. Guardar la causa en la tabla de consumos operativos
            $consumo = $this->consumoRepo->create([
                'sede_id'             => $data['sede_id'],
                'deposito_id'         => $data['deposito_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'cantidad_litros'     => $cantidadLitros,
                'equipo_maquinaria'   => strtoupper($data['equipo_maquinaria'] ?? 'GENÉRICO'),
                'user_id'             => $userId,
                'observaciones'       => $data['observaciones'] ?? null,
            ]);

            $consumoId = is_object($consumo) ? $consumo->id : $consumo;

            // 2. Asentar la salida física en el Ledger (Restar de la fosa)
            $this->ledgerRepo->registrar([
                'sede_id'             => $data['sede_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => 'general',
                'tipo_movimiento'     => 'consumo_operativo',
                'cantidad_litros'     => -abs($cantidadLitros), // (-) Resta
                'deposito_id'         => $data['deposito_id'],
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
     * 3. Devuelve físicamente los litros al tanque/depósito de ImporDiesel.
     */
    public function registrarReversoCombustible(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $cantidadLitros = (float) ($data['cantidad_litros'] ?? 0); // Los litros que rebotaron y volvieron
            $userId = $data['user_id'] ?? (auth()->id() ?? 1);

            if ($cantidadLitros <= 0) {
                throw new Exception("La cantidad de litros a reversar debe ser mayor a cero.");
            }

            // 1. Insertar soporte en la tabla 'reversos_combustible'
            $reverso = $this->reversoRepo->create([
                'viaje_id'            => $data['viaje_id'],
                'cliente_id'          => $data['cliente_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'cantidad_litros'     => $cantidadLitros,
                'motivo_reverso'      => $data['motivo_reverso'] ?? 'Capacidad excedida en tanque del cliente',
                'user_id'             => $userId,
            ]);

            $reversoId = is_object($reverso) ? $reverso->id : $reverso;

            // 2. Crear la cuenta por entregar en 'saldos_pendientes_clientes' (Tipo: acumulado)
            $this->saldoClienteRepo->registrar([
                'cliente_id'           => $data['cliente_id'],
                'tipo_combustible_id'  => $data['tipo_combustible_id'],
                'tipo_accion'          => 'acumulado', // Regla de negocio: Acumula por reverso 💡
                'cantidad_litros'      => $cantidadLitros,
                'viaje_id'             => $data['viaje_id'],
                'llenado_prepagado_id' => null,
                'user_id'              => $userId,
                'observaciones'        => "Saldo a favor generado por Reverso #{$reversoId} en Viaje #{$data['viaje_id']}.",
            ]);

            // 3. Registrar el INGRESO FÍSICO real en el Ledger (transacciones_combustible)
            // El camión trajo el producto de vuelta, por ende sumamos (+) a nuestra fosa
            $this->ledgerRepo->registrar([
                'sede_id'             => $data['sede_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => 'general', 
                'tipo_movimiento'     => 'ingreso_reverso', // Nombre semántico para tu auditoría
                'cantidad_litros'     => abs($cantidadLitros), // (+) Suma a tu inventario físico
                'deposito_id'         => $data['deposito_id'], // Fosa de la sede donde descargó el camión
                'user_id'             => $userId,
                'observaciones'       => "Ingreso físico por retorno de producto rebotado en Viaje #{$data['viaje_id']}."
            ]);

            return $reversoId;
        });
    }
}