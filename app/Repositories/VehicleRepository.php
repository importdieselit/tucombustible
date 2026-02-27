<?php

namespace App\Repositories;

use App\Models\Vehiculo;

class VehicleRepository
{
    public function countAll()
    {
        return Vehiculo::count();
    }

    public function countDisponibles()
    {
        return Vehiculo::where('es_flota', true)
            ->where('status', 1)
            ->count();
    }

    public function countEnMantenimiento()
    {
        // Usamos el método que ya tenías definido en el modelo
        return Vehiculo::countVehiculosEnMantenimiento();
    }

    public function countConOrdenAbierta()
    {
        // Usamos el scope que ya tenías definido en el modelo
        return Vehiculo::VehiculosConOrdenAbierta()->count();
    }
}