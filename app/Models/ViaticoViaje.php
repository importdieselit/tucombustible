<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaticoViaje extends Model
{
    use HasFactory;

    protected $table = 'viaticos_viaje';

    protected $fillable = [
        'viaje_id',
        'concepto',
        'monto_base',
        'cantidad',
        'monto_ajustado',
        'ajustado_por',
        'es_editable',
    ];

    /**
     * Relación con el Viaje al que pertenece este viático.
     */
    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class, 'viaje_id');
    }

    /**
     * Relación con el usuario (Coordinador Administrativo) que hizo el ajuste.
     */
    public function ajustadoPor(): BelongsTo
    {
        // Asume que la tabla de usuarios se llama 'users'
        return $this->belongsTo(User::class, 'ajustado_por');
    }
}
