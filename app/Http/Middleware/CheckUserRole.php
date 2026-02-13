<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * 
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roleId): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->status_usuario === 'prospecto') {
            if (!$request->routeIs('captacion.completar')) {
                return redirect()->route('captacion.completar');
            }
            return $next($request);
        }

        if (Auth::user()->id_perfil == 1) {
            return $next($request);
        }

        if (Auth::user()->id_perfil != $roleId) {
            return abort(403, 'Acceso no autorizado.');
        }

        return $next($request);
    }
}