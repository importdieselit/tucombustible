<?php

namespace App\Repositories;
use App\Models\SuministroCompra;

class PurchaseRepository {
    public function countPendientes() {
        return SuministroCompra::where('estatus', 1)->count();
    }
}