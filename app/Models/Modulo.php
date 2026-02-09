<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos'; // Nombre explícito de la tabla

    protected $fillable = [
        'nombre',
        'icono',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Un módulo tiene muchas secciones.
     */
    public function secciones()
    {
        return $this->hasMany(Seccion::class, 'modulo_id');
    }
    public function parent()
    {
        return $this->belongsTo(Modulo::class, 'id_parent', 'id_modulo-data');
    }

    public function children()
    {
        return $this->hasMany(Modulo::class, 'id_parent', 'id_modulo-data');
    }
}
