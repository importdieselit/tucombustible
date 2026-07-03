<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialLlenadoCupoPrepagado extends Model
{
    protected $table = 'historial_llenados_cupos_prepagados';

    protected $fillable = [
        'cliente_id',
        'id_sede',
        'id_deposito',
        'tipo_combustible_id',
        'litros',
    ];

    // Relaciones para cuando muestres el historial en las tablas de Livewire
    
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sede(): BelongsTo
    {
        // Usamos 'Sedes' que es como se llama tu modelo
        return $this->belongsTo(Sedes::class, 'id_sede');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'id_deposito');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id');
    }
}