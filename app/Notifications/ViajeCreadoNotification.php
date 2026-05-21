<?php

namespace App\Notifications;

use App\Models\Viaje;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ViajeCreadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $viaje;

    public function __construct(Viaje $viaje)
    {
        $this->viaje = $viaje;
    }

    // Definimos los canales de envío
    public function via($notifiable)
    {
        // Enviará por WebPush y por nuestro canal interno de WhatsApp
        return [WebPushChannel::class, 'whatsapp'];
    }

    // 1. Configuración de la notificación WebPush (Flutter / Web webview)
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('¡Nuevo Viaje Asignado!')
            ->body("Se ha programado el viaje ID-{$this->viaje->id} hacia " . ($this->viaje->destino_ciudad ?? 'Destino No Definido') . ". para el día " . $this->viaje->fecha_salida->format('d/m/Y H:i') . ". Recuerda realizar el Checklist de Salida antes de partir.")
            ->icon('/images/icons/icon-192x192.png') // Ruta de tu icono corporativo
            ->badge('/images/icons/badge-72x72.png')
            ->data(['url' => url("/viajes/resumen-programacion/{$this->viaje->id}")])
            ->options(['TTL' => 86400]); // Duración de 24 horas en cola si está offline
    }

    // 2. Lógica de mensajería para WhatsApp
    public function toWhatsApp($notifiable)
    {
        $numero = $notifiable->routeNotificationForWhatsApp();
        if (!$numero) return null;

        $mensaje = "*TuCombustible - Nuevo Viaje Asignado*\n\n"
                 . "Hola, se te ha asignado un nuevo viaje.\n"
                 . "📍 *Destino:* " . ($this->viaje->destino_ciudad ?? 'N/A') . "\n"
                 . " *Unidad:* ".($this->viaje->vehiculo->flota ?? 'N/A')." [" . ($this->viaje->vehiculo->placa ?? 'N/A') . "]\n";

        if (!is_null($this->viaje->cisterna)) {
            $mensaje .= "🛢️ *Cisterna:* " . $this->viaje->cisternaAcoplada->flota . " [" . $this->viaje->cisternaAcoplada->placa . "]\n\n";
        } 
        $mensaje .= "📅 *Fecha:* " . $this->viaje->fecha_salida->format('d/m/Y H:i') . "\n\n";
        $mensaje .= "Por favor, Recuerda realizar el Checklist de Salida antes de partir. Puedes revisar los detalles del viaje aquí: " . url("/viajes/resumen-programacion/{$this->viaje->id}") . "\n\n"
                 . "¡Buen viaje y maneja con seguridad!";

        // Retornamos la estructura limpia para el driver externo
        return [
            'to' => $numero,
            'message' => $mensaje
        ];
    }
}