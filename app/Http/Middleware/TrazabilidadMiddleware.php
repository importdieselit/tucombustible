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
            if(!is_null($routeAction)){
                    list($controller, $method) = explode('@', class_basename($routeAction));
                    // CAPTURA EL MENSAJE PERSONALIZADO SI EXISTE
                    $customMsg = $request->attributes->get('bitacora_msg');

                    $omitir = [
                        //'GpsController',             // Omite todo el controlador
                        //'NotificacionController',    // Omite todo el controlador
                        //'ViajesController@getUpdate' // Omite solo una función específica de refresco visual
                    ];

                    if (in_array($controller, $omitir) || in_array($controller.'@'.$method, $omitir)) {
                        return $response;
                    }

                    // Si el request tiene un flag de omitir (para llamadas AJAX pesadas)
                    if ($request->has('skip_log')) return $response;

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
        }

        return $response;
    }
}