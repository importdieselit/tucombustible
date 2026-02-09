<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspeccionImagen extends Model
{
    protected $table = 'inspeccion_imagenes';

    protected $fillable = [
        'inspeccion_id',
        'ruta_imagen',
        'descripcion',
        'tipo_evidencia',
        'orden'
    ];

    // Relación inversa con Inspeccion
    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class, 'inspeccion_id');
    }
    
    /**
     * Obtener la URL completa de la imagen
     */
    public function getUrlImagenAttribute()
    {
        return url('storage/' . $this->ruta_imagen);
    }
}

