<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoPerfil extends Model
{
    use HasFactory;

    protected $table = 'permiso_perfil'; // Nombre explícito de la tabla

    protected $fillable = [
        'id_perfil',
        'id_modulo',
        'create',
        'read',
        'update',
        'delete',
    ];

    protected $casts = [
        'create' => 'boolean',
        'read' => 'boolean',
        'update' => 'boolean',
        'delete' => 'boolean',
    ];

    /**
     * Un permiso de perfil pertenece a un perfil.
     */
    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'id_perfil');
    }

    /**
     * Un permiso de perfil pertenece a una sección.
     */
    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'id_modulo');
    }

    public $timestamps = true;
    
}
