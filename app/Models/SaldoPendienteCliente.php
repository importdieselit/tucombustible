<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoPendienteCliente extends Model
{
    protected $table = 'saldos_pendientes_clientes';

    protected $fillable = [
        'cliente_id',
        'tipo_combustible_id',
        'tipo_accion',
        'cantidad_litros',
        'viaje_id',
        'llenado_prepagado_id',
        'user_id',
        'observaciones',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id');
    }
}