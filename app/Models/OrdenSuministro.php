<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenSuministro extends Model
{
    use HasFactory;
    
    protected $table = 'orden_suministros'; // Asumo que tienes esta tabla pivote (o similar)

    protected $fillable = [
        'orden_id',
        'inventario_id', // ID del item si viene del inventario (puede ser NULL)
        'descripcion', // Descripción si es un item manual (puede ser NULL)
        'cantidad_solicitada',
        'es_manual' // Flag para identificar si fue agregado manualmente
    ];

    /**
     * Relación: Pertenece a una Orden.
     */
    public function orden()
    {
        return $this->belongsTo(Orden::class);
    }

    /**
     * Relación: Al ítem de inventario (si no es manual).
     */
    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
}
