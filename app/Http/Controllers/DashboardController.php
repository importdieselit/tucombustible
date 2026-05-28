<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $perfilId = (int) $user->id_perfil;

        // El controlador actúa como un policía de tránsito distribuyendo según el perfil_id
        return match ($perfilId) {
            // 1. LÓGICA DEL CHOFER (Perfil 4)
            4 => redirect()->route('inspecciones.index'), // Sustituye por tu ruta real de choferes
            19 =>  view('layouts.otros'), // Sustituye por tu ruta real de choferes

            // 2. LÓGICA DEL CLIENTE (Perfil 3)
            3 => $this->redirigirDashboardCliente($request, $user),

            // 3. DASHBOARD PRINCIPAL / ADMINISTRATIVOS (Se excluyó el 4 de esta lista)
            default => in_array($perfilId, [1, 2, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18])
                ? redirect()->route('vehiculos.index')
                : abort(403, 'Perfil de usuario no reconocido o sin privilegios de acceso.'),
        };
    }

    /**
     * Procesa la data operativa y retorna la vista correspondiente para el ecosistema de Clientes.
     * * @param Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\View\View|\Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function redirigirDashboardCliente(Request $request, $user)
    {
        // 1. Capturamos el ID de sucursal si viene en la URL
        $sucursalId = $request->query('sucursal_id');

        // 2. Pasamos el ID al servicio para el "Modo Espejo"
        $data = $this->dashboardService->getDashboardData($user, $sucursalId);

        // 3. Cargamos los pedidos usando el cliente correcto (Padre o Sucursal)
        if (isset($data['cliente'])) {
            $data['pedidos'] = app(\App\Services\PedidoService::class)
                                ->listarPedidosParaUsuario($data['cliente']);
        }

        // 4. Definimos la variable para la consistencia de la vista
        $data['viendoSucursal'] = $request->filled('sucursal_id');
        
        // Retornamos la vista final correspondiente según el sub-estado del cliente
        return match ($data['perfil']) {
            'cliente_en_registro' => view('cliente.en_proceso', $data),
            'cliente_rechazado'   => view('cliente.rechazado', $data),
            'cliente_inactivo'    => view('cliente.inactivo', $data),
            'cliente_padre',
            'cliente_sucursal'    => view('cliente.index', $data),
            default               => abort(403, 'Perfil de cliente no reconocido o expediente no vinculado.'),
        };
    }
}