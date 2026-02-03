<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class InventarioAsociado extends Model
{
    protected $table = 'inventario_asociados';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'id_inventario',
        'marca',
        'modelo',
        'fecha_in',
        'observacion',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_inventario' => 'integer',
        'marca' => 'integer',
        'modelo' => 'integer',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    public function marcaObj()
    {
        return $this->belongsTo(Marca::class, 'marca', 'id');
    }

    // public function modeloObj()
    // {
    //     return $this->belongsTo(Modelo::class, 'modelo', 'id');
    // }

}