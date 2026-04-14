<?php

namespace App\Services;

use App\Repositories\GascoCupoRepository;
use Carbon\Carbon;

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
        $ahora = Carbon::now();

        $cupo = $this->repository->updateOrCreateQuota([
            'cliente_id' => $clienteId,
            'mes'        => $ahora->month,
            'anio'       => $ahora->year,
        ], [
            'litros_autorizados' => $litros
        ]);

        // Aseguramos que si consumidos es null, se trate como 0
        $consumidos = $cupo->litros_consumidos ?? 0;
        $nuevoDisponible = $cupo->litros_autorizados - $consumidos;
        
        \App\Models\Cliente::where('id', $clienteId)->update([
            'disponible' => $nuevoDisponible
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