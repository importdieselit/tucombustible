<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\Alerta;
use App\Models\GascoCupoMensual;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

class ClienteObserver
{
    public function updated(Cliente $cliente)
    {
        // Solo actuamos si el campo 'disponible' en la tabla 'clientes' cambió
        if ($cliente->isDirty('disponible')) {
            
            $newDisponible = $cliente->disponible;
            $oldDisponible = $cliente->getOriginal('disponible');

            // 1. Buscamos el Cupo Total (litros_autorizados) de este mes en GASCO
            $cupoMensual = GascoCupoMensual::where('cliente_id', $cliente->id)
                ->where('mes', now()->month)
                ->where('anio', now()->year)
                ->first();

            // 2. Verificamos que exista el cupo y no sea cero para evitar el crash
            if ($cupoMensual && $cupoMensual->litros_autorizados > 0) {
                
                $totalAutorizado = $cupoMensual->litros_autorizados;
                $porcentajeRestante = ($newDisponible / $totalAutorizado) * 100;

                // 3. Si el disponible bajó y ahora es menos del 10% del total mensual
                if ($newDisponible < $oldDisponible && $porcentajeRestante < 10) {
                    
                    try {
                        // Enviar Notificación Push
                        FcmNotificationService::sendBajoDisponibleNotification(
                            $cliente,
                            $newDisponible,
                            $totalAutorizado
                        );

                        // Registrar Alerta en DB
                        Alerta::create([
                            'id_usuario'  => $cliente->user->id ?? 1,
                            'id_rel'      => $cliente->id,
                            'fecha'       => now(),
                            'observacion' => "¡Alerta de consumo! Tu disponible actual es de " . number_format($newDisponible, 0) . "L, lo cual representa menos del 10% de tu cupo GASCO mensual (" . number_format($totalAutorizado, 0) . "L).",
                            'estatus'     => 0,
                            'accion'      => "/mi-perfil",
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error("Error enviando alerta de disponible bajo: " . $e->getMessage());
                    }
                }
            }
        }
    }
}