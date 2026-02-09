<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class InventarioDetalleCompra extends Model
{
    protected $table = 'inventario_detalle_compras';
    protected $primaryKey = 'id_detalle_compra';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'id_inventario_compra',
        'id_inventario',
        'cantidad',
        'precio',
        'total',
        'observacion',
        'precio_div',
        'total_div',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_inventario_compra' => 'integer',
        'id_inventario' => 'integer',
        'cantidad' => 'integer',
        'precio' => 'decimal:2',
        'total' => 'decimal:2',
        'precio_div' => 'decimal:2',
        'total_div' => 'decimal:2',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function compra()
    {
        return $this->belongsTo(InventarioCompra::class, 'id_inventario_compra', 'id_inventario_compra');
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }
}