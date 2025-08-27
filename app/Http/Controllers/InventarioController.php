<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Almacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventarioController extends BaseController
{
    /**
     * Muestra el dashboard principal del inventario.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $totalItems = Inventario::count();
        $totalCantidad = Inventario::sum('existencia');
        $valorTotal = Inventario::sum(DB::raw('existencia * costo'));

        return view('inventario.index', compact('totalItems', 'totalCantidad', 'valorTotal'));
    }


    /**
     * Muestra el formulario para crear un nuevo item.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $item = new Inventario();
        $almacenes = Almacen::all();
        // Nota: Los datos de Marca, Modelo, Condicion, etc. deben ser cargados aquí
        return view('inventario.form', compact('item', 'almacenes'));
    }

    /**
     * Almacena un nuevo item en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_usuario' => 'required|exists:users,id',
            'prioridad' => 'required|integer|in:1,2',
            'estatus' => 'required|integer',
            'id_almacen' => 'required|exists:inventario_almacenes,id',
            'codigo' => 'required|string|max:50|unique:inventario',
            'codigo_fabricante' => 'nullable|string|max:50',
            'fabricante' => 'required|string|max:100',
            'referencia' => 'required|string|max:50',
            'descripcion' => 'required|string|max:200',
            'existencia' => 'required|numeric|min:0',
            'costo' => 'required|numeric|min:0',
            'costo_div' => 'required|numeric|min:0',
            'existencia_minima' => 'required|integer|min:0',
            'marca' => 'required|integer',
            'modelo' => 'required|integer',
            'fecha_in' => 'required|string|max:10', // Considerar 'date' para validación
            'observacion' => 'nullable|string|max:200',
            'avatar' => 'nullable|string',
            'factura_referencia' => 'nullable|string|max:100',
            'grupo' => 'required|string|max:100',
            'codigo_interno' => 'required|string|max:100',
            'clasificacion' => 'required|integer',
            'incorporacion' => 'required|integer',
            'existencia_maxima' => 'nullable|integer|min:0',
            'condicion' => 'required|integer',
            'fecha_conteo' => 'nullable|date',
            'serialized' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->route('inventario.create')
                ->withErrors($validator)
                ->withInput();
        }

        // Se asigna el usuario autenticado (asumiendo que hay un sistema de autenticación)
        $request->merge(['id_usuario' => Auth::id()]);
        Inventario::create($request->all());

        return redirect()->route('inventario.list')
            ->with('success', 'Item de inventario creado exitosamente.');
    }

    /**
     * Muestra un item específico.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $item = Inventario::with(['almacen', 'usuario'])->find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }
        return view('inventario.show', compact('item'));
    }

    /**
     * Muestra el formulario para editar un item existente.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $item = Inventario::find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }
        $almacenes = Almacen::all();
        // Nota: Los datos de Marca, Modelo, Condicion, etc. deben ser cargados aquí
        return view('inventario.form', compact('item', 'almacenes'));
    }

    /**
     * Actualiza un item existente en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $item = Inventario::find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }

        $validator = Validator::make($request->all(), [
            'id_usuario' => 'sometimes|required|exists:users,id',
            'prioridad' => 'sometimes|required|integer|in:1,2',
            'estatus' => 'sometimes|required|integer',
            'id_almacen' => 'sometimes|required|exists:inventario_almacenes,id',
            'codigo' => 'sometimes|required|string|max:50|unique:inventario,codigo,' . $id,
            'codigo_fabricante' => 'nullable|string|max:50',
            'fabricante' => 'sometimes|required|string|max:100',
            'referencia' => 'sometimes|required|string|max:50',
            'descripcion' => 'sometimes|required|string|max:200',
            'existencia' => 'sometimes|required|numeric|min:0',
            'costo' => 'sometimes|required|numeric|min:0',
            'costo_div' => 'sometimes|required|numeric|min:0',
            'existencia_minima' => 'sometimes|required|integer|min:0',
            'marca' => 'sometimes|required|integer',
            'modelo' => 'sometimes|required|integer',
            'salida_motivo' => 'nullable|string',
            'salida_fecha' => 'nullable|date',
            'salida_id_usuario' => 'nullable|exists:users,id',
            'fecha_in' => 'sometimes|required|string|max:10',
            'observacion' => 'nullable|string|max:200',
            'avatar' => 'nullable|string',
            'factura_referencia' => 'nullable|string|max:100',
            'grupo' => 'sometimes|required|string|max:100',
            'codigo_interno' => 'sometimes|required|string|max:100',
            'clasificacion' => 'sometimes|required|integer',
            'incorporacion' => 'sometimes|required|integer',
            'existencia_maxima' => 'nullable|integer|min:0',
            'condicion' => 'sometimes|required|integer',
            'fecha_conteo' => 'nullable|date',
            'serialized' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->route('inventario.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $item->update($request->all());

        return redirect()->route('inventario.list')
            ->with('success', 'Item de inventario actualizado exitosamente.');
    }

    /**
     * Elimina un item de la base de datos.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $item = Inventario::find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }

        $item->delete();

        return redirect()->route('inventario.list')
            ->with('success', 'Item de inventario eliminado exitosamente.');
    }
    
    public function entry()
    {
    }

    public function adjustment()
    {
    }
}
