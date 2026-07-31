<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo; 
use App\Models\MantenimientoItem;
use Illuminate\Http\Request;

class PlanMayorController extends Controller
{
    public function index()
    {
        $unidades = Vehiculo::with('planMayorItems')->orderBy('id', 'asc')->get();
        $items = MantenimientoItem::all();
        $itemsAgrupados = $items->groupBy('categoria');

        $totalInversionGlobal = 0;
        foreach($unidades as $u) {
            $totalInversionGlobal += $u->planMayorItems->sum('costo_promedio');
        }

        return view('plan_mayor.index', compact('unidades', 'items', 'itemsAgrupados', 'totalInversionGlobal'));
    }

    public function toggleItem(Request $request)
    {
        $request->validate([
            'unidad_id' => 'required|integer',
            'item_id' => 'required|integer'
        ]);

        $unidad = Vehiculo::findOrFail($request->unidad_id);
        $resultado = $unidad->planMayorItems()->toggle($request->item_id);
        
        $nuevoCostoUnidad = $unidad->planMayorItems()->sum('costo_promedio');
        $cantTrabajosUnidad = $unidad->planMayorItems()->count();

        $totalInversionGlobal = 0;
        $todasLasUnidades = Vehiculo::with('planMayorItems')->get();
        foreach($todasLasUnidades as $u) {
            $totalInversionGlobal += $u->planMayorItems->sum('costo_promedio');
        }

        return response()->json([
            'success' => true,
            'status' => count($resultado['attached']) ? 'attached' : 'detached',
            'costo_unidad' => $nuevoCostoUnidad,
            'cant_trabajos' => $cantTrabajosUnidad,
            'total_global' => $totalInversionGlobal
        ]);
    }

    // NUEVO: Guardar o Registrar un nuevo ítem en el baremo
    public function storeBaremo(Request $request)
    {
        $request->validate([
            'categoria' => 'required|string|in:OVERHAUL,REPARACION GENERAL',
            'nombre' => 'required|string|max:255',
            'costo_promedio' => 'required|numeric|min:0'
        ]);

        MantenimientoItem::create([
            'categoria' => $request->categoria,
            'nombre' => mb_strtoupper($request->nombre, 'UTF-8'),
            'costo_promedio' => $request->costo_promedio
        ]);

        return back()->with('success', 'Nuevo ítem registrado en el Plan Mayor.');
    }

    // NUEVO: Actualizar costo o nombre de un ítem existente
    public function updateBaremo(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:mantenimiento_items,id',
            'categoria' => 'required|string|in:OVERHAUL,REPARACION GENERAL',
            'nombre' => 'required|string|max:255',
            'costo_promedio' => 'required|numeric|min:0'
        ]);

        $item = MantenimientoItem::findOrFail($request->item_id);
        $item->update([
            'categoria' => $request->categoria,
            'nombre' => mb_strtoupper($request->nombre, 'UTF-8'),
            'costo_promedio' => $request->costo_promedio
        ]);

        return back()->with('success', 'Parámetro de baremo actualizado.');
    }

    // NUEVO: Eliminar ítem del catálogo
    public function destroyBaremo($id)
    {
        $item = MantenimientoItem::findOrFail($id);
        // Al usar onDelete('cascade') en la migración, se limpian automáticamente las relaciones del cuadro
        $item->delete(); 

        return back()->with('success', 'Ítem eliminado correctamente del sistema.');
    }
}