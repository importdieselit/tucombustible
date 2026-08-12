<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';

    protected $fillable = [
        'modulo',
        'ruta',
        'icono',
        'orden',
        'id_padre',
        'url_directa',
        'visible',
        'descripcion',
    ];

    protected $casts = [
        'url_directa' => 'boolean',
        'visible'     => 'boolean',
        'orden'       => 'integer',
        'id_padre'    => 'integer',
    ];

    /**
     * Módulo Padre
     */
    public function padre()
    {
        return $this->belongsTo(Modulo::class, 'id_padre')->withDefault([
            'modulo' => 'Módulo Raíz (Sin Padre)'
        ]);
    }

    /**
     * Submódulos o ítems hijos
     */
    public function hijos()
    {
        return $this->hasMany(Modulo::class, 'id_padre')->orderBy('orden', 'asc');
    }

}
