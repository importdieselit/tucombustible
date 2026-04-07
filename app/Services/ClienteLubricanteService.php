<?php

namespace App\Services;

use App\Models\ClienteLubricante;
use App\Repositories\ClienteLubricanteRepository;

class ClienteLubricanteService
{
    protected ClienteLubricanteRepository $repository;

    public function __construct(ClienteLubricanteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function obtenerTodos()
    {
        return $this->repository->getAll();
    }

    public function registrar(array $data): ClienteLubricante
    {
        return $this->repository->create([
            'razon_social' => strtoupper($data['razon_social']),
            'rif'          => strtoupper($data['rif']),
            'email'        => strtolower($data['email']),
            'telefono'     => $data['telefono'] ?? null,
        ]);
    }

    public function actualizar($id, array $data): ClienteLubricante
    {
        return $this->repository->update($id, [
            'razon_social' => strtoupper($data['razon_social']),
            'rif'          => strtoupper($data['rif']),
            'email'        => strtolower($data['email']),
            'telefono'     => $data['telefono'] ?? null,
        ]);
    }

    public function eliminar($id): void
    {
        $this->repository->delete($id);
    }
}