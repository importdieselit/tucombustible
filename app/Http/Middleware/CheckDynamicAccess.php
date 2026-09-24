<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckDynamicAccess
{
    /**
     * Intercepta la petición para validar permisos dinámicos por módulo y acción.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $action Acción a evaluar: read, create, update, delete
     * @param int|null $explicitModuleId ID explícito si se pasa desde el web.php
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $action = 'read', ?int $explicitModuleId = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Si el usuario es Super Admin (ej. ID de perfil 1/2 o flag), bypass directo para no romper soporte
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // 1. Resolver el ID del módulo
        $moduleId = $explicitModuleId ?? $this->resolveModuleIdFromRoute($request);

        // 2. Si la ruta NO pertenece a un módulo en la BD, se permite el paso por defecto
        if (!$moduleId) {
            return $next($request);
        }

        // 3. Evaluar permisos mediante el método canAccess del modelo User
        if (method_exists($user, 'canAccess') && !$user->canAccess($action, $moduleId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tiene permisos para realizar esta acción.'], 403);
            }
            abort(403, 'No tiene permisos para realizar esta acción en este módulo.');
        }

        return $next($request);
    }

    /**
     * Resuelve el ID del módulo basado en el nombre de la ruta o prefijos.
     */
    private function resolveModuleIdFromRoute(Request $request): ?int
    {
        $routeName = $request->route() ? $request->route()->getName() : null;
        if (!$routeName) {
            return null;
        }

        // A. Búsqueda exacta por coincidencia de ruta registrada en la BD
        $modulo = DB::table('modulos')->where('ruta', $routeName)->first(['id']);
        if ($modulo) {
            return (int) $modulo->id;
        }

        // B. Búsqueda por prefijo (ej: si la ruta es 'vehiculos.updatev', busca 'vehiculos.%')
        $prefix = explode('.', $routeName)[0];
        $moduloPadre = DB::table('modulos')
            ->where('ruta', 'LIKE', $prefix . '.%')
            ->orWhere('ruta', $prefix)
            ->first(['id']);

        return $moduloPadre ? (int) $moduloPadre->id : null;
    }
}