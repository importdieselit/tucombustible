<?php

namespace App\Repositories;

use App\Models\TransaccionCombustible;
use Illuminate\Support\Facades\DB;

class TransaccionCombustibleRepository
{
    /**
     * Registrar un nuevo movimiento en el Ledger.
     */
    public function registrar(array $data): TransaccionCombustible
    {
        return TransaccionCombustible::create($data);
    }

    public function getDisponibilidadPrepagada(int $sedeId, int $tipoCombustibleId): float
    {
        return (float) TransaccionCombustible::where('sede_id', $sedeId)
            ->where('tipo_combustible_id', $tipoCombustibleId)
            ->where('bolsa_tipo', 'prepagado')
            ->sum('cantidad_litros');
    }

    
    public function getSaldoFisicoGeneral(int $sedeId, int $tipoCombustibleId): float
    {
        return (float) TransaccionCombustible::where('sede_id', $sedeId)
            ->where('tipo_combustible_id', $tipoCombustibleId)
            ->where('bolsa_tipo', 'general')
            ->sum('cantidad_litros');
    }

    public function getSaldoTeoricoPorDeposito(int $depositoId): float
    {
        return (float) TransaccionCombustible::where('deposito_id', $depositoId)
            ->sum('cantidad_litros');
    }
}