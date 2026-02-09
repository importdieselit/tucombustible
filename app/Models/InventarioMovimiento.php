<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';
    protected $primaryKey = 'id_movimiento';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'id_inventario',
        'cantidad',
        'fecha_movimiento',
        'tipo_movimiento',
        'observacion',
        'referencia',
        'origen',
        'destino',
        'hora',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_inventario' => 'integer',
        'cantidad' => 'integer',
        'fecha_movimiento' => 'date',
        'origen' => 'integer',
        'destino' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
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

    public function origenAlmacen()
    {
        return $this->belongsTo(InventarioAlmacen::class, 'origen', 'id');
    }

    public function destinoAlmacen()
    {
        return $this->belongsTo(InventarioAlmacen::class, 'destino', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}