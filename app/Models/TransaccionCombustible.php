<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaccionCombustible extends Model
{
    protected $table = 'transacciones_combustible';

    protected $fillable = [
        'sede_id',
        'tipo_combustible_id',
        'bolsa_tipo',
        'tipo_movimiento',
        'cantidad_litros',
        'deposito_id',
        'viaje_id',
        'cliente_id',
        'user_id',
        'observaciones',
    ];

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function sede(): BelongsTo 
    { 
        return $this->belongsTo(Sedes::class, 'sede_id'); 
    }

    public function viaje(): BelongsTo 
    { 
        return $this->belongsTo(Viaje::class, 'viaje_id'); 
    }
}