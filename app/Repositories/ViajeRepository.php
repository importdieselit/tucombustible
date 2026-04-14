<?php

namespace App\Repositories;

use App\Models\Viaje;
use App\Models\DespachoViaje;

class ViajeRepository
{
    public function createViaje(array $data)
    {
        return Viaje::create($data);
    }

    public function createDetalle(array $data)
    {
        return DespachoViaje::create($data);
    }

    public function find($id)
    {
        return Viaje::with(['tipoCombustible', 'detalles.cliente', 'vehiculo', 'chofer'])->findOrFail($id);
    }

    public function getViajesProgramados()
    {
        return Viaje::where('status', 'PROGRAMADO')->orderBy('fecha_salida', 'asc')->get();
    }
}