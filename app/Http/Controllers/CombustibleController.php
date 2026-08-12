<?php

namespace App\Http\Controllers;

use App\Services\CombustibleService;
use App\Models\Sedes;
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
            $sedeId = $request->filled('id_sede') ? $request->input('id_sede') : null;

            // Retorna todas las métricas incluyendo 'vehiculosPrecargados'
            $metricas = $this->combustibleService->obtenerMetricasDashboard($sedeId) ?? [];

            $sedes = Sedes::select('id', 'nombre')->get();

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