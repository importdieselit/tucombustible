<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    use HasFactory;

    protected $table = 'secciones'; // Nombre explícito de la tabla

    protected $fillable = [
        'modulo_id',
        'nombre',
        'ruta',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Una sección pertenece a un módulo.
     */
    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'modulo_id');
    }

    /**
     * Una sección puede tener muchos permisos de perfil.
     */
    public function permisosPerfil()
    {
        return $this->hasMany(PermisoPerfil::class, 'seccion_id');
    }

    /**
     * Una sección puede tener muchos permisos de usuario.
     */
    public function permisosUsuario()
    {
        return $this->hasMany(PermisoUsuario::class, 'seccion_id');
    }
}
