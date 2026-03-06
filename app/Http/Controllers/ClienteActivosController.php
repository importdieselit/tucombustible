<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminActivosRequest;
use App\Services\ClienteService;
use Exception;
use Illuminate\Support\Facades\Log;

class ClienteActivosController extends BaseController // Usamos BaseController por consistencia con tus otros controladores
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Guarda las placas y chóferes que el Administrador ha validado.
     */
    public function asignarActivos(AdminActivosRequest $request)
    {
        try {
            // El FormRequest ya validó perfil 1 o 2, y los formatos de datos
            $this->clienteService->registrarActivosAprobados(
                $request->cliente_id,
                $request->input('placas', []),
                $request->input('choferes', [])
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Patrimonio de activos del cliente actualizado correctamente.'
            ], 201);

        } catch (Exception $e) {
            Log::error("Error en carga de activos - Cliente ID {$request->cliente_id}: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al procesar los activos.'
            ], 500);
        }
    }
}