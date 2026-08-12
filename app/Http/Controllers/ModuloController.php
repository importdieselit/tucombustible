<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

class ModuloController extends Controller
{
    /**
     * Muestra la jerarquía del menú en una sola vista.
     */
    public function index()
    {
        // Trae los módulos raíz (padres) con sus respectivos hijos ordenados
        $modulos = Modulo::with(['hijos' => function($q) {
            $q->orderBy('orden', 'asc');
        }])
        ->where('id_padre', 0)
        ->orWhereNull('id_padre')
        ->orderBy('orden', 'asc')
        ->get();

        // Para poblar el select de módulos padre en el modal
        $padresSelect = Modulo::where('id_padre', 0)
            ->orWhereNull('id_padre')
            ->orderBy('modulo', 'asc')
            ->get();

        return view('admin.modulos-adm', compact('modulos', 'padresSelect'));
    }

    /**
     * Guarda un nuevo ítem del menú.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'modulo'      => 'required|string|max:255',
            'ruta'        => 'nullable|string|max:255',
            'icono'       => 'nullable|string|max:255',
            'orden'       => 'required|integer|min:0',
            'id_padre'    => 'required|integer',
            'descripcion' => 'nullable|string|max:255',
            'visible'     => 'nullable|boolean',
            'url_directa' => 'nullable|boolean',
        ]);

        $validated['visible'] = $request->has('visible') ? 1 : 0;
        $validated['url_directa'] = $request->has('url_directa') ? 1 : 0;

        Modulo::create($validated);

        Session::flash('success', 'Ítem de menú creado correctamente.');
        return Redirect::route('admin.modulos-adm');
    }

    /**
     * Actualiza un ítem existente.
     */
    public function update(Request $request, $id)
    {
        $modulo = Modulo::findOrFail($id);

        $validated = $request->validate([
            'modulo'      => 'required|string|max:255',
            'ruta'        => 'nullable|string|max:255',
            'icono'       => 'nullable|string|max:255',
            'orden'       => 'required|integer|min:0',
            'id_padre'    => 'required|integer',
            'descripcion' => 'nullable|string|max:255',
            'visible'     => 'nullable|boolean',
            'url_directa' => 'nullable|boolean',
        ]);

        // Evitar que un módulo sea su propio padre
        if ((int)$validated['id_padre'] === (int)$id) {
            Session::flash('error', 'Un módulo no puede ser su propio padre.');
            return Redirect::route('admin.modulos-adm');
        }

        $validated['visible'] = $request->has('visible') ? 1 : 0;
        $validated['url_directa'] = $request->has('url_directa') ? 1 : 0;

        $modulo->update($validated);

        Session::flash('success', 'Ítem de menú actualizado correctamente.');
        return Redirect::route('admin.modulos-adm');
    }

    /**
     * Elimina un ítem si no tiene hijos.
     */
    public function destroy($id)
    {
        $modulo = Modulo::findOrFail($id);

        if ($modulo->hijos()->count() > 0) {
            Session::flash('error', 'No se puede eliminar un módulo que contiene sub-menús.');
            return Redirect::route('admin.modulos-adm');
        }

        $modulo->delete();

        Session::flash('success', 'Ítem eliminado correctamente.');
        return Redirect::route('admin.modulos-adm');
    }

    /**
     * AJAX: Cambia estado de Visibilidad en el Menú.
     */
    public function toggleVisible($id)
    {
        try {
            $modulo = Modulo::findOrFail($id);
            $modulo->visible = !$modulo->visible;
            $modulo->save();
            return response()->json(['success' => true, 'visible' => $modulo->visible]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * AJAX: Cambia estado de URL Directa.
     */
    public function toggleUrlDirecta($id)
    {
        try {
            $modulo = Modulo::findOrFail($id);
            $modulo->url_directa = !$modulo->url_directa;
            $modulo->save();
            return response()->json(['success' => true, 'url_directa' => $modulo->url_directa]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}