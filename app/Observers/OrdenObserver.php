<?php

namespace App\Observers;
use App\Models\Orden;
use App\Notifications\OrdenTrabajoCreada;

class OrdenObserver
{
    public function created(Orden $orden)
    {
        // Ejemplo: Notificar al Gerente de Operaciones
        $gerente = \App\Models\User::whereIn('perfil_id', [1,2,7,8,18] )->first();
        if ($gerente) {
            $gerente->notify(new OrdenTrabajoCreada($orden));
        }
    }
}