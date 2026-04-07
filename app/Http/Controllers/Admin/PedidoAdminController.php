<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PedidoService;
use App\Repositories\ClienteRepository;
use App\Repositories\DepositoRepository;
use Illuminate\Http\Request;
use Exception;

class PedidoAdminController extends Controller
{
    protected $pedidoService;
    protected $clienteRepo;
    protected $depositoRepo;

    public function __construct(
        PedidoService $pedidoService,
        ClienteRepository $clienteRepo,
        DepositoRepository $depositoRepo
    ) {
        $this->pedidoService = $pedidoService;
        $this->clienteRepo = $clienteRepo;
        $this->depositoRepo = $depositoRepo;
    }

    public function index()
    {
        $pedidos = $this->pedidoService->listarPedidosParaAdmin();
        return view('admin.pedidos.index', compact('pedidos'));
    }

    public function create()
    {
        // Usamos el repositorio en lugar de Cliente::where(...)
        $clientes = $this->clienteRepo->obtenerClientesActivos();
        return view('admin.pedidos.create_manual', compact('clientes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id'    => 'required|exists:clientes,id',
            'cantidad'      => 'required|numeric|min:1',
            'observaciones' => 'nullable|string',
        ]);

        try {
            // Simulamos el usuario basándonos en el cliente seleccionado vía Repositorio
            $cliente = $this->clienteRepo->find($validated['cliente_id']);
            $userPlaceholder = clone $cliente->usuario; // O como manejes la relación

            $this->pedidoService->registrarSolicitud($validated, $userPlaceholder);

            return redirect()->route('admin.pedidos.index')->with('success', 'Pedido manual registrado con éxito.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Equivalente a despachar/planificar. 
     * Asigna el tanque físico y cambia el estado.
     */
    public function planificar(Request $request, $id)
    {
        $validated = $request->validate([
            'deposito_id'    => 'required|exists:depositos,id',
            'vehiculo_id'    => 'required|exists:vehiculos,id', // Vimos que la app pide vehículo
            'fecha_despacho' => 'required|date|after_or_equal:today',
        ]);

        try {
            // Toda la lógica pesada (validar disponibilidad del depósito, restar saldos, cambiar estatus, FCM) 
            // se encapsula en este método del Service.
            $this->pedidoService->planificarYDespachar($id, $validated);

            return back()->with('success', 'Planificación guardada. El pedido ha sido despachado del tanque.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al planificar: ' . $e->getMessage());
        }
    }
}