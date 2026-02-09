<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'nombre_proveedor',
        'contacto',
        'telefono',
        'email',
        'direccion',
        'tipo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}