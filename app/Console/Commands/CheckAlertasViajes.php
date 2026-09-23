<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Viaje;
use App\Models\Inspeccion;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckAlertasViajes extends Command
{
    protected $signature = 'viajes:check-alertas';
    protected $description = 'Evalúa retrasos en salidas y retornos de viajes con control de excepciones y optimización de consultas';

    public function handle()
    {
        $baseUrl = rtrim(config('services.whatsapp.url'), '/');
        $tokenWA = config('services.whatsapp.key');
        $endpoint = "{$baseUrl}/messages/chat"; // Limpiamos el token de la URL si se envía por el Body
        
        $this->info("Iniciando verificación de alertas operativas: " . now()->toDateTimeString());

        // IDs de usuarios a notificar vía Push (FCM)
        $usuariosNotificar = [1, 504, 495]; 
        $reporte = ['salidas' => 0, 'notificadas_salida' => 0, 'retornos' => 0, 'notificadas_retorno' => 0];

        // --- CASO 1: SALIDAS RETRASADAS (Optimizado sin N+1) ---
        $this->comment("Verificando salidas programadas (umbrales exactos 15, 30 y 60 min)..."); 
        $viajesProgramados = Viaje::with(['vehiculo', 'chofer.persona'])
            ->where('status', 'Programado')
            // Filtro de rango de tiempo para optimizar memoria (entre 15 y 65 minutos atrás)
            ->where('fecha_salida', '<=', now()->subMinutes(15))
            ->where('fecha_salida', '>=', now()->subMinutes(65))
            ->whereDoesntHave('inspecciones', function($query) {
                $query->whereNull('respuesta_in');
            })
            ->get();

        // Filtrar únicamente los viajes cuyo tiempo transcurrido coincida exactamente con 15, 30 o 60 min
        $viajesRetrasados = $viajesProgramados->filter(function ($viaje) {
            $minutosTranscurridos = (int) $viaje->fecha_salida->diffInMinutes(now());
            return in_array($minutosTranscurridos, [15, 30, 60]);
        });
        
        

        $this->info("Viajes en mora coincidentes con los umbrales: " . $viajesRetrasados->count());

        foreach ($viajesRetrasados as $viaje) {
            $reporte['salidas']++;
            
            try {
                $tiempoRetraso = (int) $viaje->fecha_salida->diffInMinutes(now());
                $this->warn(" > Procesando alerta ({$tiempoRetraso} min): Viaje #{$viaje->id}");
                
                $mensajeSalida = "*🚨 ALERTA SALIDA RETRASADA ({$tiempoRetraso} MIN) 🚨*\n\n" .
                    "El viaje *#{$viaje->id}* con destino a *{$viaje->destino_ciudad}* no registra checklist ni ha salido tras *{$tiempoRetraso} min* de retraso.\n\n" .
                    "• *Vehículo:* {$viaje->vehiculo->flota} ({$viaje->vehiculo->placa})\n" .
                    "• *Destino:* {$viaje->destino_ciudad}\n" .
                    "• *Fecha Salida Programada:* {$viaje->fecha_salida->format('d/m/Y H:i A')}\n" .
                    "• *Conductor:* {$viaje->chofer->persona->nombre}\n\n" .
                    "• *ACCIÓN RECOMENDADA:* Notificar el motivo del retraso o gestionar los ajustes correspondientes para reprogramar la planificación.";

                // Envío de alertas Push (Protegido individualmente)
                $this->enviarAlertaPush($mensajeSalida, $usuariosNotificar, $viaje);
                $reporte['notificadas_salida']++;

                // Notificación al grupo de WhatsApp con Timeout de protección
                Http::asForm()
                    ->timeout(10)
                    ->withoutVerifying()
                    ->post($endpoint, [
                        'token'    => $tokenWA,
                        'to'      => config('services.whatsapp.group_operaciones'),
                        'body'     => $mensajeSalida,
                        'priority' => 1,
                    ]);

            } catch (Exception $e) {
                $this->error("Falló el procesamiento del viaje de salida #{$viaje->id}: " . $e->getMessage());
                Log::error("Error en CheckAlertasViajes (Salidas) - Viaje #{$viaje->id}: " . $e->getMessage());
            }
        }

        $this->newLine();

        // --- CASO 2: RETORNOS COMPLETADOS SIN CHECK-IN (Optimizado sin N+1) ---
        $this->comment("Verificando viajes COMPLETADOS sin Checklist de Entrada (1h de tolerancia)...");

        $viajesSinCheckIn = Viaje::with(['vehiculo', 'chofer.persona'])
            ->where('status', 'COMPLETADO')
            ->whereNotNull('fecha_llegada')
            ->where('fecha_llegada', '<=', now()->subHour())
            // Trae solo los que no tienen ninguna inspección con respuesta_in completada
            ->whereDoesntHave('inspecciones', function($q) {
                $q->whereNotNull('respuesta_in');
            })
            ->get();

        $this->info("Viajes completados sin check-in encontrados: " . $viajesSinCheckIn->count());

        foreach ($viajesSinCheckIn as $viaje) {
            $reporte['retornos']++;

            try {
                $this->warn(" > Procesando alerta retorno: Viaje #{$viaje->id}");
                $tiempoRetraso = now()->diffInMinutes($viaje->fecha_llegada);
                
                // Corregido: Ahora se utiliza la variable estructurada real
                $mensajeRetorno = "*🚨 ALERTA: RETORNO SIN CHECK-IN 🚨*\n\n" .
                    "El viaje *#{$viaje->id}* fue marcado como *COMPLETADO* (Unidad Liberada), pero el conductor aún no ha realizado el Checklist de Entrada tras *{$tiempoRetraso} min* desde su llegada física.\n\n" .
                    "• *Vehículo:* {$viaje->vehiculo->flota} ({$viaje->vehiculo->placa})\n" .
                    "• *Procedencia:* {$viaje->destino_ciudad}\n" .
                    "• *Hora de Llegada Real:* {$viaje->fecha_llegada->format('d/m/Y H:i A')}\n" .
                    "• *Conductor:* {$viaje->chofer->persona->nombre}\n\n" .
                    "*Acción Requerida:* Exigir la ejecución inmediata del checklist de entrada para cerrar el ciclo de auditoría.";

                // Envío de Alertas Push
                $this->enviarAlertaPush($mensajeRetorno, $usuariosNotificar, $viaje);
                $reporte['notificadas_retorno']++;

                // Enviar también al grupo de WhatsApp operativo para mantener el estándar
               /* Http::asForm()
                    ->timeout(10)
                    ->withoutVerifying()
                    ->post($endpoint, [
                        'token'    => $tokenWA,
                        'to'      => config('services.whatsapp.group_operaciones'),
                        'body'     => $mensajeRetorno,
                        'priority' => 1,
                    ]); */

            } catch (Exception $e) {
                $this->error("Falló el procesamiento del retorno del viaje #{$viaje->id}: " . $e->getMessage());
                Log::error("Error en CheckAlertasViajes (Retornos) - Viaje #{$viaje->id}: " . $e->getMessage());
            }
        }

        $this->renderResumen($reporte);
    }

    private function enviarAlertaPush($mensaje, $usuarios, $viaje)
    {
        foreach ($usuarios as $userId) {
            try {
                FcmNotificationService::enviarNotification(
                    "Seguridad Operativa",
                    $mensaje,
                    ['viaje_id' => $viaje->id, 'tipo' => 'ALERTA_OPERATIVA', 'user_id' => $userId]
                );
            } catch (Exception $e) {
                // Logueamos el fallo push de este usuario en específico pero dejamos que el bucle continúe
                Log::warning("No se pudo enviar notificación FCM al usuario {$userId} para el viaje #{$viaje->id}: " . $e->getMessage());
            }
        }
    }

    private function renderResumen($reporte)
    {
        $this->newLine();
        $this->info("PROCESO FINALIZADO CON ÉXITO");
        $this->table(
            ['Categoría', 'Encontrados', 'Alertas Disparadas'],
            [
                ['Salidas Retrasadas', $reporte['salidas'], $reporte['notificadas_salida']],
                ['Retornos Inconsistentes (Sin Check-In)', $reporte['retornos'], $reporte['notificadas_retorno']],
            ]
        );
    }
}