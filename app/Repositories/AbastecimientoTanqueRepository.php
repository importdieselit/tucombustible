<?php

namespace App\Repositories;

use App\Models\AbastecimientoTanque;

class AbastecimientoTanqueRepository
{
    public function crear(array $data): AbastecimientoTanque
    {
        return AbastecimientoTanque::create($data);
    }

    public function obtenerTodos(?int $idSede = null, int $perPage = 20)
    {
        return AbastecimientoTanque::with([
                'vehiculo',
                'deposito',
                'sede',
                'tipoCombustible',
                'usuario',
                'precargaOrigen',
                'compraCombustible'
            ])
            ->when($idSede, function ($query, $idSede) {
                return $query->where('id_sede', $idSede);
            })
            ->latest('fecha_hora')
            ->paginate($perPage);
    }

    public function buscarPorId(int $id): ?AbastecimientoTanque
    {
        return AbastecimientoTanque::with([
            'vehiculo',
            'deposito',
            'sede',
            'tipoCombustible',
            'usuario',
            'precargaOrigen',
            'compraCombustible'
        ])->find($id);
    }
}