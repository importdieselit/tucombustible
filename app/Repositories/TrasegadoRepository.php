<?php

namespace App\Repositories;

use App\Models\Trasegado;

class TrasegadoRepository {

    public function create(array $data): Trasegado {

        return Trasegado::create($data);

    }

    public function getHistoricoPorSede(int $sedeId) {

        return Trasegado::where(function ($query) use ($sedeId) {
                $query->where('sede_origen_id', $sedeId)
                      ->orWhere('sede_destino_id', $sedeId);
            })
            ->with(['aliado', 'tipoCombustible'])
            ->orderBy('created_at', 'desc')
            ->get();
            
    }
}