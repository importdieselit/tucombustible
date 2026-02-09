<?php

namespace App\Exports;

use App\Models\Aforo;
use App\Models\Deposito;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AforoCondensedExport implements FromCollection, WithHeadings
{
    protected $depositoId;

    public function __construct(int $depositoId)
    {
        $this->depositoId = $depositoId;
    }

    // Define los encabezados de las 10 columnas de forma dinámica
    public function headings(): array
    {
        $deposito = Deposito::find($this->depositoId);
        $D = $deposito->diametro;
        $pasoAforo = 0.5;
        $numColumnasRango = 5;
        $rangoPorColumna = (int)ceil($D / $numColumnasRango);
        $headings = [];

        for ($j = 0; $j < $numColumnasRango; $j++) {
            $inicio = $j * $rangoPorColumna;
            $fin = min($D, ($j + 1) * $rangoPorColumna - $pasoAforo);

            // Encabezado para la columna CM
            $headings[] = "CM";
            // Encabezado para la columna LITROS
            $headings[] = "LITROS";
        }

        return $headings;
    }

    // Consulta y reestructura los datos para el formato de exportación
    public function collection(): Collection
    {
        $deposito = Deposito::find($this->depositoId);
        $aforos = Aforo::where('deposito_id', $this->depositoId)
                        ->orderBy('profundidad_cm')
                        ->get();

        $D = $deposito->diametro;
        $pasoAforo = 0.5;
        $numColumnasRango = 5;
        $rangoPorColumna = (int)ceil($D / $numColumnasRango);

        $tablaCondensada = [];
        $maxFilas = 0;

        // 1. Agrupar los datos en la estructura [fila][columna]
        foreach ($aforos as $aforo) {
            $h = $aforo->profundidad_cm;

            if ($h > $D) continue;

            $indiceColumna = (int)floor($h / $rangoPorColumna);
            $hRelativa = $h - ($indiceColumna * $rangoPorColumna);
            $indiceFila = (int)round($hRelativa / $pasoAforo);

            $tablaCondensada[$indiceFila][$indiceColumna]['h'] = $h;
            $tablaCondensada[$indiceFila][$indiceColumna]['v'] = $aforo->litros;

            if ($indiceFila > $maxFilas) {
                $maxFilas = $indiceFila;
            }
        }

        // 2. Convertir la estructura agrupada en filas planas de 10 columnas
        $exportRows = collect();

        for ($i = 0; $i <= $maxFilas; $i++) {
            $row = [];
            $hayDataEnFila = false;
            
            for ($j = 0; $j < $numColumnasRango; $j++) {
                $data = $tablaCondensada[$i][$j] ?? ['h' => null, 'v' => null];
                
                // Formatear y añadir CM (Columna par)
                $cm = ($data['h'] !== null && $data['h'] <= $D) ? number_format($data['h'], 1, '.', '') : '';
                $row[] = $cm;
                
                // Formatear y añadir LITROS (Columna impar)
                $litros = ($data['v'] !== null && $data['h'] <= $D) ? number_format($data['v'], 2, '.', '') : '';
                $row[] = $litros;
                
                if ($cm !== '' || $litros !== '') $hayDataEnFila = true;
            }
            
            // Solo añadir la fila si contiene algún dato real (para evitar filas vacías)
            if ($hayDataEnFila) {
                 $exportRows->push($row);
            }
        }

        return $exportRows;
    }
}