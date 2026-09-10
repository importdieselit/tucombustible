<?php

namespace App\Observers;

use App\Models\Viaje;
use App\Models\Vehiculo;
use App\Services\LogisticaInventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\ViajeCreadoNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ViajeObserver
{
    protected LogisticaInventarioService $inventarioService;

    public function __construct(LogisticaInventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Disparado al crear un nuevo viaje.
     */
    public function created(Viaje $viaje): void
    {
        try {
            DB::transaction(function () use ($viaje) {
                $vehiculo = Vehiculo::lockForUpdate()->find($viaje->vehiculo_id);
                
                if ($vehiculo) {
                    $vehiculo->chofer_id = $viaje->chofer_id;

                    // Desacople preventivo y nuevo acople de cisterna
                    if (!is_null($viaje->cisterna)) {
                        Vehiculo::where('acoplado_id', $viaje->cisterna)
                            ->where('id', '!=', $vehiculo->id)
                            ->update(['acoplado_id' => null]);

                        $vehiculo->acoplado_id = $viaje->cisterna;
                    }
                    
                    $vehiculo->save();
                }
            });

            // Envío de notificaciones fuera de la transacción DB
            $this->notificarAsignacionViaje($viaje);

        } catch (Throwable $e) {
            Log::error("Error en ViajeObserver@created para Viaje #{$viaje->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    /**
     * Disparado al actualizar un viaje existente.
     */
    public function updated(Viaje $viaje): void
    {
        // Evaluación correcta post-persistencia
        if (!$viaje->wasChanged('status')) {
            return;
        }

        $viejoStatus = strtoupper((string) $viaje->getOriginal('status'));
        $nuevoStatus = strtoupper((string) $viaje->status);

        try {
            // 1. TRANSICIÓN A "EN RUTA" (Salida Confirmada)
            if ($viejoStatus === 'PROGRAMADO' && $nuevoStatus === 'EN RUTA') {
                
                DB::transaction(function () use ($viaje) {
                    // Sincronizar estatus físico de la unidad y acoplado
                    $this->actualizarEstatusFlota($viaje, 2);

                    // LEDGER AUTOMÁTICO: Única fuente de verdad para el descuento de stock
                    $this->inventarioService->registrarSalidaFisicaDespacho($viaje);
                });

            // 2. TRANSICIÓN A "COMPLETADO" (Llegada / Recepción)
            } elseif ($nuevoStatus === 'COMPLETADO') {
                
                DB::transaction(function () use ($viaje) {
                    // Liberar acople y cambiar estatus a Disponible (1)
                    Vehiculo::where('id', $viaje->vehiculo_id)->update(['acoplado_id' => null]);
                    $this->actualizarEstatusFlota($viaje, 1);

                    // Si es una Compra de Combustible (Tipo 4), ingresar stock al Ledger
                    if ((int) $viaje->tipo_planificacion === 4) {
                        $this->inventarioService->registrarEntradaCompra($viaje);
                        
                        DB::table('compras_combustible')
                            ->where('viaje_id', $viaje->id)
                            ->update([
                                'estatus' => 'COMPLETADO',
                                'updated_at' => now()
                            ]);
                    }
                });
            }
        } catch (Throwable $e) {
            Log::critical("Error procesando transición de estado para Viaje #{$viaje->id} ({$viejoStatus} -> {$nuevoStatus}): " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    /**
     * Actualiza el estatus operativo del Chuto y su Cisterna de forma atómica.
     */
    private function actualizarEstatusFlota(Viaje $viaje, int $estatusDestino): void
    {
        // Actualizar vehículo principal
        Vehiculo::where('id', $viaje->vehiculo_id)->update(['estatus' => $estatusDestino]);

        // Actualizar cisterna si aplica
        if (!is_null($viaje->cisterna)) {
            Vehiculo::where('id', $viaje->cisterna)->update(['estatus' => $estatusDestino]);
        }
    }

    /**
     * Notificación multicanal aislada de fallos de red.
     */
    private function notificarAsignacionViaje(Viaje $viaje): void
    {
        try {
            $viaje->loadMissing(['chofer', 'ayudante_chofer', 'vehiculo', 'cisternaAcoplada']);

            $destinatarios = collect();

            if ($viaje->chofer) {
                $destinatarios->push($viaje->chofer);
            }
            
            if ($viaje->ayudante_chofer) {
                $destinatarios->push($viaje->ayudante_chofer);
            }

            if ($destinatarios->isNotEmpty()) {
                Notification::send($destinatarios, new ViajeCreadoNotification($viaje));
            }
        } catch (Throwable $e) {
            Log::error("Fallo al enviar notificación de Viaje Creado #{$viaje->id}: " . $e->getMessage());
        }
    }
}