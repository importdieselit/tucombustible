<?php

namespace App\Observers;
use App\Models\Orden;
use App\Notifications\OrdenTrabajoCreada;

class OrdenObserver
{
    public function created(Orden $orden)
    {
        // Ejemplo: Notificar al Gerente de Operaciones
        $gerente = \App\Models\User::whereIn('id_perfil', [1,2,6,12] )->get();
        
        if ($gerente) {
            foreach($gerente as $user){
                $user->notify(new OrdenTrabajoCreada($orden));
            }
        }
    }
}