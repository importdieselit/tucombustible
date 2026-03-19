<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroPaso extends Model
{
    use HasFactory;

    protected $table = 'registro_pasos';

    protected $fillable = [
        'nombre',
        'orden',
        'activo',
    ];

    // -------------------------------------------------------
    // RELACIONES
    // -------------------------------------------------------

    /**
     * Un paso puede estar asignado a muchos clientes.
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'registro_paso');
    }

    // -------------------------------------------------------
    // SCOPES
    // -------------------------------------------------------

    /**
     * Solo pasos activos, ordenados por su campo 'orden'.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', 1)->orderBy('orden');
    }
}