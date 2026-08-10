<?php

namespace App\Repositories;

use App\Models\VehiculoPrecargado;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class VehiculoPrecargadoRepository
{

    public function crear(array $data): VehiculoPrecargado
    {
        return VehiculoPrecargado::create($data);
    }

    public function obtenerActivas(?int $idSede = null): Collection
    {
        return VehiculoPrecargado::with([
                'vehiculo', 
                'sede', 
                'deposito', 
                'tipoCombustible'
            ])
            ->where('estatus', 0)
            ->when($idSede, function ($query, $idSede) {
                return $query->where('id_sede', $idSede);
            })
            ->orderBy('fecha_hora_carga', 'desc')
            ->get();
    }

    public function obtenerHistorico(?int $idSede = null, int $perPage = 20): LengthAwarePaginator
    {
        return VehiculoPrecargado::with([
                'vehiculo', 
                'sede', 
                'deposito', 
                'tipoCombustible'
            ])
            ->when($idSede, function ($query, $idSede) {
                return $query->where('id_sede', $idSede);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function buscarPorId(int $id): VehiculoPrecargado
    {
        return VehiculoPrecargado::findOrFail($id);
    }

    /**
     * Cambia el estatus de una precarga (ej. 0 = Activa, 1 = Finalizada/Procesada).
     */
    public function cambiarEstatus(int $id, int $estatus): bool
    {
        $precarga = $this->buscarPorId($id);
        return $precarga->update(['estatus' => $estatus]);
    }
}