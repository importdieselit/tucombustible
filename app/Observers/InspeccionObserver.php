<?php

namespace App\Observers;

use App\Models\Inspeccion;
use App\Models\Vehiculo;
use App\Models\Viaje;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Services\TelegramNotificationService;
use App\Services\WhatsappApiService;
use App\Services\FcmNotificationService;



class InspeccionObserver
{
    /**
     * Handle the Inspeccion "created" event.
     *
     * @param  \App\Models\Inspeccion  $inspeccion
     * @return void
     */
    public function created(Inspeccion $inspeccion)
    {
        $user = auth()->user();
        $nombre = $user->persona->nombre ?? 'Usuario'.$user->id;
        $vehiculo = Vehiculo::find($inspeccion->vehiculo_id);
        $baseUrl = rtrim(config('services.whatsapp.url'), '/');
        $tokenWA = config('services.whatsapp.key');
        $endpoint = "{$baseUrl}/messages/chat?token={$tokenWA}";
        
        if ($vehiculo) {
            // Pasar vehículo a estatus 2
            if ($vehiculo->estatus != 2) {
                $vehiculo->estatus = 2;
                $vehiculo->save();
            }
            
            // Buscar viaje programado
            $viajesProgramados = Viaje::where('vehiculo_id', $vehiculo->id)
                                      ->where('status', 'Programado')->orderBy('fecha_salida', 'asc')
                                      ->get();
            if ($viajesProgramados->isNotEmpty()) {
                if (!$inspeccion->viaje_id) {
                    $inspeccion->viaje_id = $viajesProgramados->first()->id;
                    $inspeccion->save();
                }

                $mensaje =" CHECKOUT: {$nombre} ha registrado el checklist de salida para la unidad {$vehiculo->flota} - {$vehiculo->placa}. Salida #{$viajesProgramados->first()->id} a {$viajesProgramados->first()->destino_ciudad}.";
                $response = Http::asForm()
                            ->withoutVerifying() // Equivalente a CURLOPT_SSL_VERIFYPEER => 0
                            ->post($endpoint, [
                                'token'      => $tokenWA,
                                'to'         => config('services.whatsapp.group_operaciones'),
                                'body'       => $mensaje,
                                'priority'   => 1, // Importante si lo tenías en el script original
                                'referenceId' => '',
                            ]);
                
                $viaje = $viajesProgramados->first();
                $viaje->status = 'EN RUTA';
                $viaje->save(); // ¡Esto disparará el ViajeObserver automáticamente!
            }
        }
    }

    /**
     * Handle the Inspeccion "updated" event.
     *
     * @param  \App\Models\Inspeccion  $inspeccion
     * @return void
     */
    public function updated(Inspeccion $inspeccion)
    {
        $user = auth()->user();
        $nombre = $user->persona->nombre ?? 'Usuario'.$user->id;

        $baseUrl = rtrim(config('services.whatsapp.url'), '/');
        $tokenWA = config('services.whatsapp.key');
        $endpoint = "{$baseUrl}/messages/chat?token={$tokenWA}";
        // Verificamos si respuesta_in cambió de Null a algo con contenido
        $respuestaInCambio = $inspeccion->isDirty('respuesta_in');
        $eraNull = is_null($inspeccion->getOriginal('respuesta_in'));
        $ahoraTieneDatos = !is_null($inspeccion->respuesta_in);

        if ($respuestaInCambio && $eraNull && $ahoraTieneDatos) {
            
            $vehiculo = Vehiculo::find($inspeccion->vehiculo_id);
            if ($vehiculo) {
                // Pasar vehículo a estatus 1
                if ($vehiculo->estatus != 1) {
                    $vehiculo->estatus = 1;
                    $vehiculo->save();
                }

                // Buscar viaje en ruta
                $viajesEnRuta =Viaje::where('vehiculo_id', $vehiculo->id)
                                     ->where('status', 'EN RUTA')
                                     ->update(['status' => 'COMPLETADO']);
                                    // ->get();
                $mensaje = "CHECKIN: {$nombre} ha registrado el checklist de llegada para la unidad {$vehiculo->flota} - {$vehiculo->placa}. El viaje #{$inspeccion->viaje_id} ha sido marcado como COMPLETADO.";
                $response = Http::asForm()
                            ->withoutVerifying() // Equivalente a CURLOPT_SSL_VERIFYPEER => 0
                            ->post($endpoint, [
                                'token'      => $tokenWA,
                                'to'         => config('services.whatsapp.group_operaciones'),
                                'body'       => $mensaje,
                                'priority'   => 1, // Importante si lo tenías en el script original
                                'referenceId' => '',
                            ]);
                // Si hay exactamente 1, pasarlo a COMPLETADO
                // if ($viajesEnRuta->count() === 1) {
                //     $viaje = $viajesEnRuta->first();
                //     $viaje->status = 'COMPLETADO';
                //     $viaje->save(); // ¡Esto disparará el ViajeObserver automáticamente!
                // }
            }
        }
    }

    /**
     * Handle the Inspeccion "deleted" event.
     *
     * @param  \App\Models\Inspeccion  $inspeccion
     * @return void
     */
    public function deleted(Inspeccion $inspeccion)
    {
        //
    }

    /**
     * Handle the Inspeccion "restored" event.
     *
     * @param  \App\Models\Inspeccion  $inspeccion
     * @return void
     */
    public function restored(Inspeccion $inspeccion)
    {
        //
    }

    /**
     * Handle the Inspeccion "force deleted" event.
     *
     * @param  \App\Models\Inspeccion  $inspeccion
     * @return void
     */
    public function forceDeleted(Inspeccion $inspeccion)
    {
        //
    }
}
