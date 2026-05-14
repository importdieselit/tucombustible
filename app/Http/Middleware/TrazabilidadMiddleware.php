<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\BitacoraSistema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class TrazabilidadMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (Auth::check() && $request->route()) {
            
            $routeAction = $request->route()->getActionName();
            $controller = 'N/A';
            $method = 'N/A';

            if (str_contains($routeAction, '@')) {
                $actionBase = class_basename($routeAction);
                $parts = explode('@', $actionBase);
                $controller = $parts[0];
                $method = $parts[1] ?? 'index';
            } else {
                // Es un Closure o una ruta especial
                $controller = class_basename($routeAction); 
                $method = 'Closure/Other';
            }

            // CAPTURA EL MENSAJE PERSONALIZADO SI EXISTE
            $customMsg = $request->attributes->get('bitacora_msg');

            $omitir = [
                'NotificacionController',
            ];

            // Verificamos omisión
            if (in_array($controller, $omitir) || in_array($controller.'@'.$method, $omitir)) {
                return $response;
            }

            // Si el request tiene un flag de omitir
            if ($request->has('skip_log')) return $response;

            // Registro en Bitácora
            BitacoraSistema::create([
                'id_usuario'         => Auth::id(),
                'tipo'               => 'CONTROLLER',
                'actividad'          => $controller,
                'metodo_accion'      => $method,
                'mensaje'            => $customMsg,
                'parametros_request' => json_encode($request->except(['password', '_token'])),
                'ip'                 => $request->ip(),
                'user_agent'         => $request->userAgent(),
            ]);
        }

        return $response;
    }
}