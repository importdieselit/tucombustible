<?php

namespace App\Http\Controllers;

use App\Models\TransaccionCombustible;
use App\Models\TipoCombustible;
use App\Models\Deposito;
use App\Models\Sedes;
use Illuminate\Http\Request;

class TransaccionCombustibleController extends Controller
{
    public function index(Request $request)
    {
        // Carga ansiosa (Eager Loading) limpia sin relaciones inexistentes
        $query = TransaccionCombustible::with([
            'sede', 
            'tipoCombustible', 
            'deposito', 
            'cliente', // <-- Esta relación trae tanto clientes como aliados comerciales
            'user',
        ]);

        // Filtro por Rango de Fechas
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('created_at', [
                $request->fecha_desde . ' 00:00:00', 
                $request->fecha_hasta . ' 23:59:59'
            ]);
        }

        // Filtro por Tipo de Movimiento
        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        // Filtro por Sede
        if ($request->filled('sede_id')) {
            $query->where('sede_id', $request->sede_id);
        }

        // Filtro por Depósito
        if ($request->filled('deposito_id')) {
            $query->where('deposito_id', $request->deposito_id);
        }

        // Filtro por Tipo de Combustible
        if ($request->filled('tipo_combustible_id')) {
            $query->where('tipo_combustible_id', $request->tipo_combustible_id);
        }

        // 6. Obtener registros paginados
        $transacciones = $query->latest('id')->paginate(25);

        // 7. Data de apoyo para los selects
        $tiposCombustible = TipoCombustible::all();
        $depositos = Deposito::all();
        $sedes = Sedes::all();

        return view('combustibles.transacciones_combustibles.index', compact(
            'transacciones', 
            'tiposCombustible', 
            'depositos',
            'sedes'
        ));
    }

    public function show($id)
    {
        $transaccion = TransaccionCombustible::with([
            'sede', 
            'tipoCombustible', 
            'deposito', 
            'cliente', 
            'user', 
            'viaje',
        ])->findOrFail($id);

        return view('combustibles.transacciones_combustibles.show', compact('transaccion'));
    }
}