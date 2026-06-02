<?php

namespace App\Repositories;

use App\Models\Deposito;

class DepositoRepository
{
    /**
     * Guarda el nuevo tanque de forma directa en MySQL.
     */
    public function create(array $data): Deposito
    {
        return Deposito::create($data);
    }
}