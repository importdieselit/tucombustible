<?php

namespace App\Observers;

use App\Models\Viaje;
use App\Models\Vehiculo;
use App\Services\LogisticaInventarioService;
use Illuminate\Support\Facades\DB;

class ViajeObserver
{
    protected $inventarioService;

    public function __construct(LogisticaInventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Handle the Viaje "created" event.
     */
    public function created(Viaje $viaje)
    {
        $vehiculo = Vehiculo::find($viaje->vehiculo_id);
        
        if ($vehiculo) {
            $vehiculo->chofer_id = $viaje->chofer_id;

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
     */
    public function updated(Viaje $viaje)
    {
        // Verificamos si el campo status cambió en esta actualización
        if ($viaje->isDirty('status')) {
            $nuevoStatus = $viaje->status;
            $viejoStatus = $viaje->getOriginal('status');

            // 1. TRANSICIÓN A "EN RUTA" (Salida de Planta)
            if (strtoupper($viejoStatus) === 'PROGRAMADO' && $nuevoStatus === 'EN RUTA') {
                
                $this->actualizarEstatusFlota($viaje, 2);

                // ⚡ LEDGER AUTOMÁTICO: Libera compromiso comercial y descuenta Stock Físico de la Sede
                $this->inventarioService->registrarSalidaFisicaDespacho($viaje);

            // 2. TRANSICIÓN A "COMPLETADO" (Llegada / Descarga)
            } elseif ($nuevoStatus === 'COMPLETADO') {
                
                Vehiculo::where('id', $viaje->vehiculo_id)->update(['acoplado_id' => null]);
                $this->actualizarEstatusFlota($viaje, 1);

                // ⚡ LEDGER AUTOMÁTICO: Si es una Compra (Tipo 4), suma los litros físicos reales
                if ((int) $viaje->tipo_planificacion === 4) {
                    $this->inventarioService->registrarEntradaCompra($viaje);
                    
                    // Actualizamos el estatus en la tabla auxiliar de compras
                    DB::table('compras_combustible')
                        ->where('viaje_id', $viaje->id)
                        ->update(['estatus' => 'COMPLETADO']);
                }
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

        // 2. Actualizar la Cisterna
        if (!is_null($viaje->cisterna)) {
            $cisterna = Vehiculo::find($viaje->cisterna);
            if ($cisterna && $cisterna->estatus != $estatusDestino) {
                $cisterna->estatus = $estatusDestino;
                $cisterna->save();
            }
        }
    }
}