<?php

namespace App\Http\Controllers;

use App\Services\CombustibleService;
use Illuminate\Http\Request;
use Exception;

class CombustibleController extends Controller
{
    protected $combustibleService;

    public function __construct(CombustibleService $combustibleService)
    {
        $this->combustibleService = $combustibleService;
    }

    /**
     * Almacena o actualiza el cupo mensual de GASCO para un cliente
     */
    public function registrarCupoGasco(Request $request)
    {
        // 1. Validación básica de los datos de entrada
        $validated = $request->validate([
            'cliente_id'         => 'required|exists:clientes,id',
            'litros_autorizados' => 'required|numeric|min:100',
            'mes'                => 'nullable|integer|min:1|max:12',
            'anio'               => 'nullable|integer|min:2025',
        ], [
            'cliente_id.required'         => 'Debe seleccionar un cliente.',
            'cliente_id.exists'           => 'El cliente seleccionado no es válido.',
            'litros_autorizados.required' => 'Debe ingresar la cantidad de litros.',
            'litros_autorizados.min'      => 'La cantidad debe ser mayor a 0.',
        ]);

        // 2. Ejecución a través del Service
        try {
            $this->combustibleService->registrarCupoMensualGasco($validated);
            
            // Si todo sale bien, regresamos con un mensaje de éxito
            return back()->with('success', 'El cupo de GASCO ha sido actualizado correctamente para este mes.');
            
        } catch (Exception $e) {
            // Si el servicio lanza una excepción (ej: supera el cupo aprobado general),
            // la capturamos y mostramos el error sin que la aplicación colapse.
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}