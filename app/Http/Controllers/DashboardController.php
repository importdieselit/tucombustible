<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Quitamos el constructor que pedía el DashboardService.
     * Esto evita que Laravel intente cargar todos los repositorios al inicio.
     */

    public function index()
    {
        $user = Auth::user();

        // 1. DASHBOARD PRINCIPAL (Admin / SuperUser)
        // Redirigimos antes de tocar cualquier Servicio
        if (in_array($user->id_perfil, [1, 2])) {
            return redirect()->route('admin.dashboard_principal_provisional');
        }

        /**
         * 2. DASHBOARDS DEL MÓDULO CLIENTES (Perfil 3)
         * Cargamos el servicio manualmente solo aquí. 
         * Si falla por falta de repositorios, solo le fallará al Cliente.
         */
        $dashboardService = app(DashboardService::class);
        $data = $dashboardService->getDashboardData($user);

        if ($data['perfil'] === 'cliente_proceso') {
            return view('cliente.en_proceso', $data);
        }

        if ($data['perfil'] === 'cliente_padre') {
            return view('cliente.index', $data);
        }

        if ($data['perfil'] === 'cliente_sucursal') {
            return view('cliente.index_sucursal', $data);
        }

        return abort(403, 'Perfil de usuario no reconocido.');
    }
}