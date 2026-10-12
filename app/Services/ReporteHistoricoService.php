<?php

namespace App\Services;

use App\Models\ReporteHistorico;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReporteHistoricoService
{
    /**
     * Guarda o actualiza la instantánea (snapshot) del reporte.
     */
    public function guardar(string $nombreReporte, $fecha, string $turno, array $data): ReporteHistorico
    {
        $fechaFormatted = Carbon::parse($fecha)->format('Y-m-d');

        return ReporteHistorico::updateOrCreate(
            [
                'nombre_reporte' => $nombreReporte,
                'fecha'          => $fechaFormatted,
                'turno'          => strtolower($turno),
            ],
            [
                'contenido' => $data // Laravel ejecuta json_encode internamente por el $cast
            ]
        );
    }

    /**
     * Obtiene el contenido de un reporte histórico si existe.
     */
    public function obtener(string $nombreReporte, string $fecha, string $turno): ?array
    {
        $fechaFormatted = Carbon::parse($fecha)->format('Y-m-d');

        $registro = ReporteHistorico::where('nombre_reporte', $nombreReporte)
            ->where('fecha', $fechaFormatted)
            ->where('turno', strtolower($turno))
            ->first();

        if (!$registro) {
            return null;
        }

        // Convertir arrays asociativos a objetos stdClass recursivamente
        // para mantener compatibilidad con sintaxis $v->propiedad en Blade
        return $this->normalizarParaBlade($registro->contenido);
    }

    /**
     * Convierte recursivamente arrays decoded de JSON a Objetos / Colecciones
     * para que Blade los consuma exactamente igual que los modelos Eloquent.
     */
   private function normalizarParaBlade($contenido): array
    {
        if (empty($contenido)) {
            return [];
        }

        // Si viene como string JSON, se decodifica a stdClass.
        // Si ya viene como array u objeto (por casting de Eloquent), se fuerza a stdClass.
        if (is_string($contenido)) {
            $data = (array) json_decode($contenido, false);
        } else {
            $data = (array) json_decode(json_encode($contenido), false);
        }

        // Normalizar sub-elementos a Colecciones de Laravel con acceso a objetos anidados
        return array_map(function ($value) {
            if (is_array($value)) {
                return collect($value)->map(function ($item) {
                    if (is_object($item)) {
                        // Convertir colecciones anidadas internas (ej: $viaje->despachos)
                        foreach (get_object_vars($item) as $propiedad => $valor) {
                            if (is_array($valor)) {
                                $item->$propiedad = collect($valor);
                            }
                        }
                    }
                    return $item;
                });
            }
            return $value;
        }, $data);
    }

    private function convertirAColecciones(mixed $node): mixed
    {
        if (is_array($node)) {
            // Si es un listado (array indexado), lo volvemos Collection y procesamos sus ítems
            return collect($node)->map(fn ($item) => $this->convertirAColecciones($item));
        }

        if ($node instanceof \stdClass) {
            // Si es un objeto, recorremos sus propiedades para procesar posibles sub-listas u objetos
            foreach ($node as $key => $value) {
                $node->{$key} = $this->convertirAColecciones($value);
            }
        }

        return $node;
    }
}