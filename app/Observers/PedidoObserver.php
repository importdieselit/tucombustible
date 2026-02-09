<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Services\FcmNotificationService;
use App\Models\Alerta;
use Illuminate\Support\Facades\Log;

class PedidoObserver
{
    /**
     * Manejar el evento 'created' del modelo Pedido.
     */
    public function created(Pedido $pedido)
    {
        // Enviar notificación al administrador cuando se crea un nuevo pedido
        FcmNotificationService::sendNewPedidoNotificationToAdmin($pedido);

        // Crear alerta para el administrador
        Alerta::create([
            'id_usuario' => null, // null para notificar a todos los admins
            'id_rel' => $pedido->id,
            'fecha' => now(),
            'observacion' => "Se ha creado un nuevo pedido #{$pedido->id} por {$pedido->cantidad_solicitada}L.",
            'estatus' => 0,
            'accion' => "/pedidos/{$pedido->id}" // Ruta al detalle del pedido
        ]);
        Log::info("Alerta creada para nuevo pedido #{$pedido->id}");
    }

    /**
     * Manejar el evento 'updated' del modelo Pedido.
     */
    public function updated(Pedido $pedido)
    {
        // Si el estatus ha cambiado...
        if ($pedido->isDirty('estatus')) {
            $oldStatus = $pedido->getOriginal('estatus');
            $newStatus = $pedido->estado; // La propiedad se llama `estado` en tu modelo de Pedido

            Log::info("Cambio de estatus en pedido #{$pedido->id}: de {$oldStatus} a {$newStatus}");

            // Notificaciones para el cliente
            if (in_array($newStatus, ['aprobado', 'en_proceso', 'rechazado', 'completado', 'cancelado'])) {
                FcmNotificationService::sendPedidoStatusNotification(
                    $pedido, 
                    $oldStatus, 
                    $newStatus
                );

                // Crear alerta para el cliente
                Alerta::create([
                    'id_usuario' => $pedido->cliente->user->id, // Asume que el cliente tiene un usuario asociado
                    'id_rel' => $pedido->id,
                    'fecha' => now(),
                    'observacion' => FcmNotificationService::getStatusChangeBody($pedido, $oldStatus, $newStatus),
                    'estatus' => 0,
                    'accion' => "/mis-pedidos/{$pedido->id}" // Ruta para el cliente
                ]);
            }
        }
    }
}