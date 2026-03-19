<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->canAccess('read', 51)) {
            abort(403, 'No tiene permisos para acceder a este módulo.');
        }

        $clienteId = $user->cliente_id;
        $stats     = $this->userService->obtenerDashboardData($clienteId);
        $usuarios  = $this->userService->obtenerListaFiltrada($request->all(), $clienteId);

        return view('usuarios.index', array_merge($stats, ['usuarios' => $usuarios]));
    }

}