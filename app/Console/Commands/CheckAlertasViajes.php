<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Viaje;
use App\Models\Vehiculo;
use App\Models\Inspeccion; // Asumiendo este nombre de tabla/modelo
use App\Services\FcmNotificationService;
use Carbon\Carbon;

class CheckAlertasViajes extends Command
{
    protected $signature = 'viajes:check-alertas';
    protected $description = 'Evalúa retrasos en salidas y retornos de viajes';

    public function handle()
    {
        $ahora = Carbon::now();
        $usuariosNotificar = [1, 2, 5]; // IDs de usuarios específicos (Gerencia/Seguridad)

        // CASO 1: Programado + 30 min sin Checklist de Salida
        $viajesRetrasados = Viaje::where('status', 'Programado')
            ->where('fecha_salida', '<=', $ahora->subMinutes(30))
            ->get();

        foreach ($viajesRetrasados as $viaje) {
            $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)
                ->whereNull('respuesta_in') // Checklist de salida
                ->exists();

            if (!$hasChecklist) {
                $this->enviarAlerta("ALERTA SALIDA: El viaje #{$viaje->id} a {$viaje->destino_ciudad} no tiene checklist de salida tras 30 min de su hora programada.", $usuariosNotificar, $viaje);
            }
        }

        // CASO 3: Vehículo Disponible pero Viaje "EN RUTA" (1 hora sin Check-in)
        // Buscamos viajes que siguen marcados EN RUTA pero su vehículo ya fue liberado
        $viajesSinCerrar = Viaje::where('status', 'EN RUTA')
            ->whereHas('vehiculo', function($q) {
                $q->where('estatus', 1); // Disponible
            })
            ->where('updated_at', '<=', $ahora->subHour())
            ->get();

        foreach ($viajesSinCerrar as $viaje) {
            $hasCheckIn = Inspeccion::where('viaje_id', $viaje->id)
                ->whereNotNull('respuesta_in') // Checklist de entrada
                ->exists();

            if (!$hasCheckIn) {
                $this->enviarAlerta("ALERTA RETORNO: El vehículo {$viaje->vehiculo->flota} está disponible pero el viaje #{$viaje->id} sigue 'EN RUTA' sin checklist de entrada (1h de retraso).", $usuariosNotificar, $viaje);
            }
        }
    }

    private function enviarAlerta($mensaje, $usuarios, $viaje)
    {
        foreach ($usuarios as $userId) {
            FcmNotificationService::enviarNotification(
                "Seguridad Operativa",
                $mensaje,
                ['viaje_id' => $viaje->id,
                'tipo' => 'ALERTA_OPERATIVA'],
                $userId
            );
        }
    }
}