<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmacenEstructuraGrid extends Model
{
    use HasFactory;
    protected $table = 'almacen_estructuras_grid';
    protected $primaryKey = 'id';
    protected $fillable = [
        'almacen_id',
        'coord_x',
        'coord_y',
        'tipo_estructura',
        'codigo_bloque',
        'cantidad_niveles',
        'cantidad_secciones',
        'config_layout',
        'rotacion',
    ];
    protected $casts = [
        'config_layout' => 'array',
    ];
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}
