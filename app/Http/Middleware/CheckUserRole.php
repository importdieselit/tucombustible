<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roleId): Response
    {
        // Verifica si hay un usuario autenticado y si su perfil_id no coincide con el rol requerido.
        if (!Auth::check() || Auth::user()->perfil_id != $roleId) {
            // Si el usuario no tiene el rol adecuado, puedes abortar con un error 403
            // o redirigirlo a una página de inicio.
            return abort(403, 'Acceso no autorizado.');
            // return redirect()->route('home'); // Alternativa: redirigir a otra ruta
        }

        return $next($request);
    }
}