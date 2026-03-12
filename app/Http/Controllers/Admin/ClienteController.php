<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClienteService;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function index(Request $request)
    {
        // Capturamos search y el nuevo status_filtro
        $filtros = $request->only(['search', 'status_filtro']);
        $data = $this->clienteService->obtenerDashboardAdmin($filtros);

        return view('admin.cliente.index', [
            'clientes' => $data['clientes'],
            'stats'    => $data['stats'],
            'pasos'    => Cliente::PASOS_REGISTRO,
            'filtros'  => $filtros // Enviamos de vuelta los filtros para mantenerlos en la vista
        ]);
    }

    public function show($id)
    {
        $cliente = $this->clienteService->obtenerExpediente($id);
        return view('admin.cliente.show', compact('cliente'));
    }

    public function updatePaso(Request $request, $id)
    {
        try {
            $nuevoPaso = $request->input('paso');
            $this->clienteService->avanzarPaso($id, $nuevoPaso, $request->all());
            
            Session::flash('success', '¡Estatus actualizado con éxito!');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error("Error en Admin/ClienteController@updatePaso: " . $e->getMessage());
            return Redirect::back()->with('error', 'Error al procesar el cambio de etapa.');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $this->clienteService->cambiarEstatus($id);
            Session::flash('success', 'Estatus operativo actualizado.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'No se pudo cambiar el estatus.');
        }
    }
}