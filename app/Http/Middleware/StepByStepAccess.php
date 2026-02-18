<?php 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StepByStepAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // PASO 1: Cambio de contraseña obligatorio
        if ($user->must_change_password) {
            if (!$request->is('password/change*') && !$request->is('logout')) {
                return redirect()->route('password.change')
                                 ->with('info', 'Debe cambiar su clave inicial para continuar.');
            }
            return $next($request);
        }

        // PASO 2: Si es prospecto, solo puede estar en la carga de documentos
        if ($user->status_usuario === 'prospecto') {
            // Rutas permitidas para el prospecto
            $allowedRoutes = [
                'captacion.completar', 
                'captacion.upload-doc', 
                'logout'
            ];

            if (!$request->routeIs($allowedRoutes)) {
                return redirect()->route('captacion.completar');
            }
        }

        return $next($request);
    }
}