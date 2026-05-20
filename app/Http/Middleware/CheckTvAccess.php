<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckTvAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. PRIORIDAD ABSOLUTA: Si la petición trae el Token de la TV (Caso APK)
        $tvToken = $request->header('X-TV-Token');

        if ($tvToken) {
            if ($tvToken === env('TV_DASHBOARD_TOKEN')) {
                return $next($request);
            }
            
            Log::warning("Token de TV inválido ".$tvToken." !== ".env('TV_DASHBOARD_TOKEN')." intentando acceder desde la IP: " . $request->ip());
            abort(403, 'Acceso denegado. Token de pantalla inválido. '.$tvToken);
        }

        // 2. CASO ALTERNATIVO: Acceso vía Navegador Web (Caso Tú auditando)
        // Verificamos si hay sesión activa Y si NO es un cliente (Excluimos Rol 3 según tus rutas)
        if (Auth::check()) {
            $user = Auth::user();
            
            // Reemplaza 'role_id' por el campo exacto que uses para validar tus roles
            if (isset($user->id_perfil) && $user->id_perfil !== 3) {
                return $next($request);
            }
        }

        // 3. BLOQUEO: Si no es la APK con token válido ni un administrador logueado
        Log::warning("Intento de acceso no autorizado a la Sala de Control desde la IP: " . $request->ip());
        abort(403, 'Acceso denegado. Este endpoint es exclusivo para pantallas autorizadas o personal operativo.');
    }
}