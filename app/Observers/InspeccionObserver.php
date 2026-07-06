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
        $data = json_decode($inspeccion->respuesta_json, true);
        $observacion=false;
        $check=0;
        // Recorremos las secciones e items buscando la etiqueta específica
       foreach ($data['sections'] as $section) {
            foreach ($section['items'] as $item) {
                $label = $item['label'] ?? '';

                if ($label === 'Observaciones Generales') {
                    $observacion = isset($item['value']) ? trim($item['value']) : false;
                    $check++;
                }

                if ($label === 'Seleccione Ruta a Cubrir' && !empty($item['value'])) {
                    if (preg_match('/ID-(\d+)/', $item['value'], $matches)) {
                        $inspeccion->viaje_id = $matches[1];
                    }
                    $check++;
                }

                if ($check === 2) break 2;
            }
        }
        
        if ($vehiculo) {
            // Pasar vehículo a estatus 2
            if ($vehiculo->estatus != 2) {
                $vehiculo->estatus = 2;
                $vehiculo->save();
            }
            
            $viaje = $inspeccion->viaje_id ? Viaje::find($inspeccion->viaje_id) : null;

            // Prioridad B (Failsafe): Si no vino ID en el form, o era un ID viejo/borrado, rescatamos el primero en fila
            if (!$viaje) {
                $viaje = Viaje::where('vehiculo_id', $vehiculo->id)
                            ->where('status', 'Programado')
                            ->orderBy('fecha_salida', 'asc')
                            ->first(); // Eficiencia SQL: LIMIT 1

                // Si el failsafe tuvo éxito, aseguramos la trazabilidad guardando el ID en la inspección
                if ($viaje) {
                    $inspeccion->viaje_id = $viaje->id;
                    $inspeccion->save(); 
                }
            }
            if ($viaje) {
                // BUG CORREGIDO: Ahora el mensaje usa los datos reales del objeto $viaje resuelto
                $mensaje = " CHECKOUT: {$nombre} ha registrado el checklist de salida para la unidad {$vehiculo->flota} - {$vehiculo->placa}. Salida #{$viaje->id} a {$viaje->destino_ciudad}.";
                if ($observacion) {
                    $mensaje .= " Observación: {$observacion}";
                }
                $response = Http::asForm()
                            ->withoutVerifying() // Equivalente a CURLOPT_SSL_VERIFYPEER => 0
                            ->post($endpoint, [
                                'token'      => $tokenWA,
                                'to'         => config('services.whatsapp.group_operaciones'),
                                'body'       => $mensaje,
                                'priority'   => 1, // Importante si lo tenías en el script original
                                'referenceId' => '',
                            ]);
                
                if ($viaje && $viaje->status != 'EN RUTA') {
                        $viaje->status = 'EN RUTA';
                        $viaje->save(); // ¡Esto disparará el ViajeObserver automáticamente!
                }
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
        $respuestaInCambio = $inspeccion->isDirty('respuesta_in')   ;
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
            $viaje = Viaje::find($inspeccion->viaje_id) ?? 
                     Viaje::where('vehiculo_id', $vehiculo->id)->where('status', 'EN RUTA')->first();

            if ($viaje) {
                $viaje->update(['status' => 'COMPLETADO']); // Dispara tu Observer perfectamente
            }
                $data = json_decode($inspeccion->respuesta_in, true);
                $observacion=false;
                // Recorremos las secciones e items buscando la etiqueta específica
                foreach ($data['sections'] as $section) {
                    foreach ($section['items'] as $item) {
                        if (isset($item['label']) && $item['label'] === 'Observaciones Generales') {
                            // Retornamos el valor limpio de espacios
                        $observacion = isset($item['value']) ? trim($item['value']) : false;
                        break 2; // Salimos de ambos bucles una vez encontrada la etiqueta
                        }
                    }
                }


                $mensaje = "CHECKIN: {$nombre} ha registrado el checklist de llegada para la unidad {$vehiculo->flota} - {$vehiculo->placa}. El viaje #{$inspeccion->viaje_id} ha sido marcado como COMPLETADO.";
                if ($observacion) {
                    $mensaje .= " Observación: {$observacion}";
                }
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
