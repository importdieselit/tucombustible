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
        // 1. DASHBOARD PRINCIPAL (Admin / SuperUser)
        // Redirigimos antes de tocar cualquier Servicio

            // 2. LÓGICA DEL CLIENTE (Perfil 3)
            3 => redirect()->route('cliente.index'),

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
   
    public function adminPrincipal()
    {
        // Ahora sí existe el método en el Service
        $stats = $this->dashboardService->getAdminStats();
        
        // Cargamos la vista profesional que está en resources/views/dashboard/admin_principal.blade.php
        return view('dashboard.admin_principal', compact('stats'));
    }
}