<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumenDiario extends Model
{
    use HasFactory;

    protected $table = 'resumen_diario';
    protected $primaryKey = 'id_resumen';
    
    protected $fillable = [
        'fecha',
        'plan',
        'real',
        'disponibilidad',
        'conteo',
        'plan_models',
    ];

    /**
     * Conversión de tipos de atributos.
     * El campo plan_models se convierte a array automáticamente.
     */
    protected $casts = [
        'plan_models' => 'array',
        'fecha' => 'date',
    ];
}
