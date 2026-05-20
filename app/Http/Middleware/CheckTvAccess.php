<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTvAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Capturamos el token desde las cabeceras HTTP creadas por la APK
        $tvToken = $request->header('X-TV-Token');

        // Validamos si coincide con el de nuestro archivo .env
        if (!$tvToken || $tvToken !== env('TV_DASHBOARD_TOKEN')) {
            Log::warning("Intento de acceso no autorizado a la Sala de Control desde la IP: " . $request->ip());
            abort(403, 'Acceso denegado. Este endpoint es exclusivo para pantallas autorizadas.');
        }

        return $next($request);
    }
}