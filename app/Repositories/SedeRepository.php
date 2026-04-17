<?php

namespace App\Repositories;

use App\Models\Sedes;

class SedeRepository
{
    public function getAllActivas()
    {
        return Sedes::where('estatus', true)->with(['estado', 'ciudad'])->get();
    }

    public function find($id)
    {
        return Sedes::findOrFail($id);
    }
}