<?php

namespace App\Observers;
use App\Models\Orden;
use App\Notifications\OrdenTrabajoCreada;

class OrdenObserver
{
    public function created(Orden $orden)
    {
        $vehiculo = $orden->vehiculo;
        if (!$vehiculo) {
            return;
        }

        $vehiculo->estatus = in_array($orden->tipo, ['Mantenimiento', 'Preventivo']) ? 3 : 5;

        // Ejemplo: Notificar al Gerente de Operaciones
        $gerente = \App\Models\User::whereIn('id_perfil', [1,2,6,12] )->get();
        
        if ($gerente) {
            foreach($gerente as $user){
                $user->notify(new OrdenTrabajoCreada($orden));
            }
        }
    }

    public function updated(Orden $orden)
    {
        // Aquí podrías agregar lógica para cuando una orden es actualizada, si es necesario
    }
}