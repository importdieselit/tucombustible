<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    protected $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    public function index()
    {
        $pedidos = $this->pedidoService->listarPedidosParaUsuario(Auth::user());
        return view('cliente.pedidos.index', compact('pedidos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_combustible_id' => 'required|integer',
            'cantidad'            => 'required|numeric|min:1',
        ]);

        try {
            $data = [
                'tipo_combustible_id' => $request->tipo_combustible_id,
                'cantidad'            => $request->cantidad, 
                'estado'              => 'pendiente',
            ];

            $this->pedidoService->registrarSolicitud($data, Auth::user());
        
            return redirect()->route('portal.clientes.index')
                ->with('success', 'Solicitud enviada exitosamente.');
        } catch (\Exception $e) {
            Log::error("Error en PedidoController@store: " . $e->getMessage());
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }
}