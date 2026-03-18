<?php

namespace App\Repositories;

use App\Models\ClienteLubricante;

class ClienteLubricanteRepository
{
    public function getAll()
    {
        return ClienteLubricante::orderBy('razon_social', 'asc')->paginate(15);
    }

    public function find($id): ClienteLubricante
    {
        return ClienteLubricante::findOrFail($id);
    }

    public function create(array $data): ClienteLubricante
    {
        return ClienteLubricante::create($data);
    }

    public function update($id, array $data): ClienteLubricante
    {
        $cliente = ClienteLubricante::findOrFail($id);
        $cliente->update($data);
        return $cliente;
    }

    public function delete($id): void
    {
        ClienteLubricante::findOrFail($id)->delete();
    }
}