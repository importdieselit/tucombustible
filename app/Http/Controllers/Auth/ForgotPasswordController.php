<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForgotPasswordController extends Controller
{
    /**
     * Muestra el formulario para ingresar el email.
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email_direct');
    }

    /**
     * Valida el email y redirige al cambio obligatorio.
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'El correo electrónico no se encuentra registrado.'
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Activamos la bandera de cambio obligatorio
        $user->must_change_password = 1;
        $user->save();

        // 2. Iniciamos sesión automáticamente
        Auth::login($user);

        // 3. Redirigimos al flujo que ya tienes. 
        // El middleware CheckPasswordChange hará el resto.
        return redirect()->route('password.change')
            ->with('info', 'Usuario validado. Por favor, asigne su nueva contraseña.');
    }
}