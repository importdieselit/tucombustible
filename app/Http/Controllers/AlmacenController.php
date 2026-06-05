<?php
namespace App\Http\Controllers;

use App\Models\Almacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlmacenController extends BaseController
{
    /**
     * Muestra una lista de todos los almacenes.
     * Retorna una vista de Blade.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $almacenes = Almacen::all();
        return view('almacen.index', compact('almacenes'));
    }


    /**
     * Almacena un nuevo almacén en la base de datos.
     * Redirige al usuario.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:almacenes',
            'direccion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('almacenes.create')
                ->withErrors($validator)
                ->withInput();
        }

        Almacen::create($request->all());

        return redirect()->route('almacenes.list')
            ->with('success', 'Almacén creado exitosamente.');
    }

    // /**
    //  * Muestra un almacén específico.
    //  * Retorna una vista de Blade.
    //  * @param int $id
    //  * @return \Illuminate\View\View
    //  */
    // public function show($id)
    // {
    //     $almacen = Almacen::find($id);
    //     if (!$almacen) {
    //         abort(404, 'Almacén no encontrado.');
    //     }
    //     return view('almacen.show', compact('almacen'));
    // }


    /**
     * Actualiza un almacén existente en la base de datos.
     * Redirige al usuario.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $almacen = Almacen::find($id);
        if (!$almacen) {
            abort(404, 'Almacén no encontrado.');
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255|unique:almacenes,nombre,' . $id,
            'direccion' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('almacenes.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $almacen->update($request->all());

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén actualizado exitosamente.');
    }

}