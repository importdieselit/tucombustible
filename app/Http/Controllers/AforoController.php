<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use App\Models\Aforo;
use App\Traits\CalcularAforo;
use Maatwebsite\Excel\Facades\Excel; 
use App\Exports\AforoCondensedExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AforoController extends Controller
{
    use CalcularAforo;
    
    public function showAforoTable($id) 
    {
        $deposito = Deposito::findOrFail($id);
        
        // Determinamos el techo de la tabla según la forma
        $alturaMaxima = ($deposito->forma == 'R' || $deposito->forma == 'OV') 
                        ? $deposito->alto 
                        : $deposito->diametro;

        $pasoAforo = 0.5; 
        $numColumnasRango = 5;
        $rangoPorColumna = ceil($alturaMaxima / $numColumnasRango);

        $tablaCondensada = [];
        $maxFilas = 0;
        // Generamos el aforo dinámicamente
        for ($h = 0; $h <= $alturaMaxima; $h += $pasoAforo) {
            $litros = $this->calcularLitros($deposito, $h);

            $indiceColumna = floor($h / $rangoPorColumna);
            $hRelativa = $h - ($indiceColumna * $rangoPorColumna);
            $indiceFila = round($hRelativa / $pasoAforo);

            $tablaCondensada[$indiceFila][$indiceColumna] = [
                'cm' => $h,
                'litros' => $litros
            ];

            if ($indiceFila > $maxFilas) $maxFilas = $indiceFila;
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
        $nombreArchivo = 'Aforo_Tanque_' . str_replace(' ', '_', $deposito->serial) . '_' . date('Ymd') . '.xlsx';
        
        // Iniciar la descarga usando la clase de exportación
        return Excel::download(new AforoCondensedExport($deposito->id), $nombreArchivo);
    }

}