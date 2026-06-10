<?php

namespace App\Observers;

use App\Models\Pago;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\Log;

class PagosObserver
{

    protected $whatsappService;

    public function __construct()
    {
        $this->whatsappService = new WhatsappApiService();
    }
    /**
     * Handle the Viaje "created" event.
     *
     * @return void
     */
    public function created(Pago $pago)
    {

        $notificacion = "Buen el pago por su pedido #{$pago->id_pedido} Ha sido Validado";
        $this->whatsappService->enviarMensaje($notificacion, $pago->routeNotificationForWhatsApp());
    
    }

}
