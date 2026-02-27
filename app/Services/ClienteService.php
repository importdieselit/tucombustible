<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use Illuminate\Support\Str;

class ClienteService
{
    protected $repository;

    public function __construct(ClienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registrarProspecto(array $data)
    {
        // Agregamos la lógica de negocio antes de guardar
        $data['status'] = 0; // Inactivo por defecto
        $data['registro_paso'] = 1;
        $data['token_registro'] = Str::random(40);
        $data['nombre'] = strtoupper($data['razon_social']); // Estandarización

        return $this->repository->create($data);
    }
}