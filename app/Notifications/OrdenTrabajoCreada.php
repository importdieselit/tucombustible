<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Models\Orden;


class OrdenTrabajoCreada extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $orden;

    public function __construct($orden)
    {
        $this->orden = $orden;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [WebPushChannel::class]; // Usar canal WebPush
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('🛠️ Nueva Orden de Trabajo')
            ->icon('/img/icon-192x192.png') // Tu icono corporativo
            ->body("Se ha generado la Orden #{$this->orden->id} para la unidad {$this->orden->vehiculoBelong->flota}")
            ->data(['url' => url('/ordenes/show/' . $this->orden->id)])
            ->badge('/img/logomini.png'); // El logo pequeño para la barra superior
    }
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
