<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SucursalController extends Controller
{
    /**
     * Activa o desactiva una sucursal vinculada.
     * Solo permitido si el cliente autenticado es el 'parent' de la sucursal.
     */
    public function toggleStatus($id)
    {
        $clientePadre = Auth::user()->cliente;

        if (!$clientePadre) {
            return back()->with('error', 'No tiene un expediente de cliente asociado.');
        }

        // Verificamos que la sucursal exista y pertenezca a este padre (parent)
        $sucursal = Cliente::where('id', $id)
            ->where('parent', $clientePadre->id)
            ->first();

        if (!$sucursal) {
            return back()->with('error', 'Sucursal no encontrada o no pertenece a su cuenta.');
        }

        // Cambio de estatus (tinyint 1/0 en DDL)
        $sucursal->status = ($sucursal->status == 1) ? 0 : 1;
        $sucursal->save();

        $mensaje = $sucursal->status == 1 ? 'activada' : 'desactivada';
        
        return back()->with('success', "La sucursal {$sucursal->nombre} ha sido {$mensaje} correctamente.");
    }

    /**
     * Muestra el expediente de una sucursal específica para que el padre supervise.
     */
    public function showExpediente($id)
    {
        $clientePadre = Auth::user()->cliente;

        // Seguridad: Solo ver si el parent ID coincide
        $sucursal = Cliente::with(['documentos', 'ciudad', 'estado'])
            ->where('id', $id)
            ->where('parent', $clientePadre->id)
            ->first();

        if (!$sucursal) {
            return redirect()->route('portal.clientes.index')
                ->with('error', 'No tiene permisos para ver este expediente.');
        }

        return view('cliente.sucursales.show', compact('sucursal'));
    }
}