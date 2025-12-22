<?php

namespace App\Http\Controllers\Apis;

use App\Models\Buques;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Viaje;
use App\Models\Guia;

class SearchController extends Controller
{
    public function handle(Request $request)
    {
        $field = $request->get('field'); // Qué estamos buscando (cliente, buque, chuto...)
        $term = $request->get('term');   // Lo que el usuario escribe

        switch ($field) {
            case 'cliente':
                return Cliente::where('nombre', 'LIKE', "%$term%")
                ->orWhere('alias', 'LIKE', "%$term%")
                ->limit(5)
                ->get(['id', 'nombre', 'alias', 'rif', 'direccion']) // Traemos las columnas limpias
                ->map(function($cliente) {
                    return [
                        'id'        => $cliente->id,
                        // Si hay alias, lo concatena; si no, solo pone el nombre
                        'label'     => $cliente->alias 
                                    ? "{$cliente->nombre} [{$cliente->alias}]" 
                                    : $cliente->nombre,
                        'value'     => $cliente->nombre, // Lo que se escribe en el input
                        'rif'       => $cliente->rif,
                        'direccion' => $cliente->direccion,
                    ];
                });

            case 'buque':
                return Buques::where('nombre', 'LIKE', "%$term%")
                    ->where('cliente_id', $request->cliente_id) // Opcional: filtrar por cliente actual
                    
                    ->get()
                    ->map(function($b) {
                        return [
                            'label'   => "{$b->nombre} [IMO: {$b->imo}]",
                            'nombre'   => $b->nombre,
                            'imo'     => $b->imo,
                            'bandera' => $b->bandera,
                            'id'      => $b->id
                        ];
                    });

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