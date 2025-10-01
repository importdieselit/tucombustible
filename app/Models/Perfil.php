<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    use HasFactory;

    protected $table = 'perfiles'; // Nombre explícito de la tabla

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Un perfil tiene muchos usuarios.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'perfil_id');
    }
    /**
     * Un perfil tiene muchos permisos.
     */
    public function permisos()
    {
        return $this->hasMany(PermisoPerfil::class, 'id_perfil');

}
