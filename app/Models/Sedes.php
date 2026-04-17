<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sedes extends Model
{
    protected $table = 'sedes';

    protected $fillable = [
        'nombre', 
        'estado_id', 
        'ciudad_id', 
        'direccion_especifica', 
        'estatus'
    ];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }
}