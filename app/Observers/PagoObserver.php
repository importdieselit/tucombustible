<?php

namespace App\Observers;

use App\Models\Pago;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\Log;

class PagoObserver
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
        try {
            // 1. Obtenemos el número formateado utilizando el método que ya creaste en el modelo
            $telefono = $pago->routeNotificationForWhatsApp();

            // 2. Validamos que el teléfono no esté vacío antes de accionar la API
            if (empty($telefono)) {
                Log::warning("Notificación de WhatsApp omitida: El cliente asociado al pago #{$pago->id} no posee un número válido.");
                return;
            }

            // 3. Estructuramos un mensaje más profesional (aprovechando negritas de WhatsApp con *)
            $notificacion = "¡Hola! Queremos informarte que tu pago por *{$pago->litros} LTS* (Ref: {$pago->referencia}) ha sido validado exitosamente.\n\nGracias por confiar en nosotros.";

            // 4. Ejecutamos el envío
            $this->whatsappService->enviarMensaje($notificacion, $telefono);

            // Opcional: Registrar el éxito en el log para auditorías
            Log::info("WhatsApp enviado con éxito al cliente por validación del pago #{$pago->id}");

        } catch (\Throwable $e) {
            // 5. Corrección del Catch: Usamos \Throwable para atrapar cualquier excepción o error
            // y enviamos un arreglo de contexto para depurar más rápido desde el archivo .log
            Log::error("Error al enviar notificación de WhatsApp por pago creado.", [
                'pago_id' => $pago->id ?? null,
                'pedido_id' => $pago->id_pedido ?? null,
                'mensaje_error' => $e->getMessage()
            ]);
        }
    }

}
