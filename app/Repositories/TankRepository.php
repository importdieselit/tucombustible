<?php

namespace App\Repositories;

use App\Models\Tanque;

class TankRepository {
    public function countAll() { return Tanque::count(); }
}