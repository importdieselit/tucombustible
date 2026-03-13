<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClienteService;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function index(Request $request)
    {
        $filtros = $request->only(['search', 'status_filtro']);
        $data = $this->clienteService->obtenerDashboardAdmin($filtros);

        // CONSULTA DE PEDIDOS PENDIENTES PARA EL MODAL DE NOTIFICACIONES
        $pedidosPendientes = Pedido::with('cliente')
            ->where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->get();

        // CONSULTA DE ALERTAS PARA EL HEADER
        $unreadAlerts = Alerta::where('id_usuario', Auth::id())
            ->where('estatus', 0)
            ->orderBy('fecha', 'desc')
            ->get();
        
        $unreadAlertsCount = $unreadAlerts->count();

        return view('admin.cliente.index', [
            'clientes'          => $data['clientes'],
            'stats'             => $data['stats'],
            'pasos'             => Cliente::PASOS_REGISTRO,
            'filtros'           => $filtros,
            'pedidosPendientes' => $pedidosPendientes,
            'unreadAlerts'      => $unreadAlerts,
            'unreadAlertsCount' => $unreadAlertsCount
        ]);
    }

    public function show($id)
    {
        $cliente = $this->clienteService->obtenerExpediente($id);

        // CONSULTA DE ACTIVOS ASOCIADOS AL CLIENTE (PLACAS Y CHOFERES)
        $placas = DB::table('placas_vehiculos')
            ->where('cliente_id', $id)
            ->where('activo', 1)
            ->get();

        $choferes = DB::table('choferes_clientes')
            ->where('cliente_id', $id)
            ->where('activo', 1)
            ->get();

        return view('admin.cliente.show', compact('cliente', 'placas', 'choferes'));
    }

    /**
     * Actualiza el paso del registro y gestiona la asignación de cupos de forma exclusiva.
     */
    public function updatePaso(Request $request, $id)
    {
        try {
            $request->validate([
                'paso'                => 'required|integer',
                'cupo'                => 'nullable|numeric|min:0',
                'tipo_combustible_id' => 'nullable|in:1,2' // 1: Diesel, 2: MGO
            ]);

            $nuevoPaso = $request->input('paso');
            $cliente = Cliente::findOrFail($id);

            DB::transaction(function () use ($request, $cliente, $nuevoPaso) {
                
                $extraData = [];

                // Si se está aprobando el cliente (Paso 10) y se envió un cupo
                if ($nuevoPaso == 10 && $request->filled('cupo') && $request->filled('tipo_combustible_id')) {
                    
                    $litros = $request->input('cupo');
                    $tipoId = $request->input('tipo_combustible_id');

                    // Sincronización con el modelo de datos según DDL y lógica de cupos
                    $extraData['cupo'] = $litros;
                    $extraData['disponible'] = $litros;
                    $extraData['tipo_combustible_id'] = $tipoId;
                }

                // Ejecutamos el avance de paso a través del servicio
                $this->clienteService->avanzarPaso($cliente->id, $nuevoPaso, $extraData);
            });
            
            Session::flash('success', '¡Expediente y asignación de cupo actualizados con éxito!');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error("Error en Admin/ClienteController@updatePaso: " . $e->getMessage());
            return Redirect::back()->with('error', 'Error al procesar el cambio de etapa: ' . $e->getMessage());
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