<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Viaje;

class SearchController extends Controller
{
    public function handle(Request $request)
    {
        $field = $request->get('field'); // Qué estamos buscando (cliente, buque, chuto...)
        $term = $request->get('term');   // Lo que el usuario escribe

        switch ($field) {
            case 'cliente_nombre':
                return Cliente::where('nombre', 'LIKE', "%$term%")
                    ->limit(5)
                    ->get(['id', 'nombre as value']);

            case 'buque':
                // Buscamos buques usados en viajes anteriores para sugerir
                return Viaje::where('buque', 'LIKE', "%$term%")
                    ->distinct()
                    ->limit(5)
                    ->get(['buque as value']);

            case 'chuto_placa':
                return Vehiculo::where('tipo', 'TRACTOR')
                    ->where('placa', 'LIKE', "%$term%")
                    ->limit(5)
                    ->get(['id', 'placa as value', 'flota']);

            case 'cisterna_placa':
                return Vehiculo::where('tipo', 'CISTERNA')
                    ->where('placa', 'LIKE', "%$term%")
                    ->limit(5)
                    ->get(['id', 'placa as value']);

            default:
                return response()->json([]);
        }
    }
}