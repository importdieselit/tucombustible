<?php

namespace App\Http\Controllers;

use App\Services\CombustibleService;
use App\Models\Sedes; // Ajustar a Sede::class si usas el singular estándar de Eloquent
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class CombustibleController extends Controller
{
    protected $combustibleService;

    public function __construct(CombustibleService $combustibleService)
    {
        $this->combustibleService = $combustibleService;
    }

    public function index(Request $request)
    {
        try {
            // Convertimos cadenas vacías a NULL para que el repositorio interprete "Todas las sedes"
            $sedeId = $request->filled('id_sede') ? $request->input('id_sede') : null;

            // 1. Obtener métricas estructuradas desde el servicio / repositorio
            $metricas = $this->combustibleService->obtenerMetricasDashboard($sedeId) ?? [];

            // 2. Carga optimizada de sedes para el select del filtro
            $sedes = Sedes::select('id', 'nombre')->get();

            // 3. Fusionamos los arreglos y retornamos la vista
            return view('combustibles.dashboard', array_merge($metricas, [
                'sedeId' => $sedeId,
                'sedes'  => $sedes,
            ]));

        } catch (Exception $e) {
            Log::error('Error en CombustibleController@index: ' . $e->getMessage(), [
                'sede_id' => $request->input('id_sede'),
                'trace'   => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al cargar las métricas del dashboard. Por favor, intente de nuevo.');
        }
    }
}