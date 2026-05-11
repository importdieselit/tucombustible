<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\PlanMayorItem;
use App\Models\Modelo;

class PlanMayorController extends Controller
{
    public function index($filtro = 'all')
    {
        // 1. Manejo del filtro (Scope o condicional)
        $query = Vehiculo::with(['ordenesTrabajo' => function($q) {
                $q->where('estatus', 'ABIERTA');
            }, 'planMayorItems']) // Eager loading de las relaciones para evitar N+1 queries
            ->where('estatus', '<', 5)
            ->where('disp', 's');

        if ($filtro === 'r') {
            // Aplicar tu lógica de filtro '+'
        } elseif ($filtro === 'o') {
            // Aplicar tu lógica de filtro '-'
        }

        // Ordenamos por longitud de flota y luego alfabético (como en CI)
        $vehiculos = $query->orderByRaw('LENGTH(flota) ASC, flota ASC')->get();

        // 2. Obtener Modelos únicos de los vehículos activos
        $modelos = Modelo::whereHas('vehiculos', function($q) {
            $q->where('estatus', '<', 5)->where('disp', 's');
        })->get();
        
        // Agregar el "TRAILER" manual como en tu código original
        $modelos->push((object)['id_modelo' => 0, 'modelo' => 'TRAILER']);

        // 3. Obtener Ítems Mayores y Menores con sus costos cargados
        $mayores = PlanMayorItem::with('costos')->where('tipo', '+')->get();
        $menores = PlanMayorItem::with('costos')->where('tipo', '-')->get();

        // 4. Procesar valores / fallas (Lógica que tenías en el controlador de CI)
        $valores = ['OTRAS FALLAS' => []];
        // ... (Aquí adaptaremos el bucle de las descripciones de la orden de trabajo)

        return view('reportes.mayor.index', compact(
            'vehiculos', 'modelos', 'mayores', 'menores', 'valores', 'filtro'
        ));
    }
}