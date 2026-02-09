<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class Accesos extends Model
{
    protected $table = 'accesos';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_usuario',
        'id_modulo',
        'read',
        'update',
        'create',
        'delete',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_modulo' => 'integer',
        'read' => 'boolean',
        'update' => 'boolean',
        'create' => 'boolean',
        'delete' => 'boolean',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id');
    }
}