<?php

namespace App\Notifications;

use App\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;


class PagoValidadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $pago;

    public function __construct(Pago $pago)
    {
        $this->pago = $pago;
    }

    // Definimos los canales de envío
    public function via($notifiable)
    {
        // Enviará por WebPush y por nuestro canal interno de WhatsApp
        return ['whatsapp'];
    }

    

    // 2. Lógica de mensajería para WhatsApp
    public function toWhatsApp($notifiable)
    {
        $numero = $notifiable->routeNotificationForWhatsApp();
        if (!$numero) return null;

        $mensaje = "*TuCombustible - Pago Validado*\n\n"
                 . "Hola, El pago de Su pedido ya ha sido validado.\n"
                 . "📍 *Cantidad Litros:* " . $this->pago->litros . "\n"
                 . "📍 *Fecha Solicitud:* " . $this->pago->fecha_solicitud->format('d/m/Y H:i') . "\n";
        $mensaje .= "Su pedido ya se encuentra en nuestro sistema, se le notificara cuando ya este en camino";
        
        return [
            'to' => $numero,
            'message' => $mensaje
        ];
        
    }
}