<?php

namespace App\Observers;

use App\Models\Viaje;
use App\Models\Vehiculo;
use App\Models\Chofer;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\DB;

class ViajeObserver
{
    /**
     * Handle the Viaje "created" event.
     *
     * @param  \App\Models\Viaje  $viaje
     * @return void
     */
    public function created(Viaje $viaje)
    {
        $vehiculo = Vehiculo::find($viaje->vehiculo_id);
        
        if ($vehiculo) {
            $vehiculo->chofer_id = $viaje->chofer_id;

            // Corrección lógica: Solo hacer esto si la cisterna NO es nula
            if (!is_null($viaje->cisterna)) {
                // Desacoplar esta cisterna de cualquier otro vehículo
                Vehiculo::where('acoplado_id', $viaje->cisterna)->update(['acoplado_id' => null]);
                // Acoplarla al vehículo actual
                $vehiculo->acoplado_id = $viaje->cisterna;
            }
            
            $vehiculo->save();
        }
        
        
    }

    /**
     * Handle the Viaje "updated" event.
     *
     * @param  \App\Models\Viaje  $viaje
     * @return void
     */
    public function updated(Viaje $viaje)
    {
        // isDirty() verifica si un campo específico cambió en esta actualización
        if ($viaje->isDirty('status')) {
            $nuevoStatus = $viaje->status;
            $viejoStatus = $viaje->getOriginal('status'); // El valor antes del update

            if ($viejoStatus === 'Programado' && $nuevoStatus === 'EN RUTA') {
              $this->actualizarEstatusFlota($viaje, 2);
            } elseif ($nuevoStatus === 'COMPLETADO') {
              $this->actualizarEstatusFlota($viaje, 1);
              Vehiculo::where('id', $viaje->vehiculo_id)->update(['acoplado_id' => null]);
            }
        }
    }


    private function actualizarEstatusFlota(Viaje $viaje, int $estatusDestino)
    {
        // 1. Actualizar el Chuto/Vehículo principal
        $vehiculo = Vehiculo::find($viaje->vehiculo_id);
        if ($vehiculo && $vehiculo->estatus != $estatusDestino) {
            $vehiculo->estatus = $estatusDestino;
            $vehiculo->save();
        }

        // 2. Actualizar la Cisterna (Asumiendo que el ID de cisterna guarda otro Vehiculo)
        if (!is_null($viaje->cisterna)) {
            $cisterna = Vehiculo::find($viaje->cisterna);
            if ($cisterna && $cisterna->estatus != $estatusDestino) {
                $cisterna->estatus = $estatusDestino;
                $cisterna->save();
            }
        }
    }

    /**
     * Handle the Viaje "deleted" event.
     *
     * @param  \App\Models\Viaje  $viaje
     * @return void
     */
    public function deleted(Viaje $viaje)
    {
        //
    }

    /**
     * Handle the Viaje "restored" event.
     *
     * @param  \App\Models\Viaje  $viaje
     * @return void
     */
    public function restored(Viaje $viaje)
    {
        //
    }

    /**
     * Handle the Viaje "force deleted" event.
     *
     * @param  \App\Models\Viaje  $viaje
     * @return void
     */
    public function forceDeleted(Viaje $viaje)
    {
        //
    }
}
