<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Http\Requests\ClienteRequest; // Importamos el FormRequest
use Illuminate\Http\Request; // Usamos el Request estándar para compatibilidad con BaseController
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Controlador para gestionar los clientes.
 */
class ClienteController extends BaseController
{
   

    /**
     * Almacena un nuevo cliente en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validamos los datos manualmente usando las reglas de ClienteRequest
        $rules = (new ClienteRequest())->rules();
        $validatedData = $request->validate($rules);

        try {
            Cliente::create($validatedData);
            Session::flash('success', 'Cliente creado exitosamente.');
            return Redirect::route('clientes.list');
        } catch (\Exception $e) {
            Log::error('Error al crear cliente: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al crear el cliente.');
            return Redirect::back()->withInput();
        }
    }

    /**
     * Actualiza un cliente existente en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Validamos los datos manualmente usando las reglas de ClienteRequest
        $rules = (new ClienteRequest())->rules($id); // Pasamos el ID para la validación de unicidad
        $validatedData = $request->validate($rules);

        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->update($validatedData);
            Session::flash('success', 'Cliente actualizado exitosamente.');
            return Redirect::route('clientes.list');
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Cliente no encontrado.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al actualizar el cliente.');
            return Redirect::back()->withInput();
        }
    }

    /**
     * Elimina un cliente de la base de datos.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->delete();
            Session::flash('success', 'Cliente eliminado exitosamente.');
            return Redirect::route('clientes.index');
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Cliente no encontrado.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al eliminar cliente: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al eliminar el cliente.');
            return Redirect::back();
        }
    }
}
