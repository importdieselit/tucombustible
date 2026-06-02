<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MantenimientoItem extends Model
{
    protected $table = 'mantenimiento_items';
    protected $fillable = ['categoria', 'nombre', 'costo_promedio'];

    public function vehiculos()
    {
        return $this->belongsToMany(Vehiculo::class, 'plan_mayor_controles', 'mantenimiento_item_id', 'vehiculo_id');
    }
}