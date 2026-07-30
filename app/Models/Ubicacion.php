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
        'estructura_grid_id',
        'codigo_ubicacion',
        'pasillo',
        'estante',
        'nivel',
        'posicion',
        'tipo',
        'capacidad_maxima_kg',
        'volumen_maximo_litros',
        'colspan',
        'subposicion',
        'esta_bloqueada'
    ];
    protected $hasTimestamps = true;

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
    
    public function inventarioStock()
    {
        return $this->hasOne(InventarioStock::class, 'ubicacion_id');
    }

    public function inventarioItems()
    {
        return $this->hasOneThrough(Inventario::class, InventarioStock::class, 'ubicacion_id', 'id', 'id', 'inventario_id');
    }   

    public function estructuraGrid()
    {
        return $this->belongsTo(AlmacenEstructuraGrid::class, 'estructura_grid_id');
    }

    
}
