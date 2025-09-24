<?php

namespace App\Observers;

use App\Models\Deposito; // o Deposito, según tu modelo
use App\Models\Alerta;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

class DepositoObserver
{
    /**
     * Manejar el evento 'updated' del modelo Deposito.
     */
    public function updated(Deposito $deposito)
    {
        // Detectar si el nivel ha cambiado (un despacho)
        if ($deposito->isDirty('nivel_actual_litros')) {
            $oldNivel = $deposito->getOriginal('nivel_actual_litros');
            $newNivel = $deposito->nivel_actual_litros;

            Log::info("Cambio de nivel en deposito {$deposito->id}: de {$oldNivel}L a {$newNivel}L");
            
            // Cuando se despacha de un deposito
            // Asume que el cambio de nivel es por un despacho
            // Lógica para el deposito 00:
            if ($deposito->serial === '00') {
                 // Aquí puedes verificar si el despacho fue confirmado como recibido para enviar otra notificación.
                 // Lógica: Cuando se confirma un despacho, se puede disparar un evento 'DespachoConfirmadoEvent'
                 // y un listener se encargaría de esta notificación y alerta.
            }

            // Alerta de nivel bajo (para administradores)
            $porcentajeActual = ($newNivel / $deposito->capacidad_litros) * 100;
            if ($porcentajeActual < 10 && $deposito->getOriginal('nivel') >= 10) {
                 // Enviar notificación a los super admins
                 FcmNotificationService::sendBajoDisponibleNotificationToSuperAdmins($deposito, $newNivel, $deposito->capacidad_litros);
                 
                 // Crear alerta para administradores
                 Alerta::create([
                    'id_usuario' => null, // null para notificar a todos los admins
                    'id_rel' => $deposito->id,
                    'fecha' => now(),
                    'observacion' => "El nivel del deposito {$deposito->serial} está por debajo del 10% ({$newNivel}L).",
                    'estatus' => 0,
                    'accion' => "/depositos/{$deposito->id}"
                 ]);
                 Log::info("Alerta de bajo nivel creada para deposito {$deposito->id}");
            }
        }
    }
}