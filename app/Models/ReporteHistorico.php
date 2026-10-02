<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteHistorico extends Model
{
    use HasFactory;

    protected $table = 'reportes_historicos';

    protected $fillable = [
        'nombre_reporte',
        'fecha',
        'turno',
        'contenido'
    ];

    // Esto es clave: Laravel manejará el JSON automáticamente
    protected $casts = [
        'fecha' => 'date',
        'contenido' => 'array', 
    ];
}