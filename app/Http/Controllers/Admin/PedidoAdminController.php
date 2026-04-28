<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\PedidoService;
use App\Services\LogisticaService;
use App\Repositories\ClienteRepository;
use App\Repositories\DepositoRepository;
use Illuminate\Http\Request;
use Exception;

class PedidoAdminController extends Controller
{
    protected $pedidoService;
    protected $clienteRepo;
    protected $depositoRepo;
    protected $LogisticaService;

    public function __construct(
        PedidoService $pedidoService,
        ClienteRepository $clienteRepo,
        DepositoRepository $depositoRepo,
        LogisticaService $LogisticaService
    ) {
        $this->pedidoService = $pedidoService;
        $this->clienteRepo = $clienteRepo;
        $this->depositoRepo = $depositoRepo;
        $this->LogisticaService = $LogisticaService;
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
            'vehiculo_id'    => 'required|exists:vehiculos,id', 
            'fecha_despacho' => 'required|date|after_or_equal:today',
        ]);

        try {
            // 1. Buscamos el pedido para armar el "item" de logística
            $pedido = Pedido::findOrFail($id);

            // 2. Construimos el array exacto que espera LogisticaService
            $dataLogistica = [
                'tipo_planificacion'   => 1, // 1 es Diesel
                'fecha_programada'     => $validated['fecha_despacho'],
                'es_transporte_propio' => '1', 
                'vehiculo_id'          => $validated['vehiculo_id'],
                'tipo_combustible_id'  => 1, 
                'items' => [
                    [
                        'cliente_id' => $pedido->cliente_id,
                        'pedido_id'  => $pedido->id,
                        'litros'     => $pedido->cantidad_solicitada,
                    ]
                ]
            ];

            // 3. Ahora sí llamamos al servicio correctamente
            $this->LogisticaService->procesarPlanificacion($dataLogistica);

            return back()->with('success', 'Planificación guardada. El pedido ha sido procesado.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al planificar: ' . $e->getMessage());
        }
    }
}