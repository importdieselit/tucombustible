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
        // 1. Validación de campos según el Modal
        $validated = $request->validate([
            'cliente_id'          => 'required|exists:clientes,id',
            'cantidad_solicitada' => 'required|numeric|min:1',
            'fecha_entrega'       => 'required|date|after_or_equal:today',
            'direccion_despacho'  => 'required|string|max:500',
            'observaciones'       => 'nullable|string|max:500',
        ]);

        try {
            $user = Auth::user();
            $clienteUsuario = $user->cliente; // El cliente asociado al usuario logueado

            // 2. SEGURIDAD: Validar que el cliente_id solicitado pertenezca al usuario
            // Si el cliente_id no es él mismo Y no es una sucursal suya, bloqueamos.
            if ($validated['cliente_id'] != $clienteUsuario->id) {
                $esSucursalPropia = $clienteUsuario->sucursales()
                                    ->where('id', $validated['cliente_id'])
                                    ->exists();
                
                if (!$esSucursalPropia) {
                    throw new Exception("No tiene autorización para realizar pedidos a esta cuenta.");
                }
            }

            // 3. Registrar mediante el Service
            $this->pedidoService->registrarSolicitud($validated, $user);

            // 4. Redirección (Usando el nombre de ruta de tu web.php)
            return redirect()->route('portal.clientes.index')
                ->with('success', '¡Solicitud enviada con éxito! Se ha descontado de su saldo disponible.');

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
