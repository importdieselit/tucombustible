<?php

namespace App\Http\Controllers;

use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class PedidoController extends Controller
{
    protected $pedidoService;

    /**
     * Inyectamos el PedidoService para manejar la lógica de negocio.
     */
    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    /**
     * Muestra el historial de pedidos del cliente autenticado.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Usamos el método que ya tienes en el Service para filtrar por jerarquía
        $pedidos = $this->pedidoService->listarPedidosParaUsuario($user->cliente);

        return view('pedidos.index', compact('pedidos'));
    }

    /**
     * Muestra el formulario para crear una nueva solicitud de combustible.
     */
    public function create()
    {
        $user = Auth::user();
        $cliente = $user->cliente;

        // Aquí podrías pasar los tipos de combustible disponibles si los tienes en una tabla
        // o simplemente cargar la vista del formulario.
        return view('pedidos.create', compact('cliente'));
    }

    /**
     * Procesa la solicitud y aplica las validaciones de GASCO.
     */
    public function store(Request $request)
    {
        // 1. Validación de los datos del formulario
        $validated = $request->validate([
            'cantidad' => 'required|numeric|min:100',
            'deposito_id' => 'required|exists:depositos,id', // Ajusta según tu tabla de combustibles/depósitos
            'observaciones' => 'nullable|string|max:500',
        ], [
            'cantidad.required' => 'Debe ingresar la cantidad de litros.',
            'cantidad.numeric'  => 'La cantidad debe ser un número válido.',
            'cantidad.min'      => 'La cantidad debe ser al menos 100 litros.',
            'deposito_id.required' => 'Debe seleccionar el tipo de combustible.',
        ]);

        try {
            $user = Auth::user();

            // 2. Llamamos al Service. 
            // Recuerda que este método ya hace:
            // - Validación de cupo GASCO (con herencia de mes).
            // - Creación del Pedido y Alertas (vía PedidoRepository).
            // - Descuento en tabla GASCO y tabla Clientes.
            $this->pedidoService->registrarSolicitud($validated, $user);

            return redirect()->route('pedidos.index')
                ->with('success', 'Solicitud de combustible enviada correctamente.');

        } catch (Exception $e) {
            // Si el cupo es insuficiente o no hay configuración, el Service lanza la excepción
            // y la atrapamos aquí para mostrarla en la vista.
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}