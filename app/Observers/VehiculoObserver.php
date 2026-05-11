<?php

namespace App\Observers;

use App\Models\Vehiculo;
use App\Services\FcmNotificationService;
use App\Models\Viaje;
use App\Models\Inspeccion;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;

class VehiculoObserver
{
    protected $telegramService;
    const LIMITE_KM_MANTENIMIENTO = 5000;
    const LIMITE_HRS_MANTENIMIENTO = 200;

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
                    "La unidad: {$vehiculo->flota} *{$vehiculo->placa}* ha cruzado el umbral de los " . self::LIMITE_KM_MANTENIMIENTO . " Km.\n" .
                    "• *Km Actual:* `{$newKm}` Km\n" .
                    //"• *Tipo:* {$vehiculo->tipo}\n\n" .
                    "*Acción:* Requiere revisión inmediata para mantenimiento preventivo.";

                // 3. Enviar la notificación de forma asíncrona (opcional) o síncrona
                $this->telegramService->sendMessage($message);

            } 
        } 

        if ($vehiculo->isDirty('hrs_mantt')) {
            $newHrs = (int) $vehiculo->hrs_mantt;
            $oldHrs = (int) $vehiculo->getOriginal('hrs_mantt');
           
            // 2. Verificar la condición: el nuevo KM supera el límite Y el KM anterior NO lo superaba.
            if ($newHrs >= self::LIMITE_HRS_MANTENIMIENTO && $oldHrs < self::LIMITE_HRS_MANTENIMIENTO) {
             
                $message = 
                    "*⚠️ ALERTA DE MANTENIMIENTO PREVENTIVO ⚠️*\n\n" .
                    "La unidad: {$vehiculo->flota} *{$vehiculo->placa}* ha cruzado el umbral de las " . self::LIMITE_HRS_MANTENIMIENTO . " Horas de Trabajo.\n" .
                    "• *Horas de trabajo Actual:* `{$newHrs}` Hrs\n" .
                    //"• *Tipo:* {$vehiculo->tipo}\n\n" .
                    "*Acción:* Requiere revisión inmediata para mantenimiento preventivo.";

                // 3. Enviar la notificación de forma asíncrona (opcional) o síncrona
                $this->telegramService->sendMessage($message);

            } 
        }
        
    }

    public function updated(Vehiculo $vehiculo)
    {
        // CASO 2: Cambio a EN RUTA (2) sin checklist previo
        if ($vehiculo->isDirty('estatus') && $vehiculo->estatus == 2) {
            
            // Buscamos el viaje programado para este vehículo hoy
            $viaje = Viaje::where('vehiculo_id', $vehiculo->id)
                ->where('status', 'Programado')
                ->latest()
                ->first();

            if ($viaje) {
                $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)
                    ->whereNull('respuesta_in')
                    ->exists();

                if (!$hasChecklist) {
                    $usuariosNotificar = [1, 2];
                    foreach ($usuariosNotificar as $userId) {
                        FcmNotificationService::enviarNotification(
                            "INCUMPLIMIENTO DE PROCESO",
                            "El vehículo {$vehiculo->flota} pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.",
                            ['viaje_id' => $viaje->id,'user_id' => $userId]
                        );
                    }
                }
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
            "La unidad {$vehiculo->flota} *{$vehiculo->placa}* ha sido dada de alta en el sistema.\n" .
            //"• *Tipo:* {$vehiculo->tipo}\n" .
            "• *Km Inicial de Mantenimiento:* `{$vehiculo->km_mantt}` KM";

        $this->telegramService->sendMessage($message);
    }
}
