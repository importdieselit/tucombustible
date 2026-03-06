<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPasswordChange
{
    /**
     * Maneja la petición entrante.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Si tiene la bandera activa y NO está ya en la ruta de cambio o saliendo
            if ($user->must_change_password == 1 && 
                !$request->is('password/change*', 'logout', 'api/*')) {
                
                return redirect()->route('password.change')
                    ->with('info', 'Por seguridad, debe actualizar su contraseña inicial (su RIF) para poder continuar con el registro.');
            }
        }

        return $next($request);
    }
}