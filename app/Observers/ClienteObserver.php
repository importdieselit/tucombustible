<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\Alerta;
use App\Services\FcmNotificationService;

class ClienteObserver
{
    /**
     * Manejar el evento 'updated' del modelo Cliente.
     */
    public function updated(Cliente $cliente)
    {
        if ($cliente->isDirty('disponible')) {
            $oldDisponible = $cliente->getOriginal('disponible');
            $newDisponible = $cliente->disponible;

            // Si el disponible ha bajado y cruza el umbral del 10%
            $porcentajeActual = ($newDisponible / $cliente->cupo) * 100;
            if ($newDisponible < $oldDisponible && $porcentajeActual < 10) {
                // Notificación y alerta para el cliente
                FcmNotificationService::sendBajoDisponibleNotification(
                    $cliente, 
                    $newDisponible, 
                    $cliente->cupo
                );
                
                Alerta::create([
                    'id_usuario' => $cliente->user->id,
                    'id_rel' => $cliente->id,
                    'fecha' => now(),
                    'observacion' => "Tu disponible ha bajado a {$newDisponible}L, menos del 10% de tu cupo.",
                    'estatus' => 0,
                    'accion' => "/mi-perfil"
                ]);

                // Notificación y alerta para el administrador
                FcmNotificationService::sendBajoDisponibleNotificationToSuperAdmins(
                    $cliente,
                    $newDisponible,
                    $cliente->cupo
                );
                
                Alerta::create([
                    'id_usuario' => null,
                    'id_rel' => $cliente->id,
                    'fecha' => now(),
                    'observacion' => "El cliente {$cliente->nombre} tiene bajo disponible ({$newDisponible}L).",
                    'estatus' => 0,
                    'accion' => "/clientes/{$cliente->id}"
                ]);
            }
        }
    }
}