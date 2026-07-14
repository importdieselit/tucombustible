<?php

namespace App\Repositories;

use App\Models\SaldoPendienteCliente;

class SaldoPendienteClienteRepository
{
    public function registrar(array $data): SaldoPendienteCliente
    {
        return SaldoPendienteCliente::create($data);
    }

    /**
     * Obtener el balance neto de litros pendientes que tiene un cliente a favor.
     */
    public function getBalancePendiente(int $clienteId, int $tipoCombustibleId): float
    {
        $totales = SaldoPendienteCliente::where('cliente_id', $clienteId)
            ->where('tipo_combustible_id', $tipoCombustibleId)
            ->selectRaw("
                SUM(CASE WHEN tipo_accion = 'acumulado' THEN cantidad_litros ELSE 0 END) as total_acumulado,
                SUM(CASE WHEN tipo_accion = 'consumido' THEN cantidad_litros ELSE 0 END) as total_consumido
            ")
            ->first();

        $acumulado = $totales->total_acumulado ?? 0;
        $consumido = $totales->total_consumido ?? 0;

        return (float) ($acumulado - $consumido);
    }
}