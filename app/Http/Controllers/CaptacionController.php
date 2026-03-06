<?php

namespace App\Http\Controllers;

use App\Services\ClienteService;
use App\Repositories\ClienteRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaptacionController extends Controller
{
    protected $clienteService;
    protected $clienteRepo;

    public function __construct(ClienteService $clienteService, ClienteRepository $clienteRepo)
    {
        $this->clienteService = $clienteService;
        $this->clienteRepo = $clienteRepo;
    }

    /**
     * PASO 1: Formulario de Registro Público
     */
    public function showRegistrationForm()
    {
        return view('auth.register_cliente');
    }

    /**
     * GUARDAR PASO 1
     */
    public function store(Request $request)
    {
        $request->validate([
            'rif' => 'required|unique:clientes,rif',
            'razon_social' => 'required',
            'email' => 'required|email|unique:users,email',
            'contacto' => 'required',
        ]);

        try {
            $this->clienteService->registrarCliente($request->all());
            return redirect()->route('login')->with('success', '¡Registro inicial completado! Inicie sesión con su RIF.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * DASHBOARD ADMIN: Listado de Clientes en Registro
     */
    public function index(Request $request)
    {
        $filtros = [
            'search' => $request->get('search'),
            'status_filtro' => $request->get('status_filtro')
        ];

        $data = $this->clienteService->obtenerDashboardAdmin($filtros);

        // Cambiado a la carpeta 'admin.cliente' y variable 'clientes'
        return view('admin.cliente.index', [
            'clientes' => $data['clientes'],
            'stats'    => $data['stats']
        ]);
    }

    /**
     * EXPEDIENTE DETALLADO (Paso 3 al 9)
     */
    public function show($id)
    {
        $cliente = $this->clienteRepo->find($id);
        // Cambiado a la carpeta 'admin.cliente'
        return view('admin.cliente.show', compact('cliente'));
    }

    /**
     * PASO 2 -> 3: El Cliente finaliza su carga
     */
    public function finalizarCargaDocs()
    {
        try {
            $clienteId = Auth::user()->cliente->id;
            $this->clienteService->enviarExpedienteARevision($clienteId);

            return redirect()->route('dashboard')->with('success', '¡Expediente enviado! Estamos revisando sus documentos.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * AVANCE MANUAL: Acción del Admin
     */
    public function updateStep(Request $request, $id)
    {
        try {
            $this->clienteService->avanzarPaso($id, $request->paso, $request->all());
            return back()->with('success', 'Estatus de registro actualizado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}