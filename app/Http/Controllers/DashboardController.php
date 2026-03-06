<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $data = $this->dashboardService->getDashboardData(Auth::user());

        // 1. DASHBOARD PRINCIPAL (Admin / SuperUser)
        if ($data['perfil'] === 'admin_sistema') {
            // Esta es la vista general del sistema (stats de ventas, inventario, etc.)
            return view('dashboard.admin_principal', $data);
        }

        // 2. DASHBOARDS DEL MÓDULO CLIENTES (Perfil 3)
        
        // A. Cliente en Proceso de Registro (Paso 1 al 9)
        // Se le muestra una vista de "En espera de aprobación"
        if ($data['perfil'] === 'cliente_proceso') {
            return view('cliente.en_proceso', $data);
        }

        // B. Cliente Padre (Activo, paso 10)
        if ($data['perfil'] === 'cliente_padre') {
            // Usamos la carpeta 'cliente' que creamos para el portal
            return view('cliente.index', $data);
        }

        // C. Cliente Sucursal (Activo, paso 10)
        if ($data['perfil'] === 'cliente_sucursal') {
            // Podrías usar la misma vista o una específica
            return view('cliente.index_sucursal', $data);
        }

        return abort(403, 'Perfil de usuario no reconocido o sin acceso al dashboard.');
    }
}