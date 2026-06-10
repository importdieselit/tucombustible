<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

class EvaluacionesRepository
{
    /**
     * Transforma las filas crudas del Sheet o del Mock en una colección estandarizada.
     */
    public function formatSheetRows(array $rows): Collection
    {
        return collect($rows)->map(function ($row) {
            return [
                'cedula'  => $row[0] ?? 'N/A',
                'nombre'  => $row[1] ?? 'N/A',
                'cargo'   => $row[2] ?? 'N/A',
                'estatus' => $row[3] ?? 'Pendiente',
            ];
        });
    }
}