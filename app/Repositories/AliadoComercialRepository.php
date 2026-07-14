<?php

namespace App\Repositories;

use App\Models\AliadoComercial;

class AliadoComercialRepository {
    public function allActivos() 
    { 
        return AliadoComercial::where('activo', true)->get(); 
    }

    public function create(array $data) 
    { 
        return AliadoComercial::create($data); 
    }
}