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
                            ->where('created_at', '>=', now()->subMinutes(30));
                      });
            })
            ->whereNull('respuesta_in')
            ->exists();

            if (!$hasChecklist) {
                $this->warn(" > Enviando alerta: Viaje #{$viaje->id}");
                $this->enviarAlerta("ALERTA SALIDA: El viaje #{$viaje->id} a {$viaje->destino_ciudad} no tiene checklist tras 30 min.", $usuariosNotificar, $viaje);
                $reporte['notificadas_salida']++;
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
                $this->warn(" > Enviando alerta: Vehículo {$viaje->vehiculo->flota} inconsistente.");
                $this->enviarAlerta("ALERTA RETORNO: El vehículo {$viaje->vehiculo->flota} está libre pero el viaje #{$viaje->id} sigue 'EN RUTA'.", $usuariosNotificar, $viaje);
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