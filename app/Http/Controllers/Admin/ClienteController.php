<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClienteService;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Redirect, Session, Log};

class ClienteController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Nivel 1: Listado Global de Clientes (Dashboard Admin)
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['search', 'status_filtro']);
        $data = $this->clienteService->obtenerDashboardAdmin($filtros);

        return view('admin.cliente.index', [
            'clientes' => $data['clientes'],
            'stats'    => $data['stats'],
            'pasos'    => Cliente::PASOS_REGISTRO
        ]);
    }

    /**
     * Nivel 2: Expediente Detallado (Revisión de Pasos)
     */
    public function show($id)
    {
        $cliente = $this->clienteService->obtenerExpediente($id);
        return view('admin.cliente.show', compact('cliente'));
    }

    /**
     * Acción: Avanzar el flujo de los 10 pasos
     */
    public function updatePaso(Request $request, $id)
    {
        try {
            $nuevoPaso = $request->input('nuevo_paso');
            // Pasamos todos los datos (incluyendo fecha de inspección si es paso 8)
            $this->clienteService->avanzarPaso($id, $nuevoPaso, $request->all());
            
            Session::flash('success', '¡Paso actualizado con éxito!');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error("Error en Admin/ClienteController@updatePaso: " . $e->getMessage());
            return Redirect::back()->with('error', 'Error al procesar el cambio de etapa.');
        }
    }

    /**
     * Acción: Activar/Desactivar cuenta de cliente (Paso 10)
     */
    public function toggleStatus($id)
    {
        $this->clienteService->cambiarEstatus($id);
        return back()->with('success', 'Estatus del cliente actualizado.');
    }
}