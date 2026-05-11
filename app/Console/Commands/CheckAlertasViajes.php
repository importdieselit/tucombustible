<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Viaje;
use App\Models\Inspeccion;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CheckAlertasViajes extends Command
{
    protected $signature = 'viajes:check-alertas';
    protected $description = 'Evalúa retrasos en salidas y retornos de viajes con reporte en consola';

    public function handle()
    {
        $baseUrl = rtrim(config('services.whatsapp.url'), '/');
        $tokenWA = config('services.whatsapp.key');
        $endpoint = "{$baseUrl}/messages/chat?token={$tokenWA}";
        
        $this->info("Iniciando verificación de alertas operativas: " . now()->toDateTimeString());
        

        // IDs de usuarios a notificar de forma individual vía Push (FCM)
        $usuariosNotificar = [1, 504, 495]; 

        $reporte = ['salidas' => 0, 'notificadas_salida' => 0, 'retornos' => 0, 'notificadas_retorno' => 0];

        // --- CASO 1: SALIDAS RETRASADAS ---
        $this->comment("Verificando salidas programadas (30 min de tolerancia)...");
        
        // Optimización: Incorporación de Eager Loading para prevenir fugas de rendimiento en el ciclo foreach
        $viajesRetrasados = Viaje::with(['vehiculo', 'chofer.persona'])
            ->where('status', 'Programado')
            ->where('fecha_salida', '<=', now()->subMinutes(30))
            ->get();

        $this->info("Viajes en mora encontrados: " . $viajesRetrasados->count());

        foreach ($viajesRetrasados as $viaje) {
            $reporte['salidas']++;
            
            // Verificamos inspección específica del viaje o general del vehículo reciente
            $hasChecklist = Inspeccion::where(function($query) use ($viaje) {
                $query->where('viaje_id', $viaje->id)
                      ->orWhere(function($q) use ($viaje) {
                          $q->where('vehiculo_id', $viaje->vehiculo_id)
                            ->where('created_at', '<=', now()->subMinutes(30));
                      });
            })
            ->whereNull('respuesta_in')
            ->exists();

            if (!$hasChecklist) {

                $this->warn(" > Enviando alerta: Viaje #{$viaje->id}");
                $tiempoRetraso = now()->diffInMinutes($viaje->fecha_salida);
                
                $mensajeSalida = "*🚨 ALERTA SALIDA RETRASADA 🚨*\n\n" .
                    "El viaje *#{$viaje->id}* con destino a *{$viaje->destino_ciudad}* no registra checklist Ni ha salido de las instalaciones tras *{$tiempoRetraso} min* de retraso.\n\n" .
                    "• *Vehículo:* {$viaje->vehiculo->flota} ({$viaje->vehiculo->placa})\n" .
                    "• *Destino:* {$viaje->destino_ciudad}\n" .
                    "• *Fecha Salida Programada:* {$viaje->fecha_salida->format('d/m/Y H:i A')}\n" .
                    "• *Conductor:* {$viaje->chofer->persona->nombre}\n".
                    "• *ACCION RECOMENDADAS:* Verificar motivo del retraso y Exigir la ejecución inmediata del checklist de salida y la partida física de la Unidad o realizar las correcciones necesarias en la planificacion para evitar impactos en la cadena logística.";

                // Envío de alertas Push
                $this->enviarAlerta($mensajeSalida, $usuariosNotificar, $viaje);

                $reporte['notificadas_salida']++;

                // Notificación única al grupo de operaciones de WhatsApp (Fuera de bucles redundantes)
                Http::asForm()
                    ->withoutVerifying()
                    ->post($endpoint, [
                        'token'       => $tokenWA,
                        'to'          => config('services.whatsapp.group_operaciones'),
                        'body'        => $mensajeSalida,
                        'priority'    => 1,
                        'referenceId' => '',
                    ]);
            }
        }

        $this->newLine();

        // --- CASO 3: RETORNOS COMPLETADOS SIN CHECK-IN ---
        $this->comment("Verificando viajes COMPLETADOS sin Checklist de Entrada (1h de tolerancia)...");

        // Optimización: Filtrado masivo desde base de datos. Se eliminó la doble consulta interna por registro.
        $viajesSinCheckIn = Viaje::with(['vehiculo', 'chofer.persona'])
            ->where('status', 'COMPLETADO')
            ->whereNotNull('fecha_llegada')
            ->where('fecha_llegada', '<=', now()->subHour())
            ->whereHas('inspecciones', function($q) {
                $q->whereNull('respuesta_in');
            })
            ->get();

        $this->info("Viajes completados sin check-in encontrados: " . $viajesSinCheckIn->count());

        foreach ($viajesSinCheckIn as $viaje) {
            $reporte['retornos']++;

            // Uso estricto de la fecha inmutable registrada por el Observer o la Telemetría
            $tiempoRetraso = now()->diffInMinutes($viaje->fecha_llegada);
            
            $mensajeRetorno = "*🚨 ALERTA: RETORNO SIN CHECK-IN 🚨*\n\n" .
                "El viaje *#{$viaje->id}* fue marcado como *COMPLETADO* (Unidad Liberada), pero el conductor aún no ha realizado el Checklist de Entrada tras *{$tiempoRetraso} min* desde su llegada física.\n\n" .
                "• *Vehículo:* {$viaje->vehiculo->flota} ({$viaje->vehiculo->placa})\n" .
                "• *Procedencia:* {$viaje->destino_ciudad}\n" .
                "• *Hora de Llegada Real:* {$viaje->fecha_llegada->format('d/m/Y H:i A')}\n" .
                "• *Conductor:* {$viaje->chofer->persona->nombre}\n\n" .
                "*Acción Requerida:* Exigir la ejecución inmediata del checklist de entrada para cerrar el ciclo de auditoría.";


            if (!$hasCheckIn) {
                $this->warn(" > Enviando alerta: Vehículo {$viaje->vehiculo->placa} inconsistente.");
                $this->enviarAlerta("ALERTA RETORNO: El vehículo {$viaje->vehiculo->flota} [{$viaje->vehiculo->placa}] está libre pero el viaje #{$viaje->id} a {$viaje->destino_ciudad} sigue 'EN RUTA'.", $usuariosNotificar, $viaje);
                $reporte['notificadas_retorno']++;
            }

        }

        $this->renderResumen($reporte);
    }

    private function enviarAlerta($mensaje, $usuarios, $viaje)
    {
        foreach ($usuarios as $userId) {
            FcmNotificationService::enviarNotification(
                "Seguridad Operativa",
                $mensaje,
                ['viaje_id' => $viaje->id, 'tipo' => 'ALERTA_OPERATIVA', 'user_id' => $userId]
            );
        }
    }

    private function renderResumen($reporte)
    {
        $this->newLine();
        $this->info("PROCESO FINALIZADO");
        $this->table(
            ['Categoría', 'Encontrados', 'Alertas Disparadas'],
            [
                ['Salidas Retrasadas', $reporte['salidas'], $reporte['notificadas_salida']],
                ['Retornos Inconsistentes (Sin Check-In)', $reporte['retornos'], $reporte['notificadas_retorno']],
            ]
        );
    }
}