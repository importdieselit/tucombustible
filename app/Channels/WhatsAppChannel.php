<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send($notifiable, Notification $notification)
    {
        // 1. Validamos que la notificación tenga el método preparado
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $data = $notification->toWhatsApp($notifiable);
        if (!$data) return;

        // 2. Extraemos la misma configuración que usas en tu Api Service
        $url = rtrim(config('services.whatsapp.url'), '/');
        $key = config('services.whatsapp.key');
        $endpoint = "{$url}/messages/chat";

        try {
            // 3. Replicamos tu estructura exacta de petición con la seguridad de timeout
            $response = Http::asForm()
                ->withoutVerifying() // Evita fallos de certificados SSL (Igual que en tu servicio)
                ->timeout(5)         // Mantenemos el timeout corto para no colgar tu sistema
                ->post($endpoint, [
                    'token'    => $key,
                    'to'       => $data['to'],
                    'body'     => $data['message'],
                    'priority' => 1
                ]);

            if (!$response->successful()) {
                Log::warning("Fallo al enviar WhatsApp a {$data['to']}. Proveedor respondió: " . $response->body());
            }

        } catch (\Throwable $e) {
            // Capturamos cualquier fallo de red o de API sin romper la experiencia del operador
            Log::error("Error crítico silenciado en WhatsAppChannel: " . $e->getMessage());
        }
    }
}