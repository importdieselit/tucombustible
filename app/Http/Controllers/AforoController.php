<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use App\Models\Aforo;
use Maatwebsite\Excel\Facades\Excel; 
use App\Exports\AforoCondensedExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AforoController extends Controller
{
    public function showAforoTable($id) 
    {
        $deposito=Deposito::find($id);
        $aforos = Aforo::where('deposito_id', $deposito->id)
                        ->orderBy('profundidad_cm')
                        ->get();
                    
        $D = $deposito->diametro;
        $pasoAforo = 0.5; // cm
        $numColumnasRango = 5; // 5 pares de (CM|LITROS)

        // 1. Calcular el Rango de Profundidad por Columna (ΔH)
        $rangoPorColumna = ceil($D / $numColumnasRango); // 54 cm para un diámetro de 267 cm

        $tablaCondensada = [];
        $maxFilas = 0;

        foreach ($aforos as $aforo) {
            $h = $aforo->profundidad_cm;

            // 2. Determinar el Índice de la Columna (0 a 4)
            $indiceColumna = floor($h / $rangoPorColumna);

            // 3. Determinar el Índice de la Fila (Altura relativa dentro del rango)
            // Ejemplo: 50.0 cm está en la columna 0, fila 100. (50/0.5 = 100)
            // 54.0 cm está en la columna 1, fila 8. (54/0.5 = 108. (108 - (1*54/0.5)) = 8)
            $hRelativa = $h - ($indiceColumna * $rangoPorColumna);
            $indiceFila = round($hRelativa / $pasoAforo); // Redondeo por seguridad

            // Almacenar el volumen
            $tablaCondensada[$indiceFila][$indiceColumna] = $aforo->litros;

            // Actualizar el número máximo de filas
            if ($indiceFila > $maxFilas) {
                $maxFilas = $indiceFila;
            }
        }

        return view('deposito.aforo', compact('deposito', 'tablaCondensada', 'rangoPorColumna', 'numColumnasRango', 'maxFilas', 'pasoAforo'));
    }



    public function exportAforoTable(Deposito $deposito)
    {
        // Verificación de seguridad
        if (!$deposito) {
            return redirect()->back()->with('error', 'Depósito no encontrado.');
        }
        
        // Generar un nombre de archivo limpio y único
        $nombreArchivo = 'Aforo_Teorico_' . str_replace(' ', '_', $deposito->nombre) . '_' . date('Ymd') . '.xlsx';
        
        // Iniciar la descarga usando la clase de exportación
        return Excel::download(new AforoCondensedExport($deposito->id), $nombreArchivo);
    }

}