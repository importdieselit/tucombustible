<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait AuthorizesModuleAccess
{
    /**
     * Valida si el usuario actual tiene acceso a una acción específica del módulo.
     */
    protected function authorizeModule(string $action, int $moduleId): void
    {
        $user = Auth::user();

        if (!$user || (method_exists($user, 'canAccess') && !$user->canAccess($action, $moduleId))) {
            abort(403, "Acción [{$action}] no autorizada para el módulo ID: {$moduleId}.");
        }
    }
}