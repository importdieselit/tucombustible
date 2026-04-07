<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function obtenerListaFiltrada(array $filtros, int $clienteId = 0)
    {
        return $this->repository->getFiltrados($filtros, $clienteId);
    }

    public function obtenerDashboardData(int $clienteId): array
    {
        $stats = $this->repository->getStatsPorPerfil($clienteId);
        return [
            'perfilesConteo' => $stats,
            'totalGeneral'   => $stats->sum('total'),
        ];
    }

    public function actualizarPasswordObligatorio($userId, string $newPassword): void
    {
        $this->repository->update($userId, [
            'password'             => Hash::make($newPassword),
            'must_change_password' => 0,
            'status_usuario'       => 'activo',
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