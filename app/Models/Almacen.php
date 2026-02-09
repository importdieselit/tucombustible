<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    protected $table = 'almacenes';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'nombre',
        'ubicacion',
        'descripcion',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_almacen', 'id');
    }
}