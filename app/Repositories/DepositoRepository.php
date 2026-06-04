<?php

namespace App\Repositories;

use App\Models\Deposito;

class DepositoRepository
{
    public function find($id): Deposito
    {
        return Deposito::findOrFail($id);
    }

    public function create(array $data): Deposito
    {
        return Deposito::create($data);
    }
}