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

        if (Auth::check()) {
            // Obtenemos el nombre del controlador y la función
            $routeAction = Route::currentRouteAction(); // Ej: "App\Http\Controllers\ViajesController@store"
            
            list($controller, $method) = explode('@', class_basename($routeAction));
            // CAPTURA EL MENSAJE PERSONALIZADO SI EXISTE
            $customMsg = $request->attributes->get('bitacora_msg');

            BitacoraSistema::create([
                'id_usuario'       => Auth::id(),
                'tipo'             => 'CONTROLLER',
                'actividad'        => $controller,
                'metodo_accion'    => $method,
                'mensaje'          => $customMsg,
                'parametros_request' => json_encode($request->except(['password', '_token'])),
                'ip'               => $request->ip(),
                'user_agent'       => $request->userAgent(),
            ]);
        }

        return $response;
    }
}