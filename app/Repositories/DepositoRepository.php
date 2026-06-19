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

    public function update($id, array $data): Deposito
    {
        $deposito = $this->find($id);
        $deposito->update($data);
        
        return $deposito;
    }

    public function delete($id): bool
    {
        $deposito = $this->find($id);
        return $deposito->delete();
    }
}