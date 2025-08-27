<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoUsuario extends Model
{
    use HasFactory;

    protected $table = 'permiso_usuario'; // Nombre explícito de la tabla

    protected $fillable = [
        'users_id',
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
     * Un permiso de usuario pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    /**
     * Un permiso de usuario pertenece a una sección.
     */
    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }
}
