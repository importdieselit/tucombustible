<?php

namespace App\Repositories;
use App\Models\MantenimientoProgramado;

class MaintenanceRepository {
    public function countProximos() {
        return MantenimientoProgramado::whereDate('fecha', '>=', now())->count();
    }
    public function countHoy() {
        return MantenimientoProgramado::whereDate('fecha', now())->count();
    }
}