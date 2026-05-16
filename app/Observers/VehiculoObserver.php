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
        if (!$vehiculo->isDirty('estatus')) {
            return;
        }

        // CASO 2: Cambio a EN RUTA (2)
        if ($vehiculo->estatus == 2) {
            Log::info("Vehículo {$vehiculo->id} ha cambiado a EN RUTA. Verificando checklist...");
            
            // Sincronizar acoplado si existe
            if ($vehiculo->acoplado_id) {
                $vehiculoAcoplado = Vehiculo::find($vehiculo->acoplado_id);
                if ($vehiculoAcoplado) {
                    $vehiculoAcoplado->estatus = 2;
                    $vehiculoAcoplado->saveQuietly(); // Disparará el observer del acoplado de forma aislada para evitar loops
                }
            }

            // Buscamos el viaje programado más próximo (eliminamos restricciones rígidas de fecha y relaciones huérfanas)
            $viaje = Viaje::with(['chofer.persona'])
                ->where('vehiculo_id', $vehiculo->id)
                ->where('status', 'Programado')
                ->orderBy('fecha_salida', 'asc') // El más antiguo o próximo a salir primero
                ->first();

            if ($viaje) {
                $viaje->status = 'EN RUTA';
                $viaje->fecha_salida_real = now();
                $viaje->save();

                // Validar Checklists
                $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)
                    ->whereNull('respuesta_in')
                    ->exists();

                if (!$hasChecklist) {
                    $hasChecklist2 = Inspeccion::where('vehiculo_id', $viaje->vehiculo_id)
                        ->where('created_at', '>=', now()->subHours(2))
                        ->whereNull('respuesta_in')
                        ->first();
                    
                    if ($hasChecklist2) {
                        $hasChecklist2->viaje_id = $viaje->id;
                        $hasChecklist2->save();
                        $hasChecklist = true;
                    }
                }

                // Si se confirma el incumplimiento
                if (!$hasChecklist) {
                    $usuariosNotificar = [1, 2];
                    
                    // 1. Notificaciones push individuales (van dentro del bucle)
                    foreach ($usuariosNotificar as $userId) {
                        FcmNotificationService::enviarNotification(
                            "INCUMPLIMIENTO DE PROCESO",
                            "El vehículo {$vehiculo->flota} pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.",
                            ['viaje_id' => $viaje->id, 'user_id' => $userId]
                        );
                    }

                    // 2. Notificación única al grupo de WhatsApp (VA FUERA DEL BUCLE)
                    $message = "*⚠️ INCUMPLIMIENTO DE PROCESO ⚠️*\n\n" .
                        "El vehículo *{$vehiculo->flota}* ({$vehiculo->placa}) pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.\n" .
                        "• *Destino:* {$viaje->destino_ciudad}\n" .
                        "• *Chofer:* {$viaje->chofer->persona->nombre}\n" .
                        "• *Fecha Programada:* {$viaje->fecha_salida->format('d/m/Y H:i')}\n\n" .
                        "*Acción Requerida:* Revisar el proceso con el conductor y asegurar cumplimiento.";
                    
                    $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
                }
            }
        } 
        
        // CASO 1: Cambio a Disponible (1)
        elseif ($vehiculo->estatus == 1 && $vehiculo->getOriginal('estatus') == 2) {
    
            Log::info("Vehículo {$vehiculo->id} regresó de ruta. Procesando cierre operativo...");

            // 1. Procesar vehículo acoplado disparando su propio ciclo de eventos y observers
            if ($vehiculo->acoplado_id) {
                $vehiculoAcoplado = Vehiculo::find($vehiculo->acoplado_id);
                
                if ($vehiculoAcoplado) {
                    Log::info("Desacoplando y liberando cisterna ID {$vehiculoAcoplado->id}. Disparando eventos de validación...");
                    
                    $vehiculoAcoplado->estatus = 1;
                    $vehiculoAcoplado->acoplado_id = null;
                    $vehiculoAcoplado->saveQuietly(); 
                }

                // Limpiamos la relación en el vehículo principal
                $vehiculo->acoplado_id = null;
                $vehiculo->saveQuietly(); // Evita bucles infinitos en el modelo actual
            }

            // 2. Buscar el viaje activo 'EN RUTA' cargando chofer y persona para la notificación
            $viajeEnRuta = Viaje::with(['chofer.persona'])
                ->where('vehiculo_id', $vehiculo->id)
                ->where('status', 'EN RUTA')
                ->first();

            if ($viajeEnRuta) {
                $viajeEnRuta->status = 'COMPLETADO';
                $viajeEnRuta->fecha_llegada = now();
                $viajeEnRuta->save();

                Log::info("Viaje #{$viajeEnRuta->id} completado automáticamente por liberación de unidad.");

                // 3. Notificación única al grupo de WhatsApp (Formato Corporativo)
                $message = "*✅ RETORNO Y CIERRE DE VIAJE ✅*\n\n" .
                    "El vehículo *{$vehiculo->flota}* ({$vehiculo->placa}) ha retornado a la Sede de forma segura.\n\n" .
                    "• *Viaje Cerrado:* #{$viajeEnRuta->id}\n" .
                    "• *Procedencia:* {$viajeEnRuta->destino_ciudad}\n" .
                    "• *Chofer:* {$viajeEnRuta->chofer->persona->nombre}\n" .
                    "• *Hora de Llegada:* {$viajeEnRuta->fecha_llegada->format('d/m/Y H:i A')}\n\n" .
                    "*Estatus Actual:* El viaje ha sido consolidado como *Completado* Atencion Chofer Realizar Checklist de Llegada para cerrar el proceso operativo.";
                
                $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
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
