<?php

namespace App\Observers;

use App\Models\Vehiculo;
use App\Models\Viaje;
use App\Models\Inspeccion;
use App\Services\FcmNotificationService;
use App\Services\TelegramNotificationService;
use App\Services\LogisticaInventarioService;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\Log;


class VehiculoObserver
{
    protected $telegramService;
    protected $whatsappService;
    protected $inventarioService;

    // Control anti-rebote en memoria por petición (Solución al error principal)
    protected static $viajesProcesados = [];

    const LIMITE_KM_MANTENIMIENTO = 5000;
    const LIMITE_HRS_MANTENIMIENTO = 200;

    // Inyectamos el servicio de notificaciones

    public function __construct(TelegramNotificationService $telegramService, WhatsappApiService $whatsappService, LogisticaInventarioService $inventarioService)
    {
        $this->telegramService = $telegramService;
        $this->whatsappService = $whatsappService;
        $this->inventarioService = $inventarioService;
    }

    /**
     * Se ejecuta ANTES de guardar en la DB.
     * Ideal para alertas de contadores y bloquear falsos positivos (Anti-Rebote).
     */
    public function updating(Vehiculo $vehiculo)
    {
        // --- ALERTAS DE MANTENIMIENTO ---
        if ($vehiculo->wasChanged('km_mantt')) {
            $newKm = (int) $vehiculo->km_mantt;
            $oldKm = (int) $vehiculo->getOriginal('km_mantt');
           
            if ($newKm >= self::LIMITE_KM_MANTENIMIENTO && $oldKm < self::LIMITE_KM_MANTENIMIENTO) {
                $message = "*⚠️ ALERTA DE MANTENIMIENTO PREVENTIVO ⚠️*\n\n" .
                           "La unidad: {$vehiculo->flota} *{$vehiculo->placa}* ha cruzado el umbral de los " . self::LIMITE_KM_MANTENIMIENTO . " Km.\n" .
                           "• *Km Actual:* `{$newKm}` Km\n" .
                           "*Acción:* Requiere revisión inmediata para mantenimiento preventivo.";
                
                $this->telegramService->sendMessage($message);
                $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
            } 
        } 

        if ($vehiculo->wasChanged('hrs_mantt')) {
            $newHrs = (int) $vehiculo->hrs_mantt;
            $oldHrs = (int) $vehiculo->getOriginal('hrs_mantt');
           
            if ($newHrs >= self::LIMITE_HRS_MANTENIMIENTO && $oldHrs < self::LIMITE_HRS_MANTENIMIENTO) {
                $message = "*⚠️ ALERTA DE MANTENIMIENTO PREVENTIVO ⚠️*\n\n" .
                           "La unidad: {$vehiculo->flota} *{$vehiculo->placa}* ha cruzado el umbral de las " . self::LIMITE_HRS_MANTENIMIENTO . " Horas.\n" .
                           "• *Horas Actual:* `{$newHrs}` Hrs\n" .
                           "*Acción:* Requiere revisión inmediata para mantenimiento preventivo.";

                $this->telegramService->sendMessage($message);
            } 
        }

        // --- 🛡️ FILTRO ANTI-REBOTE GPS ---
        // Si el vehículo intenta pasar de EN RUTA (2) a DISPONIBLE (1)
        if ($vehiculo->isDirty('estatus') && $vehiculo->estatus == 1 && $vehiculo->getOriginal('estatus') == 2) {
            $viajeActivo = Viaje::where('vehiculo_id', $vehiculo->id)
                ->where('status', 'EN RUTA')
                ->first();

            if ($viajeActivo) {
                $horaSalida = $viajeActivo->fecha_salida_real ?? $viajeActivo->updated_at;
                $minutosEnRuta = now()->diffInMinutes($horaSalida);

                if ($minutosEnRuta < 30) {
                   // Log::warning("⚠️ REBOTE GPS DETECTADO: Unidad {$vehiculo->flota} intentó marcar retorno con solo {$minutosEnRuta} min en ruta. Evitando fluctuación.");
                    
                    // Forzamos el estatus a mantenerlo en 2. Al no haber cambios reales, 'updated' no procesará nada.
                    $vehiculo->estatus = 2; 
                }
            }
        }
    }

    // public function updated(Vehiculo $vehiculo)
    // {
    //     // CASO 2: Cambio a EN RUTA (2) sin checklist previo
    //     if ($vehiculo->isDirty('estatus') && $vehiculo->estatus == 2) {
            
    //         // Buscamos el viaje programado para este vehículo hoy
    //         $viaje = Viaje::where('vehiculo_id', $vehiculo->id)
    //             ->where('status', 'Programado')
    //             ->latest()
    //             ->first();

    //         if ($viaje) {
    //             $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)
    //                 ->whereNull('respuesta_in')
    //                 ->exists();

    //             if (!$hasChecklist) {
    //                 $usuariosNotificar = [1, 2, 5];
    //                 foreach ($usuariosNotificar as $userId) {
    //                     FcmNotificationService::enviarNotification(
    //                         "INCUMPLIMIENTO DE PROCESO",
    //                         "El vehículo {$vehiculo->flota} pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.",
    //                         ['viaje_id' => $viaje->id],
    //                         $userId
    //                     );
    //                 }
    //             }
    //         }
    //     }
    // }

    /**
     * Se ejecuta DESPUÉS de guardar en la DB.
     * Ideal para disparar efectos secundarios en cascada (Notificaciones, updates de otras tablas).
     */
    public function updated(Vehiculo $vehiculo)
    {
        // Corrección del bug: Usar wasChanged en lugar de isDirty
        if (!$vehiculo->wasChanged('estatus')) {
            return;
        }
        

        // --- CASO 1: CAMBIO A EN RUTA (2) ---
        if ($vehiculo->estatus == 2) {
            // Log::info("Vehículo {$vehiculo->id} consolidado EN RUTA. Sincronizando viaje y auditorías...");
            
            // Sincronizar acoplado si existe
            if ($vehiculo->acoplado_id) {
                $vehiculoAcoplado = Vehiculo::find($vehiculo->acoplado_id);
                if ($vehiculoAcoplado) {
                    $vehiculoAcoplado->estatus = 2;
                    $vehiculoAcoplado->saveQuietly(); 
                }
            }

            $viaje = Viaje::with(['chofer.persona'])->where('vehiculo_id', $vehiculo->id)
            ->where(function ($query) {
                $query->whereDate('fecha_salida', '>=', now())
                    ->orWhere('status', 'Programado');
            })
            ->first();

            if ($viaje) {

                // 🔥 CRÍTICO: Verificar e interceptar INMEDIATAMENTE antes de guardar o alertar
                if (isset(self::$viajesProcesados[$viaje->id])) {
                    // Log::info("🛑 Anti-Rebote Interno: El viaje #{$viaje->id} ya fue procesado en esta petición. Omitiendo duplicación.");
                    return;
                }

                // 🔥 CRÍTICO: Registrar de inmediato en memoria antes del ($viaje->save())
                self::$viajesProcesados[$viaje->id] = true;
                
                $viaje->status = 'EN RUTA';
                $viaje->fecha_salida_real = now();
                $viaje->save();

                // Validación de Checklists de Salida
                $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)->whereNull('respuesta_in')->exists();

                if (!$hasChecklist) {
                    $hasChecklist2 = Inspeccion::where('vehiculo_id', $viaje->vehiculo_id)
                        ->where('created_at', '>=', now()->subMinutes(90)) // Permitir un margen de 90 minutos para completar el checklist
                        ->whereNull('respuesta_in')
                        ->first();
                    
                    if ($hasChecklist2) {
                        $hasChecklist2->viaje_id = $viaje->id;
                        $hasChecklist2->save();
                        $hasChecklist = true;
                    }
                }

                // Construcción de Notificación unificada de Salida
                $choferNombre = $viaje->chofer->persona->nombre ?? 'N/A';
                
                if (!$hasChecklist) {
                    // Notificaciones Push Internas por Incumplimiento
                    foreach ([1, 2] as $userId) {
                        FcmNotificationService::enviarNotification(
                            "INCUMPLIMIENTO DE PROCESO",
                            "El vehículo {$vehiculo->flota} pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.",
                            ['viaje_id' => $viaje->id, 'user_id' => $userId]
                        );
                    }

                    // 2. Notificación única al grupo de WhatsApp (VA FUERA DEL BUCLE)
                    $message = "*⚠️ INCUMPLIMIENTO DE PROCESO (SALIDA SIN CHECKLIST) ⚠️*\n\n" .
                        "El vehículo *{$vehiculo->flota}* ({$vehiculo->placa}) pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.\n" .
                        "• *Destino:* {$viaje->destino_ciudad}\n" .
                        "• *Chofer:* {$viaje->chofer->persona->nombre}\n" .
                        "• *Fecha Programada:* {$viaje->fecha_salida->format('d/m/Y H:i')}\n\n" .
                        "*Acción Requerida:* Revisar el proceso con el conductor y asegurar cumplimiento.";
                } else {
                    $message = "🚀 *SALIDA DETECTADA*:\n\n" .
                               "La Unidad *{$vehiculo->flota}* ({$vehiculo->placa}) va en ruta bajo la conducción de {$choferNombre}.\n\n" .
                               "• *Viaje:* #{$viaje->id}\n" .
                               "• *Destino:* {$viaje->destino_ciudad}";
                }

                // 2. 🚀 ASENTAR SALIDA FÍSICA EN EL LEDGER
                try {
                    // Ejecuta el descuento de litros en el Ledger (TransaccionCombustible)
                    $this->inventarioService->registrarSalidaFisicaDespacho($viaje);
                    
                    // Actualizar estatus del viaje a En Ruta
                    $viaje->update(['status' => 'En Ruta']);
                    
                } catch (\Exception $e) {
                    Log::error("Error registrando salida física en Ledger para Viaje #{$viaje->id}: " . $e->getMessage());
                }
                
                $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
            }
        } 
        
        // --- CASO 2: CAMBIO A DISPONIBLE / RETORNO (1) ---
        elseif ($vehiculo->estatus == 1 && $vehiculo->getOriginal('estatus') == 2) {
            // Log::info("Vehículo {$vehiculo->id} consolidado en SEDE. Procesando cierres operativos...");

            // Liberar acoplado si existe
            if ($vehiculo->acoplado_id) {
                $vehiculoAcoplado = Vehiculo::find($vehiculo->acoplado_id);
                if ($vehiculoAcoplado) {
                    $vehiculoAcoplado->estatus = 1;
                    $vehiculoAcoplado->acoplado_id = null;
                    $vehiculoAcoplado->saveQuietly(); 
                }
                $vehiculo->acoplado_id = null;
                $vehiculo->saveQuietly(); 
            }

            $viajeEnRuta = Viaje::with(['chofer.persona'])
                ->where('vehiculo_id', $vehiculo->id)
                ->where('status', 'EN RUTA')
                ->first();

            if ($viajeEnRuta) {
                $viajeEnRuta->status = 'COMPLETADO';
                $viajeEnRuta->fecha_llegada = now();
                $viajeEnRuta->save();

                $choferNombre = $viajeEnRuta->chofer->persona->nombre ?? 'N/A';

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

    public function created(Vehiculo $vehiculo)
    {
        $message = "*✅ Nuevo Vehículo Registrado ✅*\n\n" .
                   "La unidad {$vehiculo->flota} *{$vehiculo->placa}* ha sido dada de alta en el sistema.\n" .
                   "• *Km Inicial de Mantenimiento:* `{$vehiculo->km_mantt}` KM";

        $this->telegramService->sendMessage($message);
    }
}
