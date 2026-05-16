<?php

namespace App\Observers;

use App\Models\Vehiculo;
use App\Services\FcmNotificationService;
use App\Models\Viaje;
use App\Models\Inspeccion;
use App\Services\TelegramNotificationService;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\Log;

class VehiculoObserver
{
    protected $telegramService;
    protected $whatsappService;
    const LIMITE_KM_MANTENIMIENTO = 5000;
    const LIMITE_HRS_MANTENIMIENTO = 200;

    // Inyectamos el servicio de notificaciones
    public function __construct(TelegramNotificationService $telegramService, WhatsappApiService $whatsappService)
    {
        $this->telegramService = $telegramService;
        $this->whatsappService = $whatsappService;
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
                    $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
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
        if ($vehiculo->isDirty('estatus')) {
            if ($vehiculo->estatus == 2) { // Si el nuevo estatus es "En Ruta"
                Log::info("Vehículo {$vehiculo->id} ha cambiado a EN RUTA. Verificando checklist...");
                if($vehiculo->acoplado_id) {
                    Log::info("Vehículo {$vehiculo->id} tiene acoplado ID {$vehiculo->acoplado_id}. Verificando checklist para ambos...");
                    $vehiculoAcoplado = Vehiculo::find($vehiculo->acoplado_id);
                    if ($vehiculoAcoplado) {
                        $vehiculoAcoplado->estatus = 2;
                        $vehiculoAcoplado->save();
                    }
                }
            
                // Buscamos el viaje programado para este vehículo hoy
                $viaje = Viaje::with('chofer', 'vehiculo', 'chofer.persona','despachos.cliente')
                    ->where('vehiculo_id', $vehiculo->id)
                    ->where('status', 'Programado')
                    ->whereDate('fecha_salida', now()->toDateString())
                    ->first();

                if ($viaje) {
                    $viaje->status = 'EN RUTA';
                    $viaje->save();

                    $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)
                        ->whereNull('respuesta_in')
                        ->exists();

                    $hasChecklist2 = Inspeccion::where('vehiculo_id', $viaje->vehiculo_id)
                        ->where('created_at', '>=', now()->subHours(2))
                        ->whereNull('respuesta_in')
                        ->first();
                    
                    if($hasChecklist2){
                        $hasChecklist2->viaje_id = $viaje->id;
                        $hasChecklist2->save();
                        $hasChecklist = true;
                    }

                    // Si no tiene checklist de salida ni checkin, es un incumplimiento claro
                    if (!$hasChecklist) {
                        $usuariosNotificar = [1, 2];
                        foreach ($usuariosNotificar as $userId) {
                            FcmNotificationService::enviarNotification(
                                "INCUMPLIMIENTO DE PROCESO",
                                "El vehículo {$vehiculo->flota} pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.",
                                ['viaje_id' => $viaje->id,'user_id' => $userId]
                            );
                            // whatsapp
                            $message = 
                                "*⚠️ INCUMPLIMIENTO DE PROCESO ⚠️*\n\n" .
                                "El vehículo {$vehiculo->flota} {$vehiculo->placa} pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.\n" .
                                "• *Destino:* {$viaje->destino_ciudad}\n" .
                                "• *Chofer:* {$viaje->chofer->persona->nombre}\n" .
                                "• *Fecha de Salida:* {$viaje->fecha_salida->format('d/m/Y H:i')}\n\n" .
                                "*Acción Requerida:* Revisar el proceso con el conductor y asegurar cumplimiento en futuros viajes.";
                            $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
                        }
                    }
                }
            } elseif ($vehiculo->estatus == 1) { // Si el nuevo estatus es "Disponible"
                // Desacoplar cualquier cisterna asociada
                if($vehiculo->acoplado_id) {
                    Vehiculo::where('id', $vehiculo->acoplado_id)->update(['estatus' => 1, 'acoplado_id' => null]);
                    $vehiculo->acoplado_id = null;
                    $vehiculo->save();
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
