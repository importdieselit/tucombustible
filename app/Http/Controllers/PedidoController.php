<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class PedidoController extends Controller
{

    protected $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    public function index()
    {
        // El service se encarga de buscar los pedidos (incluyendo sucursales si aplica)
        $pedidos = $this->pedidoService->listarPedidosParaUsuario(Auth::user()->cliente);
        return view('pedidos.index', compact('pedidos'));
    }

    public function create()
    {
        $cliente = Auth::user()->cliente;
        
        // Calculamos el disponible de GASCO para mostrarlo en la vista
        $cupoGasco = $cliente->cupoGascoActual(); 
        $disponibleGasco = $cupoGasco ? ($cupoGasco->litros_autorizados - $cupoGasco->litros_consumidos) : 0;

        return view('pedidos.create', compact('cliente', 'disponibleGasco'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cantidad' => 'required|numeric|min:1',
            'observaciones' => 'nullable|string|max:500',
            // cliente_id opcional por si el usuario padre pide para una sucursal
            'cliente_id' => 'nullable|exists:clientes,id', 
        ]);

        try {
            $this->pedidoService->registrarSolicitud($validated, Auth::user());

            // Ajusta el nombre de la ruta según tu web.php
            return redirect()->route('pedidos.index')
                ->with('success', 'Solicitud registrada. Espere la planificación de despacho.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function cancelar(Request $request, $id)
    {
        try {
            $this->pedidoService->cancelarPedido($id, Auth::user());
            return back()->with('success', 'Pedido cancelado exitosamente.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function calificar(Request $request, $id)
    {
        $validated = $request->validate([
            'calificacion' => 'required|integer|min:1|max:5',
            'comentario_calificacion' => 'nullable|string|max:500',
        ]);

        try {
            $this->pedidoService->calificarPedido($id, $validated, Auth::user());
            return back()->with('success', 'Gracias por calificar su pedido.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
