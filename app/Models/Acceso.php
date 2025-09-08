<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acceso extends Model
{
    use HasFactory;

    protected $table = 'accesos';

    protected $fillable = [
        'id_usuario',
        'id_modulo',
        'read',
        'update',
        'create',
        'delete',
    ];

    /**
     * Define la relación con el modelo User.
     * Un permiso de acceso pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Define la relación con el modelo Modulo.
     * Un permiso de acceso pertenece a un módulo.
     */
    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'id_modulo');
    }
}
