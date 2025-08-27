<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class InventarioCargar extends Model
{
    protected $table = 'inventario_cargar';
    protected $primaryKey = 'id_carga';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'id_inventario',
        'fecha_in',
        'costo',
        'cantidad',
        'observacion',
        'referencia',
        'proveedor',
        'otro_proveedor',
        'emisor',
        'existencia',
        'tipo',
        'hora',
        'status',
        'costo_div',
        'id_cotizacion',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_inventario' => 'integer',
        'fecha_in' => 'date',
        'costo' => 'decimal:2',
        'cantidad' => 'double',
        'proveedor' => 'integer',
        'emisor' => 'integer',
        'existencia' => 'double',
        'costo_div' => 'decimal:0', // Revisar si es decimal(10,0) o integer
        'hora' => 'time',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    public function proveedorObj()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor', 'id');
    }

    public function emisorObj()
    {
        return $this->belongsTo(User::class, 'emisor', 'id');
    }
}