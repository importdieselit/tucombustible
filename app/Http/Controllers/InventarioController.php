<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Almacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\InventarioSuministro;

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
            'id_almacen' => 'required|exists:almacenes,id',
            'codigo' => 'required|string|max:50|unique:inventario',
            'descripcion' => 'required|string|max:200'
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

            'id_almacen' => 'sometimes|required|exists:almacenes,id',
            'codigo' => 'sometimes|required|string|max:50|unique:inventario,codigo,' . $id,
            'descripcion' => 'sometimes|required|string|max:200',
            'existencia' => 'sometimes|required|numeric|min:0'
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


    public function requests()
    {
        // Se obtienen todas las solicitudes de insumos con las relaciones necesarias
        $solicitudes = InventarioSuministro::with('inventario', 'orden')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('inventario.requests', compact('solicitudes'));
    }

    /**
     * Aprueba una solicitud de insumo.
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        try {
            $solicitud = InventarioSuministro::findOrFail($id);
            if ($solicitud->estatus != 2) {
                Session::flash('error', 'Esta solicitud ya ha sido procesada.');
                return redirect()->back();
            }

            $solicitud->update(['estatus' => 3]);
            Session::flash('success', 'Solicitud aprobada correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al aprobar la solicitud: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Rechaza una solicitud de insumo.
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject($id)
    {
        try {
            $solicitud = InventarioSuministro::findOrFail($id);
            if ($solicitud->estatus == 1 || $solicitud->estatus == 5) {
                Session::flash('error', 'Esta solicitud ya ha sido despachada o rechazada.');
                return redirect()->back();
            }

            $solicitud->update(['estatus' => 5]);
            Session::flash('success', 'Solicitud rechazada correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al rechazar la solicitud: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Despacha un insumo y actualiza el inventario.
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dispatch($id)
    {
        $userId = Auth::id();
        DB::beginTransaction();
        try {
            $solicitud = InventarioSuministro::findOrFail($id);

            // Verificar que la solicitud esté aprobada
            if ($solicitud->estatus != 3) {
                Session::flash('error', 'Solo se pueden despachar insumos que han sido previamente aprobados.');
                return redirect()->back();
            }

            $inventario = Inventario::findOrFail($solicitud->id_inventario);

            // Verificar si hay suficiente stock
            if ($inventario->existencia < $solicitud->cantidad) {
                Session::flash('error', 'No hay suficiente stock para despachar este insumo.');
                $solicitud->update(['estatus' => 4]);
                return redirect()->back();
            }

            // Descontar del inventario y marcar como despachado
            $inventario->existencia -= $solicitud->cantidad;
            $inventario->save();

            $solicitud->update(['estatus' => 1]);
            
            if($inventario->existencia < $inventario->existencia_minima){
                // Aquí podrías agregar lógica para generar una alerta o notificación
                Session::flash('warning', 'El inventario ha caído por debajo del nivel mínimo.');
                $this->createAlert([
                    'id_usuario' => $userId, // ID del usuario responsable de la orden.
                    'id_rel' => $inventario->id, // ID de la item.
                    'observacion' => 'El inventario del insumo '.$inventario->codigo.'-' .$inventario->descripcion.' ha caído por debajo del nivel mínimo.',
                    'accion' => route('inventario.show', $inventario->id) , // Ruta para ver la orden.
                    'dias' => 0,
                ]);
            }
            DB::commit();
            Session::flash('success', 'Insumo despachado y inventario actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Session::flash('error', 'Error al despachar el insumo: ' . $e->getMessage());
        }

        return redirect()->back();
    }

}
