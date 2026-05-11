<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\PedidoService;
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

        // 1. DASHBOARD PRINCIPAL (Admin / SuperUser)
        // Redirigimos antes de tocar cualquier Servicio
        if (in_array($user->id_perfil, [1, 2,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18])) {
            return redirect()->route('dashboard.admin');
        }

        // -------------------------------------------------------
        // LÓGICA DEL CLIENTE (perfil 3)
        // -------------------------------------------------------
        
        if ($user->id_perfil == 3) {
            return redirect()->route('portal.clientes.index');
        }

        // 1. Capturamos el ID de sucursal si viene en la URL
        $sucursalId = $request->query('sucursal_id');

        // 2. Pasamos el ID al servicio para el "Modo Espejo"
        $data = $this->dashboardService->getDashboardData($user, $sucursalId);

        // 3. Cargamos los pedidos usando el cliente correcto (Padre o Sucursal)
        // Validamos que exista 'cliente' en el array por si el perfil es 'cliente_sin_vincular'
        if (isset($data['cliente'])) {
            $data['pedidos'] = app(PedidoService::class)
                                ->listarPedidosParaUsuario($data['cliente']);
        }

        // 4. Definimos la variable que hacía explotar la vista
        $data['viendoSucursal'] = $request->filled('sucursal_id');

        // Retornamos la vista correspondiente
        return match ($data['perfil']) {
            'cliente_en_registro' => view('cliente.en_proceso', $data),
            'cliente_rechazado'   => view('cliente.rechazado', $data),
            'cliente_inactivo'    => view('cliente.inactivo', $data),
            'cliente_padre',
            'cliente_sucursal'    => view('cliente.index', $data),
            default               => abort(403, 'Perfil de usuario no reconocido o expediente no vinculado.'),
        };
    }

    public function adminPrincipal()
    {
        // Ahora sí existe el método en el Service
        $stats = $this->dashboardService->getAdminStats();
        
        // Cargamos la vista profesional que está en resources/views/dashboard/admin_principal.blade.php
        return view('dashboard.admin_principal', compact('stats'));
    }
}