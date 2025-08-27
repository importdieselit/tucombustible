<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    use HasFactory;
    
    // Define el nombre de la tabla si es diferente al plural del modelo.
    protected $table = 'alertas';
    
    // Define la llave primaria si no es 'id'.
    protected $primaryKey = 'id_alerta';
    
    // Campos que pueden ser asignados masivamente.
    protected $fillable = [
        'id_usuario',
        'id_rel',
        'dias',
        'fecha',
        'observacion',
        'estatus',
        'accion',
    ];

    // Define el tipo de datos para que Laravel los maneje automáticamente.
    protected $casts = [
        'fecha' => 'date',
    ];
}
