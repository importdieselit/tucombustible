<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;
    protected $table = 'ubicaciones';
    protected $fillable = [
        'almacen_id',
        'codigo_ubicacion',
        'pasillo',
        'estante',
        'nivel',
        'posicion',
        'tipo',
        'capacidad_maxima_kg',
        'volumen_maximo_litros'
    ];
    protected $hasTimestamps = true;

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
    
    public function inventarioStock()
    {
        return $this->hasMany(InventarioStock::class, 'ubicacion_id');
    }

    public function inventarioItems()
    {
        return $this->hasManyThrough(Inventario::class, InventarioStock::class, 'ubicacion_id', 'id', 'id', 'inventario_id');
    }   

    public function estructuraGrid()
    {
        return $this->belongsTo(AlmacenEstructuraGrid::class, 'estructura_grid_id');
    }

    
}
