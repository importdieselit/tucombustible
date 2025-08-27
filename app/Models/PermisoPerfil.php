<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoPerfil extends Model
{
    use HasFactory;

    protected $table = 'permiso_perfil'; // Nombre explícito de la tabla

    protected $fillable = [
        'perfil_id',
        'seccion_id',
        'ver',
        'crear',
        'editar',
        'eliminar',
    ];

    protected $casts = [
        'ver' => 'boolean',
        'crear' => 'boolean',
        'editar' => 'boolean',
        'eliminar' => 'boolean',
    ];

    /**
     * Un permiso de perfil pertenece a un perfil.
     */
    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    /**
     * Un permiso de perfil pertenece a una sección.
     */
    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }
}
