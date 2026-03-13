<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PedidoService;
use App\Models\Pedido;
use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PedidoAdminController extends Controller
{
    protected $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    /**
     * Muestra la lista de todos los pedidos para el Administrador
     */
    public function index()
    {
        $pedidos = $this->pedidoService->listarPedidosParaAdmin();
        return view('admin.pedidos.index', compact('pedidos'));
    }

    /**
     * Actualiza el paso/estado del pedido
     */
    public function updateEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|string'
        ]);

        try {
            // 1. Actualizar el pedido mediante el servicio
            $this->pedidoService->actualizarEstadoPedido($id, $request->estado);
            $pedido = Pedido::findOrFail($id);

            // 2. Gestionar fechas automáticas según el DDL
            if ($request->estado == 'aprobado') {
                $pedido->update(['fecha_aprobacion' => now()]);
            } elseif ($request->estado == 'completado') {
                $pedido->update(['fecha_completado' => now()]);
            }

            /**
             * 3. LÓGICA DE ALERTAS (CORREGIDA):
             * Usamos 'id_rel' para identificar el pedido y 'estatus' para marcar como leída.
             */
            $estadosFinales = ['completado', 'rechazado', 'cancelado'];

            if (in_array($request->estado, $estadosFinales)) {
                // Según tu modelo Alerta: la columna es id_rel y el estado es estatus
                Alerta::where('id_rel', $id)
                    ->orWhere('observacion', 'LIKE', "%Pedido #{$id}%")
                    ->update(['estatus' => 1]); // 1 para indicar que ya se atendió/leyó
            }

            return back()->with('success', 'Pedido #' . $id . ' actualizado a: ' . ucfirst($request->estado));
            
        } catch (\Exception $e) {
            Log::error("Error en PedidoAdminController@updateEstado: " . $e->getMessage());
            return back()->with('error', 'Error al procesar el cambio: ' . $e->getMessage());
        }
    }
}