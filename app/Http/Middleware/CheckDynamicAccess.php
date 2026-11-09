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
     */
    public function handle(Request $request, Closure $next, ?string $explicitAction = null, ?int $explicitModuleId = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // El Super Admin pasa como rey.
        if ($user->id_perfil === 1 || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return $next($request);
        }

        // 1. Resolver el ID del módulo (Dinámico o Explícito)
        $moduleId = $explicitModuleId ?? $this->resolveModuleIdFromRoute($request);

        // Si la ruta no está mapeada en ningún módulo, asumimos que es de acceso libre para autenticados
        if (!$moduleId) {
            return $next($request);
        }

        // 2. Resolver la acción (read, create, update, delete)
        $action = $explicitAction ?? $this->resolveActionFromRoute($request);

        // 3. Evaluar permisos usando TU función canAccess optimizada
        if (method_exists($user, 'canAccess') && !$user->canAccess($action, $moduleId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes permisos para realizar esta acción ('.$action.').'], 403);
            }
            abort(403, 'No tienes permisos para realizar esta acción ('.$action.') en este módulo.');
        }

        return $next($request);
    }

    /**
     * Resuelve el ID del módulo buscando en la BD.
     */
    private function resolveModuleIdFromRoute(Request $request): ?int
    {
        $routeName = $request->route() ? $request->route()->getName() : null;
        if (!$routeName) {
            return null;
        }

        // A. Búsqueda exacta
        $modulo = DB::table('modulos')->where('ruta', $routeName)->first(['id']);
        if ($modulo) {
            return (int) $modulo->id;
        }

        // B. Búsqueda por prefijo (vehiculos.index -> vehiculos)
        $prefix = explode('.', $routeName)[0];
        $moduloPadre = DB::table('modulos')
            ->where(function($query) use ($prefix) {
                $query->where('ruta', 'LIKE', $prefix . '.%')
                      ->orWhere('ruta', $prefix);
            })
            ->first(['id']);

        return $moduloPadre ? (int) $moduloPadre->id : null;
    }

    /**
     * Resuelve la acción basándose en el método HTTP y el nombre de la ruta.
     * Esto te salva de que un usuario con solo lectura te borre la base de datos.
     */
    private function resolveActionFromRoute(Request $request): string
    {
        $routeName = $request->route() ? $request->route()->getName() : '';
        
        // Inferir por la terminación del nombre de la ruta
        if (str_ends_with($routeName, '.create') || str_ends_with($routeName, '.store') || str_ends_with($routeName, '.guardar')) {
            return 'create';
        }
        if (str_ends_with($routeName, '.edit') || str_ends_with($routeName, '.update') || str_ends_with($routeName, '.actualizar')) {
            return 'update';
        }
        if (str_ends_with($routeName, '.destroy') || str_ends_with($routeName, '.eliminar')) {
            return 'delete';
        }

        // Fallback al método HTTP si el nombre de la ruta es raro
        return match ($request->getMethod()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'read',
        };
    }
}