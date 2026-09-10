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
use Illuminate\Support\Facades\DB;
use Throwable;

class VehiculoObserver
{
    protected $telegramService;
    protected $whatsappService;
    protected $inventarioService;

    protected static $viajesProcesados = [];

    public function __construct(
        TelegramNotificationService $telegramService, 
        WhatsappApiService $whatsappService, 
        LogisticaInventarioService $inventarioService
    ) {
        $this->telegramService = $telegramService;
        $this->whatsappService = $whatsappService;
        $this->inventarioService = $inventarioService;
    }

    public function updated(Vehiculo $vehiculo): void
    {
        if (!$vehiculo->wasChanged('estatus')) {
            return;
        }

        try {
            // CASO 1: CAMBIO A EN RUTA (2)
            if ((int)$vehiculo->estatus === 2) {
                $this->procesarSalidaEnRuta($vehiculo);
            } 
            // CASO 2: CAMBIO A DISPONIBLE / RETORNO (1)
            elseif ((int)$vehiculo->estatus === 1 && (int)$vehiculo->getOriginal('estatus') === 2) {
                $this->procesarRetornoSede($vehiculo);
            }
        } catch (Throwable $e) {
            Log::critical("Error fatal en VehiculoObserver para Unidad {$vehiculo->flota} (#{$vehiculo->id}): " . $e->getMessage(), [
                'exception' => $e,
                'vehiculo_id' => $vehiculo->id
            ]);
        }
    }

    /**
     * Procesa la salida de unidad y la vinculación con despachos activos.
     */
    private function procesarSalidaEnRuta(Vehiculo $vehiculo): void
    {
        Log::info("Procesando salida en ruta para Unidad {$vehiculo->flota} ({$vehiculo->placa}).");
        $this->sincronizarAcopladoSalida($vehiculo);

        // Ventana temporal de match (1 hora antes / 1 hora después)
        $ventanaInicio = now()->subMinutes(60);
        $ventanaFin    = now()->addMinutes(60);

        $viaje = Viaje::with(['chofer.persona'])
            ->where('vehiculo_id', $vehiculo->id)
            ->where('status', 'Programado')
            ->whereBetween('fecha_salida', [$ventanaInicio, $ventanaFin])
            ->first();

        // Si no coincide con un viaje, es un movimiento menor (Mantenimiento / Lavado / Prueba)
        if (!$viaje) {
            Log::info("Movimiento operativo menor (Sin viaje programado): Unidad {$vehiculo->flota} ({$vehiculo->placa}).");
            return;
        }

        if (isset(self::$viajesProcesados[$viaje->id])) {
            return;
        }
        self::$viajesProcesados[$viaje->id] = true;

        // Gestión de Inspección / Checklist
        $hasChecklist = $this->vincularOVerificarChecklist($viaje);

        // Transacción DB para asentar viaje y ledger de forma atómica
        DB::transaction(function () use ($viaje) {
            $viaje->status = 'EN RUTA';
            $viaje->fecha_salida_real = now();
            $viaje->save(); // 👈 Esto dispara automáticament ViajeObserver@updated que procesará el Ledger de forma segura
        });

        // Notificaciones desacopladas de la transacción DB
        $this->notificarSalida($vehiculo, $viaje, $hasChecklist);
    }

    /**
     * Procesa el retorno seguro a la sede.
     */
    private function procesarRetornoSede(Vehiculo $vehiculo): void
    {
        $this->liberarAcopladoRetorno($vehiculo);

        $viajeEnRuta = Viaje::with(['chofer.persona'])
            ->where('vehiculo_id', $vehiculo->id)
            ->where('status', 'EN RUTA')
            ->first();

        if (!$viajeEnRuta) {
            return;
        }

        DB::transaction(function () use ($viajeEnRuta) {
            $viajeEnRuta->status = 'COMPLETADO';
            $viajeEnRuta->fecha_llegada = now();
            $viajeEnRuta->save();
        });

        $this->notificarRetorno($vehiculo, $viajeEnRuta);
    }

    /**
     * Verifica e intenta asociar checklists huérfanos.
     */
    private function vincularOVerificarChecklist(Viaje $viaje): bool
    {
        $hasChecklist = Inspeccion::where('viaje_id', $viaje->id)
            ->whereNull('respuesta_in')
            ->exists();

        if ($hasChecklist) {
            return true;
        }

        $inspeccionHuerfana = Inspeccion::where('vehiculo_id', $viaje->vehiculo_id)
            ->where('created_at', '>=', now()->subMinutes(90))
            ->whereNull('respuesta_in')
            ->first();

        if ($inspeccionHuerfana) {
            $inspeccionHuerfana->viaje_id = $viaje->id;
            $inspeccionHuerfana->save();
            return true;
        }

        return false;
    }

    private function sincronizarAcopladoSalida(Vehiculo $vehiculo): void
    {
        if (!$vehiculo->acoplado_id) return;

        Vehiculo::where('id', $vehiculo->acoplado_id)->update(['estatus' => 2]);
    }

    private function liberarAcopladoRetorno(Vehiculo $vehiculo): void
    {
        if (!$vehiculo->acoplado_id) return;

        Vehiculo::where('id', $vehiculo->acoplado_id)->update([
            'estatus' => 1,
            'acoplado_id' => null
        ]);

        $vehiculo->acoplado_id = null;
        $vehiculo->saveQuietly();
    }

    private function notificarSalida(Vehiculo $vehiculo, Viaje $viaje, bool $hasChecklist): void
    {
        try {
            $choferNombre = $viaje->chofer->persona->nombre ?? 'N/A';

            if (!$hasChecklist) {
                $usuariosNotificar = config('servicios.notificaciones.supervisores_ids', [1, 2]);
                foreach ($usuariosNotificar as $userId) {
                    FcmNotificationService::enviarNotification(
                        "INCUMPLIMIENTO DE PROCESO",
                        "El vehículo {$vehiculo->flota} pasó a estado EN RUTA sin checklist para el viaje #{$viaje->id}.",
                        ['viaje_id' => $viaje->id, 'user_id' => $userId]
                    );
                }

                $message = "*⚠️ INCUMPLIMIENTO DE PROCESO (SALIDA SIN CHECKLIST) ⚠️*\n\n" .
                    "El vehículo *{$vehiculo->flota}* ({$vehiculo->placa}) pasó a estado EN RUTA sin completar el checklist de salida para el viaje #{$viaje->id}.\n\n" .
                    "• *Destino:* {$viaje->destino_ciudad}\n" .
                    "• *Chofer:* {$choferNombre}\n" .
                    "• *Fecha Programada:* {$viaje->fecha_salida->format('d/m/Y H:i A')}\n\n" .
                    "*Acción Requerida:* Exigir regularización inmediata al conductor.";
            } else {
                $message = "🚀 *SALIDA DETECTADA*:\n\n" .
                    "La Unidad *{$vehiculo->flota}* ({$vehiculo->placa}) va en ruta bajo la conducción de {$choferNombre}.\n\n" .
                    "• *Viaje:* #{$viaje->id}\n" .
                    "• *Destino:* {$viaje->destino_ciudad}";
            }

            $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
        } catch (Throwable $e) {
            Log::error("Error enviando notificaciones de salida para Viaje #{$viaje->id}: " . $e->getMessage());
        }
    }

    private function notificarRetorno(Vehiculo $vehiculo, Viaje $viajeEnRuta): void
    {
        try {
            $choferNombre = $viajeEnRuta->chofer->persona->nombre ?? 'N/A';
            $message = "*✅ RETORNO Y CIERRE DE VIAJE ✅*\n\n" .
                "El vehículo *{$vehiculo->flota}* ({$vehiculo->placa}) ha retornado a la Sede de forma segura.\n\n" .
                "• *Viaje Cerrado:* #{$viajeEnRuta->id}\n" .
                "• *Procedencia:* {$viajeEnRuta->destino_ciudad}\n" .
                "• *Chofer:* {$choferNombre}\n" .
                "• *Hora de Llegada:* {$viajeEnRuta->fecha_llegada->format('d/m/Y H:i A')}\n\n" .
                "*Estatus Actual:* El viaje ha sido consolidado como *Completado*. Realizar Checklist de Llegada para cerrar el proceso operativo.";

            $this->whatsappService->enviarMensaje($message, config('services.whatsapp.group_operaciones'));
        } catch (Throwable $e) {
            Log::error("Error enviando notificación de retorno para Viaje #{$viajeEnRuta->id}: " . $e->getMessage());
        }
    }
}