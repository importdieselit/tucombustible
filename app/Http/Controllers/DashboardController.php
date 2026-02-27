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

        // 1. Cliente en Proceso de Captación
        if ($data['perfil'] === 'cliente_proceso') {
            return view('dashboard.cliente_proceso', $data);
        }

        // 2. Cliente Aprobado (Panel Operativo)
        if ($data['perfil'] === 'cliente') {
            return view('dashboard.cliente_aprobado', $data);
        }

        // 3. Administrador/Gerencia (Panel Principal)
        return view('dashboard.admin', $data);
    }
}