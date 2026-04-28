<?php

namespace App\Services;

use App\Repositories\GascoCupoRepository;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GascoCupoService
{
    protected GascoCupoRepository $repository;

    public function __construct(GascoCupoRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Asigna o actualiza el cupo del mes actual para un cliente.
     */
    public function asignarCupoMensual(int $clienteId, float $litros)
    {
        // 1. REGLA DE VALIDACIÓN SIAVCOM vs GASCO
        // Buscamos el Cupo SIAVCOM (litros_aprobados) del cliente
        $cupoSiavcom = DB::table('cliente_cupos')
            ->where('cliente_id', $clienteId)
            ->value('litros_aprobados') ?? 0;

        // Lógica: Si SIAVCOM > 0, GASCO <= SIAVCOM. Si es 0, sin restricción.
        if ($cupoSiavcom > 0 && $litros > $cupoSiavcom) {
            throw new \Exception("El Cupo GASCO ($litros Lts) no puede ser superior al Cupo SIAVCOM autorizado ($cupoSiavcom Lts).");
        }

        $ahora = Carbon::now();

        // 2. Persistencia en la tabla de Gasco (Llama a tu repositorio)
        $cupo = $this->repository->updateOrCreateQuota([
            'cliente_id' => $clienteId,
            'mes'        => $ahora->month,
            'anio'       => $ahora->year,
        ], [
            'litros_autorizados' => $litros
        ]);

        // 3. Sincronización del Disponible en la tabla Clientes
        // Esto asegura que si el admin cambia el cupo a mitad de mes, 
        // el disponible se ajuste restando lo que ya se consumió.
        $consumidos = $cupo->litros_consumidos ?? 0;
        $nuevoDisponible = $cupo->litros_autorizados - $consumidos;
        
        Cliente::where('id', $clienteId)->update([
            'disponible' => max(0, $nuevoDisponible)
        ]);

        return $cupo;
    }

    /**
     * Obtiene el saldo disponible de GASCO para un cliente.
     */
    public function obtenerSaldoActual(int $clienteId): array
    {
        $cupo = $this->repository->getOrCreateMonthlyQuota($clienteId);

        return [
            'autorizados' => $cupo ? $cupo->litros_autorizados : 0,
            'disponible'  => $cupo ? $cupo->saldo_disponible : 0, // Usamos el atributo del Modelo
        ];
    }
}