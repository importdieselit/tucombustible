<?php

namespace App\View\Composers;

use App\Models\Alerta;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertsComposer
{
    /**
     * Adjunta las alertas no leídas a la vista de la cabecera.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // 1. Verificar si hay un usuario autenticado
        if (Auth::check()) {
            $userId = Auth::id();

            // 2. Obtener el conteo de alertas no leídas (estatus = 0)
            $unreadAlertsCount = Alerta::where('id_usuario', $userId)
                                       ->where('estatus', 0)
                                       ->count();

            // 3. Obtener las primeras 5 alertas para el dropdown (opcional, pero útil)
            $unreadAlerts = Alerta::where('id_usuario', $userId)
                                  ->where('estatus', 0)
                                  ->orderBy('fecha', 'desc')
                                  ->take(5)
                                  ->get();
            
            // Pasar los datos a la vista
            $view->with('unreadAlertsCount', $unreadAlertsCount)
                 ->with('unreadAlerts', $unreadAlerts);

        } else {
            // Si no hay usuario, el conteo es cero
            $view->with('unreadAlertsCount', 0)
                 ->with('unreadAlerts', collect());
        }
    }
}
