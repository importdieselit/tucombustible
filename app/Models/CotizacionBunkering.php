<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\CotizacionBunkeringDetalle;

class CotizacionBunkering extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones_bunkering';

    protected $fillable = [
        'numero_pedido',
        'nombre_buque',
        'bandera_buque',
        'imo_buque',
        'pagador',
        'punto_entrega',
        'vendedor',
        'metodo_despacho',
        'centro_suministro',
        'agencia_naviera',
        'fecha_nominacion',
        'fecha_entrega',
        'observaciones',
        'subtotal_general',
        'costo_delivery',
        'total_general',
    ];

    protected $casts = [
        'fecha_nominacion' => 'date',
        'fecha_entrega' => 'date',
        'subtotal_general' => 'float',
        'costo_delivery' => 'float',
        'total_general' => 'float',
    ];

    public function detalles()
    {
        return $this->hasMany(CotizacionBunkeringDetalle::class, 'cotizacion_id');
    }

    /**
     * Calcula el Delivery Fee según las reglas (< 890 TM = $8000 | >= 890 TM = 9 * TM)
     */
    public static function calcularCostoDelivery(string $tipoProducto, float $tm, ?float $deliveryTm = null, ?float $deliveryCompleto = null): float
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
    

    public static function generarNumeroPedido(): string
    {
        $prefijoFecha = now()->format('ymd');
        $ultimoPedido = self::where('numero_pedido', 'LIKE',$prefijoFecha . '%')
            ->orderBy('numero_pedido', 'desc')
            ->first();

        $secuencial = $ultimoPedido ? intval(substr($ultimoPedido->numero_pedido, -3)) + 1 : 1;
        return $prefijoFecha . str_pad($secuencial, 3, '0', STR_PAD_LEFT);
    }
}