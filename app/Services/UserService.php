<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    // Método que faltaba para el index del controlador
    public function obtenerListaFiltrada(array $filtros, $clienteId = 0)
    {
        return $this->repository->getFiltrados($filtros, $clienteId);
    }

    public function obtenerDashboardData($clienteId)
    {
        $stats = $this->repository->getStatsPorPerfil($clienteId);
        return [
            'perfilesConteo' => $stats,
            'totalGeneral'   => $stats->sum('total')
        ];
    }

    public function actualizarPasswordObligatorio($userId, $newPassword)
    {
        return $this->repository->update($userId, [
            'password' => Hash::make($newPassword), // Laravel 10+ usa hashed por defecto en el cast, pero Hash::make es más seguro aquí
            'must_change_password' => 0,
            'status_usuario' => 'activo'
        ]);
    }

    public function registrarUsuario(array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return $this->repository->create($data);
    }
}