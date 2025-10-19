<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SolicitudEliminacion;

class DataDeletionController extends Controller
{
    /**
     * Muestra la vista con la política de eliminación y el formulario.
     */
    public function showRequestForm()
    {
        // Esto asume que tienes una vista llamada 'data-deletion' en resources/views
        return view('data-deletion');
    }

    /**
     * Endpoint para recibir la solicitud de eliminación de datos del usuario final.
     * (El método 'submitRequest' es el mismo que definimos previamente)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitRequest(Request $request)
    {
        // 1. Validación de los datos mínimos requeridos
        $request->validate([
            'identifier' => 'required|string|max:255',
            'type' => 'required|in:telegram,email',
            'reason' => 'nullable|string|max:1000',
        ]);

        // 2. Registro de la solicitud pendiente
        try {
            SolicitudEliminacion::create([
                'user_identifier' => $request->identifier,
                'user_type' => $request->type,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            // Redirige a la misma página con un mensaje de éxito
            return back()->with('success', '✅ Su solicitud ha sido registrada y será procesada por nuestro equipo administrativo en 72 horas.');

        } catch (\Exception $e) {
            return back()->with('error', '❌ Error interno: No pudimos registrar su solicitud. Por favor, intente más tarde.');
        }
    }
}