<?php

namespace App\Observers;

use App\Models\Vehiculo;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;

class VehiculoObserver
{
    protected $telegramService;
    const LIMITE_KM_MANTENIMIENTO = 5000;

    // Inyectamos el servicio de notificaciones
    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Maneja el evento 'actualizando' (updating) del Vehiculo.
     * Se ejecuta ANTES de que el registro sea guardado en la DB.
     * Esto nos permite comparar los valores originales y los nuevos.
     *
     * @param Vehiculo $vehiculo
     * @return void
     */
    public function updating(Vehiculo $vehiculo)
    {
        // 1. Verificar si el campo km_mantt ha sido modificado
        if ($vehiculo->isDirty('km_mantt')) {
            $newKm = (int) $vehiculo->km_mantt;
            $oldKm = (int) $vehiculo->getOriginal('km_mantt');

            // 2. Verificar la condición: el nuevo KM supera el límite Y el KM anterior NO lo superaba.
            if ($newKm >= self::LIMITE_KM_MANTENIMIENTO && $oldKm < self::LIMITE_KM_MANTENIMIENTO) {
                
                $message = 
                    "*⚠️ ALERTA DE MANTENIMIENTO PREVENTIVO ⚠️*\n\n" .
                    "La unidad: *{$vehiculo->placa}* ha cruzado el umbral de los " . self::LIMITE_KM_MANTENIMIENTO . " KM.\n" .
                    "• *KM Actual:* `{$newKm}` KM\n" .
                    "• *Tipo:* {$vehiculo->tipo}\n\n" .
                    "*Acción:* Requiere revisión inmediata para mantenimiento preventivo.";

                // 3. Enviar la notificación de forma asíncrona (opcional) o síncrona
                $this->telegramService->sendMessage($message);

                Log::warning("Alerta de mantenimiento para {$vehiculo->placa} enviada a Telegram.");
            }
        }
    }

    /**
     * Maneja el evento 'creado' (created) del Vehiculo.
     *
     * @param Vehiculo $vehiculo
     * @return void
     */
    public function created(Vehiculo $vehiculo)
    {
        // Ejemplo de uso: mensaje de bienvenida para la nueva unidad.
        $message = 
            "*✅ Nuevo Vehículo Registrado ✅*\n\n" .
            "La unidad *{$vehiculo->placa}* ha sido dada de alta en el sistema.\n" .
            "• *Tipo:* {$vehiculo->tipo}\n" .
            "• *KM Inicial de Mantenimiento:* `{$vehiculo->km_mantt}` KM";

        $this->telegramService->sendMessage($message);
    }
}
