<?php

namespace App\Repositories;

use App\Models\ConsumoOperativo;

class ConsumoOperativoRepository {
    public function create(array $data): ConsumoOperativo 
    {
        return ConsumoOperativo::create($data);
    }
}