<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioStock extends Model
{
    use HasFactory;
    protected $table = 'inventario_stock';
    protected $fillable = [
        'inventario_id',
        'ubicacion_id',
        'cantidad_actual',
        'cantidad_reservada',
        'capacidad_asignada',
        'lote',
        'fecha_vencimiento'
    ];
    protected $hasTimestamps = true;

    public function item()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
    
}
