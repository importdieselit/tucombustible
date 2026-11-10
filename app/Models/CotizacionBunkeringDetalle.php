<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionBunkeringDetalle extends Model
{
    use HasFactory;

    protected $table = 'cotizacion_bunkering_detalles';

    protected $fillable = [
        'cotizacion_id',
        'tipo_producto',
        'unidad',               // NUEVO: MT, LT, GL, DR
        'cantidad_solicitada',  // NUEVO: El valor exacto ingresado en esa unidad
        'toneladas_metricas',
        'volumen_litros',
        'precio_unitario_tm',
        'subtotal_producto',
        'costo_delivery',
        'total_renglon',
    ];

    /**
     * Calcula el Delivery Fee según el tipo de producto y los campos ingresados.
     */
    public static function calcularDeliveryRenglon(string $tipoProducto, float $tm, ?float $deliveryTm = null, ?float $deliveryCompleto = null): float
    {
        $tipo = strtoupper(trim($tipoProducto));

        // Si es VLSFO, HSFO u otro producto abierto
        if (in_array($tipo, ['VLSFO', 'HSFO'])) {
            if (!is_null($deliveryCompleto) && $deliveryCompleto > 0) {
                return (float) $deliveryCompleto;
            }
            if (!is_null($deliveryTm) && $deliveryTm > 0) {
                return $tm * (float) $deliveryTm;
            }
            return 0.00;
        }

        // Regla automática para FUEL OIL 380, MGO, etc.
        if ($tm <= 0) return 0.00;
        if ($tm < 890) {
            return 8000.00;
        }
        return $tm * 9.00;
    }
}