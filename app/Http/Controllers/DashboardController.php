<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $user = Auth::user();

        // Superusuario y Administrador — redirigen al panel provisional
        if (in_array($user->id_perfil, [1, 2])) {
            return redirect()->route('admin.dashboard_principal_provisional');
        }

        // Cliente (perfil 3)
        $data = $this->dashboardService->getDashboardData($user);

        return match ($data['perfil']) {
            'cliente_en_registro' => view('cliente.en_proceso', $data),
            'cliente_rechazado'   => view('cliente.rechazado', $data),
            'cliente_inactivo'    => view('cliente.inactivo', $data),
            'cliente_padre',
            'cliente_sucursal'    => view('cliente.index', $data),
            default               => abort(403, 'Perfil de usuario no reconocido o expediente no vinculado.'),
        };
    }
}