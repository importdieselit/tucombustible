<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioStock extends Model
{
    use HasFactory;
    protected $table = 'inventario_stock';
    protected $fillable = [
        'item_id',
        'ubicacion_id',
        'cantidad_actual',
        'cantidad_reservada',
        'lote',
        'fecha_vencimiento'
    ];
    protected $hasTimestamps = true;

    public function item()
    {
        return $this->belongsTo(Inventario::class, 'item_id');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
    
}
