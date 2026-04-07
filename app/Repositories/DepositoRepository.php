<?php

namespace App\Repositories;

use App\Models\Deposito;
use Exception;

class DepositoRepository
{
    public function find($id)
    {
        return Deposito::findOrFail($id);
    }

    /**
     * Descuenta el inventario físico del tanque (nivel_actual_litros)
     */
    public function restarDisponibilidad(int $id, float $cantidad)
    {
        $deposito = $this->find($id);
        
        if ($deposito->nivel_actual_litros < $cantidad) {
            throw new Exception("El depósito {$deposito->serial} no tiene combustible suficiente (Disponible: {$deposito->nivel_actual_litros}L).");
        }

        $deposito->decrement('nivel_actual_litros', $cantidad);
        return $deposito;
    }
}