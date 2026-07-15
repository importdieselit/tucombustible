<?php

namespace App\Http\Controllers;

use App\Models\TransaccionCombustible;
use App\Models\TipoCombustible;
use App\Models\Deposito;
use Illuminate\Http\Request;

class TransaccionCombustibleController extends Controller
{
    public function index(Request $request)
    {
        // 1. Iniciamos la consulta cargando de golpe las relaciones necesarias (Eager Loading)
        $query = TransaccionCombustible::with([
            'sede', 
            'tipoCombustible', 
            'deposito', 
            'cliente', 
            'user'
        ]);

        // 2. Filtro por Rango de Fechas
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('created_at', [
                $request->fecha_desde . ' 00:00:00', 
                $request->fecha_hasta . ' 23:59:59'
            ]);
        }

        // 3. Filtro por Tipo de Movimiento (compra, despacho, despacho_prepagado, etc.)
        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        // 4. Filtro por Depósito (Tanque físico)
        if ($request->filled('deposito_id')) {
            $query->where('deposito_id', $request->deposito_id);
        }

        // 5. Filtro por Tipo de Combustible
        if ($request->filled('tipo_combustible_id')) {
            $query->where('tipo_combustible_id', $request->tipo_combustible_id);
        }

        // 6. Obtenemos los registros ordenados por el más reciente y paginados
        $transacciones = $query->latest('id')->paginate(25);

        // 7. Data de apoyo para llenar los selects de los filtros en la vista
        $tiposCombustible = TipoCombustible::all();
        $depositos = Deposito::all();

        return view('combustibles.transacciones_combustibles.index', compact(
            'transacciones', 
            'tiposCombustible', 
            'depositos'
        ));
    }

    /**
     * Muestra el detalle de una transacción específica para auditoría.
     */
    public function show($id)
    {
        $transaccion = TransaccionCombustible::with([
            'sede', 
            'tipoCombustible', 
            'deposito', 
            'cliente', 
            'user', 
            'viaje'
        ])->findOrFail($id);

        return view('combustibles.transacciones_combustibles.show', compact('transaccion'));
    }
}