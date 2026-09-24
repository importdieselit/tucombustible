<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parametro extends Model
{
    use HasFactory;

    protected $table = 'parametros';

    protected $fillable = [
        'grupo',
        'clave',
        'descripcion',
        'valor',
        'activo',
    ];

    protected $casts = [
        'valor'  => 'array',
        'activo' => 'boolean',
    ];

    /**
     * Accessor para leer directamente $param->esquema desde el array JSON 'valor'
     */
    public function getEsquemaAttribute()
    {
        return $this->valor['schema'] ?? [];
    }

    /**
     * Accessor para leer directamente $param->contenido desde el array JSON 'valor'
     */
    public function getContenidoAttribute()
    {
        return $this->valor['content'] ?? null;
    }

    public function scopeGrupo($query, $grupo)
    {
        return $query->where('grupo', $grupo);
    }

    public function scopeClave($query, $clave)
    {
        return $query->where('clave', $clave);
    }
}