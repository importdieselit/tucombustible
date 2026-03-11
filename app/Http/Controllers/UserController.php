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

        if (!$user->canAccess('read', 51)) {
            abort(403, 'No tiene permisos para acceder a este módulo.');
        }

        $clienteId = $user->cliente_id;
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

        $user = Auth::user();

        // 1. Actualizamos la contraseña y removemos el flag de cambio obligatorio
        $this->userService->actualizarPasswordObligatorio($user->id, $request->password);

        // 2. AUTOMATIZACIÓN PASO 1 -> 2
        // Si el usuario es un cliente y está en el paso inicial de registro, lo movemos a carga de documentos
        if ($user->id_perfil == 3 && $user->cliente && $user->cliente->registro_paso == 1) {
            $user->cliente->update(['registro_paso' => 2]);
        }

        return redirect()->route('dashboard')->with('success', 'Contraseña actualizada correctamente. Acceso concedido.');
    }

    public function showChangePassword()
    {
        if (Auth::user()->must_change_password != 1) {
            return redirect()->route('dashboard');
        }

        return view('auth.passwords.change');
    }
}