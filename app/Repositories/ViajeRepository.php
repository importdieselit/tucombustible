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
}