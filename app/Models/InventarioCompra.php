<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class InventarioCompra extends Model
{
    protected $table = 'inventario_compras';
    protected $primaryKey = 'id_inventario_compra';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'estatus',
        'id_usuario',
        'nro_orden',
        'destino',
        'id_auto',
        'id_proveedor',
        'observacion',
        'fecha_in',
        'compra', // Este campo es LONGTEXT, considera su manejo
        'anulacion',
        'compra_total',
        'proveedor_contacto',
        'fecha_factura',
        'factura_referencia',
        'compra_div',
        'id_moneda',
        'tasa',
        'anulada',
        'fecha_anulacion',
        'anulada_por',
        'compra_anulado',
        'nro_anulacion',
    ];

    protected $casts = [
        'estatus' => 'integer',
        'id_usuario' => 'integer',
        'nro_orden' => 'integer',
        'destino' => 'integer',
        'id_auto' => 'integer',
        'id_proveedor' => 'integer',
        'fecha_factura' => 'date',
        'compra_total' => 'decimal:2',
        'compra_div' => 'decimal:2',
        'id_moneda' => 'integer',
        'tasa' => 'decimal:2',
        'anulada' => 'boolean',
        'fecha_anulacion' => 'date',
        'anulada_por' => 'integer',
        'compra_anulado' => 'boolean',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function auto()
    {
        return $this->belongsTo(Vehiculo::class, 'id_auto', 'id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'id_moneda', 'id');
    }

    public function anuladaPorUsuario()
    {
        return $this->belongsTo(User::class, 'anulada_por', 'id');
    }
}