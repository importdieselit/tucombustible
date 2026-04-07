<?php

namespace App\Http\Controllers;

use App\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log};

class ClienteActivosController extends Controller
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Registra una placa para un cliente.
     * Solo accesible por Superusuario (1) y Administrador (2).
     */
    public function registrarPlaca(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'placa'      => 'required|string|max:8',
        ]);

        try {
            $this->clienteService->registrarPlaca(
                $request->cliente_id,
                $request->placa
            );

            if ($request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Placa registrada correctamente.'], 201);
            }

            return back()->with('success', 'Placa registrada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar placa: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Registra un chofer para un cliente.
     * Solo accesible por Superusuario (1) y Administrador (2).
     */
    public function registrarChofer(Request $request)
    {
        $request->validate([
            'cliente_id'      => 'required|exists:clientes,id',
            'nombre_completo' => 'required|string|max:255',
            'cedula'          => 'required|string|max:15',
        ]);

        try {
            $this->clienteService->registrarChofer(
                $request->cliente_id,
                $request->nombre_completo,
                $request->cedula
            );

            if ($request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Chofer registrado correctamente.'], 201);
            }

            return back()->with('success', 'Chofer registrado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar chofer: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}