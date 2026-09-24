<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ParametroService;

class ParametroController extends Controller
{
    protected $parametroService;

    public function __construct(ParametroService $parametroService)
    {
        $this->parametroService = $parametroService;
    }

    /**
     * Muestra la vista de configuración agrupada dinámicamente por Pestañas.
     */
    public function index()
    {
        $grupos = $this->parametroService->obtenerAgrupadosParaVista();
        
        return view('admin.parametros.index', compact('grupos'));
    }

    /**
     * Procesa la actualización delegando la lógica al Servicio.
     */
    public function update(Request $request)
    {
        $inputs = $request->input('parametros', []);

        // Delega la actualización masiva y la limpieza de caché al servicio
        $this->parametroService->actualizarMasivo($inputs);

        return redirect()->route('parametros.index')
            ->with('success', 'Configuración actualizada con éxito.');
    }
}