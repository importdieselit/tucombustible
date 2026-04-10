<?php

namespace App\Observers;

use App\Models\Inspeccion;
use App\Models\Vehiculo;
use App\Models\Viaje;

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
        $vehiculo = Vehiculo::find($inspeccion->vehiculo_id);
        
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

            $viaje = $viajesProgramados->first();
            $viaje->status = 'EN RUTA';
            $viaje->save(); // ¡Esto disparará el ViajeObserver automáticamente!
            
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
                //$viajesEnRuta =
                 Viaje::where('vehiculo_id', $vehiculo->id)
                                     ->where('status', 'EN RUTA')
                                     ->update(['status' => 'COMPLETADO']);
                                    // ->get();


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
