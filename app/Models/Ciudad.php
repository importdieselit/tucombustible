<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    use HasFactory;

    // DEFINICIÓN EXPLÍCITA DE LA TABLA (Fundamental para que no busque 'ciudads')
    protected $table = 'ciudades';

    // CAMPOS ASIGNABLES
    protected $fillable = [
        'estado_id',
        'nombre'
    ];

    /**
     * Relación con el Estado
     */
    public function estado() {
        return $this->belongsTo(Estado::class, 'estado_id');
    }
}