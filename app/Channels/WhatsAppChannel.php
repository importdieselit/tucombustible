<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
   public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $data = $notification->toWhatsApp($notifiable);
        if (!$data) return;

        try {
            // Tu petición al proveedor de WhatsApp
            $response = Http::timeout(5) // Importante: timeout corto para no colgar el sistema
                ->withToken(env('WHATSAPP_API_TOKEN'))
                ->post(env('WHATSAPP_API_URL'), [
                    'phone' => $data['to'],
                    'body'  => $data['message'],
                ]);

            if (!$response->successful()) {
                Log::warning("Fallo al enviar WhatsApp a {$data['to']}. Proveedor respondió: " . $response->body());
            }

        } catch (\Throwable $e) {
            // Capturamos TODO (Excepciones HTTP, fallos de red, etc.)
            // Lo guardamos en el log de Laravel, pero NO lanzamos el error hacia arriba.
            Log::error("Error crítico silenciado en WhatsAppChannel: " . $e->getMessage());
        }
    }
}