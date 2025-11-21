<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuministroCompra extends Model
{
    use HasFactory;

    protected $table = 'suministros_compras';

    protected $fillable = [
        'orden_id',
        'usuario_solicitante_id',
        'usuario_aprobador_id',
        'referencia_compra',
        'costo_total',
        'foto_factura',
        'estatus',
    ];

    /**
     * Relación con la Orden de Trabajo.
     */
    public function orden()
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    /**
     * Relación con los detalles/ítems solicitados.
     */
    public function detalles()
    {
        return $this->hasMany(SuministroCompraDetalle::class,'suministro_compra_id');
    }

    /**
     * Relación con el usuario que solicitó.
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_solicitante_id');
    }
    
    // ... Otras relaciones
}
