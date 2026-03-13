<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * El servicio se carga vía Service Container dentro del método index.
     */
    public function index()
    {
        $user = Auth::user();

        // 1. DASHBOARD PRINCIPAL (Admin / SuperUser)
        if (in_array($user->id_perfil, [1, 2])) {
            return redirect()->route('admin.dashboard_principal_provisional');
        }

        // 2. DASHBOARDS DEL MÓDULO CLIENTES (Perfil 3)
        $dashboardService = app(DashboardService::class);
        $data = $dashboardService->getDashboardData($user);

        // Caso: Cliente que aún no completa el registro
        if ($data['perfil'] === 'cliente_proceso') {
            return view('cliente.en_proceso', $data);
        }

        // Caso: Cliente Activo (Sea Padre o Sucursal)
        // Usamos la misma vista unificada 'cliente.index'
        if (in_array($data['perfil'], ['cliente_padre', 'cliente_sucursal'])) {
            return view('cliente.index', $data);
        }

        return abort(403, 'Perfil de usuario no reconocido o expediente no vinculado.');
    }
}