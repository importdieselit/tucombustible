<?php 
namespace App\Services;

use App\Models\CompraDetalle;
use App\Models\SuministroCompra;
use App\Models\SuministroCompraDetalle;
use Illuminate\Support\Facades\DB;

class ComprasService
{
    public function registrarItemCompra($compraId, $repuestoId, $cantidad, $precio)
    {
        return DB::transaction(function () use ($compraId, $repuestoId, $cantidad, $precio) {
            
            // 1. Buscar si hay una solicitud pendiente para este repuesto (FIFO: la más antigua primero)
            $solicitudPendiente = SuministroCompraDetalle::where('repuesto_id', $repuestoId)
                ->whereColumn('cantidad_recibida', '<', 'cantidad_solicitada')
                ->whereHas('cabecera', function($q) {
                    $q->whereIn('estatus', ['pendiente', 'parcial']);
                })
                ->orderBy('created_at', 'asc')
                ->first();

            // 2. Registrar la compra vinculándola o no
            $detalle = CompraDetalle::create([
                'compra_id' => $compraId,
                'repuesto_id' => $repuestoId,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'solicitud_detalle_id' => $solicitudPendiente ? $solicitudPendiente->id : null
            ]);

            // 3. Si hubo vínculo, actualizar el estatus de la solicitud
            if ($solicitudPendiente) {
                $nuevaCantidadRecibida = $solicitudPendiente->cantidad_recibida + $cantidad;
                $solicitudPendiente->update([
                    'cantidad_recibida' => $nuevaCantidadRecibida
                ]);

                // Actualizar estatus de la cabecera si todo se completó
                $this->actualizarEstatusCabecera($solicitudPendiente->solicitud_compra_id);
            }

            // 4. Actualizar Stock en Almacén
            // (Aquí llamarías a tu método de AlmacenService para sumar el stock)

            return $detalle;
        });
    }

    private function actualizarEstatusCabecera($solicitudId)
    {
        $detalles = SuministroCompraDetalle::where('solicitud_compra_id', $solicitudId)->get();
        $totalSolicitado = $detalles->sum('cantidad_solicitada');
        $totalRecibido = $detalles->sum('cantidad_recibida');

        $cabecera = SuministroCompra::find($solicitudId);
        
        if ($totalRecibido >= $totalSolicitado) {
            $cabecera->update(['estatus' => 'completada']);
        } elseif ($totalRecibido > 0) {
            $cabecera->update(['estatus' => 'parcial']);
        }
    }
}