<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Viaje;
use App\Models\Inspeccion;
use App\Services\FcmNotificationService;
use Carbon\Carbon;

class CheckAlertasViajes extends Command
{
    protected $signature = 'viajes:check-alertas';
    protected $description = 'Evalúa retrasos en salidas y retornos de viajes con reporte en consola';

    public function handle()
    {
        $this->info("Iniciando verificación de alertas operativas: " . now()->toDateTimeString());
        
        $usuariosNotificar = [1, 3, 9, 504, 495];
        $reporte = ['salidas' => 0, 'notificadas_salida' => 0, 'retornos' => 0, 'notificadas_retorno' => 0];

        // --- CASO 1: SALIDAS RETRASADAS ---
        $this->comment("Verificando salidas programadas (30 min de tolerancia)...");
        
        $viajesRetrasados = Viaje::where('status', 'Programado')
            ->where('fecha_salida', '<=', now()->subMinutes(30))
            ->get();

        $this->info("Unidades en mora de salida encontrada: " . $viajesRetrasados->count());

        foreach ($viajesRetrasados as $viaje) {
            $reporte['salidas']++;
            
            // Verificamos si existe inspección ligada al viaje o al vehículo recientemente
            $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)
                ->whereNull('respuesta_in')
                ->exists();

            if (!$hasChecklist) {
                $hasChecklist2 = Inspeccion::where('vehiculo_id', $viaje->vehiculo_id)
                    ->whereNull('respuesta_in')
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->exists();

                if (!$hasChecklist2) {
                    $this->warn(" > Notificando: Viaje #{$viaje->id} - Sin Checklist de Salida.");
                    $this->enviarAlerta("ALERTA SALIDA: El viaje #{$viaje->id} a {$viaje->destino_ciudad} no tiene checklist de salida tras 30 min.", $usuariosNotificar, $viaje);
                    $reporte['notificadas_salida']++;
                } else {
                    $this->line("   - Viaje #{$viaje->id}: Se encontró inspección reciente de vehículo, omitiendo alerta.");
                }
            }
        }

        $this->newLine();

        // --- CASO 3: RETORNOS SIN CERRAR ---
        $this->comment("Verificando viajes 'EN RUTA' con vehículo disponible (1h de retraso)...");

        $viajesSinCerrar = Viaje::where('status', 'EN RUTA')
            ->whereHas('vehiculo', function($q) {
                $q->where('estatus', 1);
            })
            ->where('updated_at', '<=', now()->subHour())
            ->get();

        $this->info("Unidades con inconsistencia de retorno encontradas: " . $viajesSinCerrar->count());

        foreach ($viajesSinCerrar as $viaje) {
            $reporte['retornos']++;

            $hasCheckIn = Inspeccion::where('viaje_id', $viaje->id)
                ->whereNotNull('respuesta_in')
                ->exists();

            if (!$hasCheckIn) {
                $hasCheckIn2 = Inspeccion::where('vehiculo_id', $viaje->vehiculo_id)
                    ->whereNotNull('respuesta_in')
                    ->where('created_at', '>=', now()->subHour())
                    ->exists();

                if (!$hasCheckIn2) {
                    $this->warn(" > Notificando: Viaje #{$viaje->id} - Vehículo {$viaje->vehiculo->flota} liberado pero viaje activo.");
                    $this->enviarAlerta("ALERTA RETORNO: El vehículo {$viaje->vehiculo->flota} está disponible pero el viaje #{$viaje->id} sigue 'EN RUTA'.", $usuariosNotificar, $viaje);
                    $reporte['notificadas_retorno']++;
                } else {
                    $this->line("   - Viaje #{$viaje->id}: Inspección de entrada detectada, omitiendo alerta.");
                }
            }
        }

        // --- RESUMEN FINAL ---
        $this->newLine();
        $this->info("PROCESO FINALIZADO");
        $this->table(
            ['Categoría', 'Encontrados', 'Notificaciones Enviadas'],
            [
                ['Salidas Retrasadas', $reporte['salidas'], $reporte['notificadas_salida']],
                ['Retornos Inconsistentes', $reporte['retornos'], $reporte['notificadas_retorno']],
            ]
        );
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
}