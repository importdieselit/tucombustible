<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuministroCompraDetalle extends Model
{
    use HasFactory;

    protected $table = 'suministros_compras_detalles';

    protected $fillable = [
        'suministro_compra_id',
        'inventario_id',
        'descripcion',
        'cantidad_solicitada',
        'costo_unitario_aprobado',
        'cantidad_aprobada',
    ];

    /**
     * Relación con la cabecera de la Orden de Compra.
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(SuministroCompra::class);
    }

    /**
     * Relación con el ítem de Inventario (si aplica).
     */
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
}
