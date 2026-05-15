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
        // Evitar que el comando se ejecute si ya hay una instancia activa (Previene duplicidad por tiempo)
        // Nota: Requiere cache driver configurado en Laravel
        $this->info("Iniciando verificación de alertas operativas: " . now()->toDateTimeString());
        
        // IDs de usuarios a notificar
        $usuariosNotificar = [1, 504, 495]; 
        $reporte = ['salidas' => 0, 'notificadas_salida' => 0, 'retornos' => 0, 'notificadas_retorno' => 0];

        // --- CASO 1: SALIDAS RETRASADAS ---
        $this->comment("Verificando salidas programadas (30 min de tolerancia)...");
        
        $viajesRetrasados = Viaje::where('status', 'Programado')
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
                $tiempoRetraso = Carbon::now()->diffInMinutes($viaje->fecha_salida);
                 $mensaje = "ALERTA SALIDA: El viaje #{$viaje->id} a {$viaje->destino_ciudad} no tiene checklist tras {$tiempoRetraso} min.\n" .
                    "• *Vehículo:* {$viaje->vehiculo->flota} {$viaje->vehiculo->placa}\n" .
                    "• *Destino:* {$viaje->destino_ciudad}\n" .
                    "• *Fecha Salida:* {$viaje->fecha_salida->format('Y-m-d H:i')}\n" .
                    "• *Conductor:* {$viaje->chofer->persona->nombre}\n";

                $this->enviarAlerta($mensaje, $usuariosNotificar, $viaje);
                $reporte['notificadas_salida']++;

                $response = Http::asForm()
                            ->withoutVerifying() // Equivalente a CURLOPT_SSL_VERIFYPEER => 0
                            ->post($endpoint, [
                                'token'      => $tokenWA,
                                'to'         => config('services.whatsapp.group_operaciones'),
                                'body'       => $mensaje,
                                'priority'   => 1, // Importante si lo tenías en el script original
                                'referenceId' => '',
                            ]);
                if ($response->successful() && ($response->json()['sent'] ?? '') == 'true') {
                    $this->info("✅ notificacion enviada.");
                } else {
                    $this->error("❌ Error enviando " . $response->body());
                }
            }
        }

        $this->newLine();

        // --- CASO 3: RETORNOS SIN CERRAR ---
        $this->comment("Verificando retornos inconsistentes (1h de retraso)...");

        $viajesSinCerrar = Viaje::where('status', 'EN RUTA')
            ->whereHas('vehiculo', function($q) { $q->where('estatus', 1); })
            ->where('updated_at', '<=', now()->subHour())
            ->get();

        foreach ($viajesSinCerrar as $viaje) {
            $reporte['retornos']++;

            $hasCheckIn = Inspeccion::where(function($query) use ($viaje) {
                $query->where('viaje_id', $viaje->id)
                      ->orWhere(function($q) use ($viaje) {
                          $q->where('vehiculo_id', $viaje->vehiculo_id)
                            ->where('created_at', '>=', now()->subHour());
                      });
            })
            ->whereNotNull('respuesta_in')
            ->exists();

            if (!$hasCheckIn) {
                $tiempoRetraso = Carbon::now()->diffInMinutes($viaje->updated_at);
                 $mensaje = "ALERTA RETORNO: El vehículo {$viaje->vehiculo->flota} {$viaje->vehiculo->placa} Ingreso a la Sede pero el viaje #{$viaje->id} sigue 'EN RUTA' tras {$tiempoRetraso} min.\n" .
                    "• *Destino:* {$viaje->destino_ciudad}\n" .
                    "• *Fecha ingreso:* {$viaje->vehiculo->updated_at->format('Y-m-d H:i')}\n" .
                    "• *Conductor:* {$viaje->chofer->persona->nombre}\n";

                $this->warn(" > Enviando alerta: Vehículo {$viaje->vehiculo->flota} {$viaje->vehiculo->placa} con viaje #{$viaje->id} inconsistente.");
                $this->enviarAlerta($mensaje, $usuariosNotificar, $viaje);
                $reporte['notificadas_retorno']++;
            }
        }

        $this->renderResumen($reporte);
    }

    private function enviarAlerta($mensaje, $usuarios, $viaje)
    {
        // IMPORTANTE: Verifica si tu Service ya acepta un array de IDs 
        // para evitar el foreach aquí, que es lo que multiplica los envíos.
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
                ['Retornos Inconsistentes', $reporte['retornos'], $reporte['notificadas_retorno']],
            ]
        );
    }
}