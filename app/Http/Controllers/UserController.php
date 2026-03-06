<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Verificación de permisos (Método en el Modelo User)
        if (!$user->canAccess('read', 51)) {
            abort(403, 'No tiene permisos para acceder a este módulo.');
        }

        $clienteId = $user->cliente_id;
        
        // 2. Obtención de datos mediante el Servicio
        $stats = $this->userService->obtenerDashboardData($clienteId);
        $usuarios = $this->userService->obtenerListaFiltrada($request->all(), $clienteId);

        return view('usuarios.index', array_merge($stats, ['usuarios' => $usuarios]));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.'
        ]);

        // Usamos el servicio para actualizar y apagar la bandera
        $this->userService->actualizarPasswordObligatorio(Auth::id(), $request->password);

        return redirect()->route('dashboard')->with('success', 'Contraseña actualizada correctamente. Bienvenido al sistema.');
    }

    /**
    * Muestra la vista de cambio de contraseña obligatorio (Paso 2)
    */
    public function showChangePassword()
    {
        // Verificamos si realmente debe cambiarla, por si llega aquí por error
        if (Auth::user()->must_change_password != 1) {
            return redirect()->route('dashboard');
        }

        return view('auth.passwords.change');
    }
}