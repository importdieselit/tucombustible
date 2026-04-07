<?php

namespace App\Repositories;

use App\Models\Orden;

class OrderRepository
{
    public function countAbiertas()
    {
        return Orden::where('estatus', 'Abierta')->count();
    }

    public function getUltimas($cantidad = 5)
    {
        return Orden::with('vehiculoBelong')->orderBy('id', 'desc')->take($cantidad)->get();
    }
}