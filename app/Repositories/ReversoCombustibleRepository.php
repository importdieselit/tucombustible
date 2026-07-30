<?php

namespace App\Repositories;

use App\Models\ReversoCombustible;

class ReversoCombustibleRepository {
    public function create(array $data): ReversoCombustible {
        return ReversoCombustible::create($data);
    }
}