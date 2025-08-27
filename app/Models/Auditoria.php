<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Auditoria extends Model
{
    protected $table = 'auditorias';
    protected $primaryKey = 'id_auditoria';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'tabla',
        'accion',
        'id_usuario',
        'descripcion',
        'fecha_accion',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'fecha_accion' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}