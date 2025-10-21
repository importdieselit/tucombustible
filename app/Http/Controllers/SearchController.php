<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\Chofer; // O App\Models\User si los choferes son usuarios
use App\Models\Cliente; // Si manejas un modelo de Clientes

class SearchController extends Controller
{
    /**
     * Realiza la búsqueda en múltiples modelos y muestra los resultados.
     */
    public function globalSearch(Request $request)
    {
        $query = $request->input('query');
        $results = [];

        if (empty($query)) {
            // Si la consulta está vacía, simplemente redirige de vuelta.
            return redirect()->back();
        }
        $query = trim( str_replace('unidad', '' ,  strtolower($query)));
        // 1. Búsqueda de Vehículos por Placa o Serial
        $vehiculos = Vehiculo::where('placa', 'LIKE', "%{$query}%")
                             ->orWhere('flota', 'LIKE', "%{$query}%")
                             ->limit(10)
                             ->get();
        
        foreach ($vehiculos as $v) {
            $results[] = [
                'type' => 'Vehículo',
                'description' => "Placa: {$v->placa} / Flota: {$v->flota}",
                'details_link' => route('vehiculos.show', $v->id), // Asume una ruta de detalle de vehículo
                'icon' => 'truck',
            ];
        }
        
        // 2. Búsqueda de Personas (Choferes/Ayudantes)
        // Asumiendo que el modelo Chofer tiene una relación 'persona' que contiene el 'name'
        $choferes = Chofer::whereHas('persona', function ($q) use ($query) {
                                $q->where('nombre', 'LIKE', "%".strtoupper($query)."%");
                            })
                            ->orWhere('dni', 'LIKE', "%{$query}%")
                            ->limit(10)
                            ->get();

        foreach ($choferes as $c) {
            $results[] = [
                'type' => 'Chofer/Persona',
                'description' => "{$c->persona->name} (Cédula: {$c->cedula})",
                'details_link' => route('choferes.show', $c->id), // Asume una ruta de detalle de chofer
                'icon' => 'person',
            ];
        }

        // 3. Búsqueda de Clientes
        // Asumiendo un modelo Cliente con un campo 'nombre' o 'rif'
         if (class_exists(Cliente::class)) {
             $clientes = Cliente::where('nombre', 'LIKE', "%{$query}%")
                                ->orWhere('rif', 'LIKE', "%{$query}%")
                                ->limit(10)
                                ->get();
            
             foreach ($clientes as $cl) {
                 $results[] = [
                     'type' => 'Cliente',
                     'description' => "{$cl->nombre} (RIF: {$cl->rif})",
                     'details_link' => route('clientes.show', $cl->id), // Asume una ruta de detalle de cliente
                     'icon' => 'people',
                 ];
             }
         }


        return view('search.search_results', [
            'results' => $results,
            'query' => $query,
        ]);
    }
}
