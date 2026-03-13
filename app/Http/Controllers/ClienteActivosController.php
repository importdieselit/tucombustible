<?php

namespace App\Http\Controllers;

use App\Services\ClienteService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ClienteActivosController extends Controller 
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Guarda las placas y chóferes validados.
     * Perfil 3 (Cliente): Seguridad forzada sobre su propio ID.
     * Perfiles 1 y 2 (Super/Admin): Gestión libre por cliente_id.
     */
    public function asignarActivos(Request $request)
    {
        $clienteId = null;

        try {
            $user = Auth::user();

            // IDENTIFICACIÓN SEGÚN JERARQUÍA DEL SISTEMA
            if ($user->id_perfil == 3) {
                // Es CLIENTE: Solo puede afectar a su propia cuenta
                $clienteId = $user->cliente_id;
            } elseif ($user->id_perfil == 1 || $user->id_perfil == 2) {
                // Es SUPERUSER (1) o ADMIN (2): Procesa el ID recibido del formulario
                $clienteId = $request->cliente_id;
            }

            if (!$clienteId) {
                if ($request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => 'Identificación de cliente no encontrada.'], 400);
                }
                return back()->with('error', 'Identificación de cliente no encontrada.');
            }

            // Registro de patrimonio a través del servicio existente
            $this->clienteService->registrarActivosAprobados(
                $clienteId,
                $request->input('placas', []),
                $request->input('choferes', [])
            );

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Patrimonio de activos actualizado correctamente.'
                ], 201);
            }

            return back()->with('success', '¡Activos actualizados con éxito!');

        } catch (Exception $e) {
            Log::error("Error en gestión de activos - Perfil " . Auth::user()->id_perfil . " - Cliente ID " . ($clienteId ?? 'N/A') . ": " . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Error al procesar los datos en el servidor.'
                ], 500);
            }

            return back()->with('error', 'Hubo un problema al guardar los activos. Reintente.');
        }
    }
}