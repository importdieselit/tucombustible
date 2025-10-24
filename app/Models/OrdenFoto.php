<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenFoto extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'orden_id',
        'ruta_archivo',
        'descripcion'
    ];

    /**
     * Relación: Una foto pertenece a una Orden.
     */
    public function orden()
    {
        return $this->belongsTo(Orden::class);
    }
    
}
