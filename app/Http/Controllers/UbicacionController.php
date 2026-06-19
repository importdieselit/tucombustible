<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UbicacionController extends Controller
{

    public function generarMallaMasiva(Request $request)
    {
        $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'pasillo'    => 'required|string', // Ej: "A"
            'estantes'   => 'required|integer|min:1', // Ej: 10
            'niveles'    => 'required|integer|min:1', // Ej: 4
            'posiciones' => 'required|integer|min:1', // Ej: 5
        ]);

        $almacenId = $request->almacen_id;
        $pasillo = strtoupper($request->pasillo);
        $nuevasUbicaciones = [];

        // Bucle generador de coordenadas 3D
        for ($e = 1; $e <= $request->estantes; $e++) {
            for ($n = 1; $n <= $request->niveles; $n++) {
                for ($p = 1; $p <= $request->posiciones; $p++) {
                    
                    // Formateo con ceros a la izquierda: A-01-01-01
                    $codigo = sprintf("%s-%02d-%02d-%02d", $pasillo, $e, $n, $p);
                    
                    $nuevasUbicaciones[] = [
                        'almacen_id'       => $almacenId,
                        'codigo_ubicacion' => $codigo,
                        'pasillo'          => $pasillo,
                        'estante'          => str_pad($e, 2, '0', STR_PAD_LEFT),
                        'nivel'            => str_pad($n, 2, '0', STR_PAD_LEFT),
                        'posicion'         => str_pad($p, 2, '0', STR_PAD_LEFT),
                        'tipo'             => 'ESTANDAR',
                        'created_at'       => now(),
                        'updated_at'       => now()
                    ];
                }
            }
        }

        // Inserción masiva optimizada (Un solo Query)
        Ubicacion::insert($nuevasUbicaciones);

        return back()->with('success', count($nuevasUbicaciones) . ' ubicaciones generadas exitosamente.');
    }
}
