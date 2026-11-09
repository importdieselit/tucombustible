<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoMatrizController extends Controller
{
    // Muestra la vista estilo Excel
    public function index()
    {
        // Traemos todos los productos. Si tienes miles, esto requerirá paginación, 
        // pero asumo que tus combustibles son unos cuantos.
        $productos = Producto::orderBy('id', 'asc')->get();
        return view('productos.matriz', compact('productos'));
    }

    // Guarda los cambios masivos
    public function updateBulk(Request $request)
    {
        $request->validate([
            'productos' => 'required|array',
            'productos.*.nombre' => 'required|string',
            'productos.*.precio' => 'required|numeric',
            
            // Valida el resto si te apetece, pero te lo dejo básico para que no llores
        ]);

        // Transacción para que, si falla uno, no se guarde nada a medias
        DB::beginTransaction();
        try {
          
            foreach ($request->productos as $id => $data) {

                Producto::where('id', $id)->update($data);
            }
            DB::commit();
            return redirect()->back()->with('success', 'Matriz actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Algo rompiste: ' . $e->getMessage());
        }
    }
}