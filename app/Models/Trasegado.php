<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trasegado extends Model {
    protected $table = 'trasegados';
    protected $fillable = [
        'tipo_trasegado', 'sede_origen_id', 'deposito_origen_id', 'bolsa_origen_tipo',
        'sede_destino_id', 'deposito_destino_id', 'bolsa_destino_tipo',
        'aliado_comercial_id', 'tipo_combustible_id', 'cantidad_litros', 'user_id', 'status', 'observaciones'
    ];

    public function aliado(): BelongsTo { return $this->belongsTo(AliadoComercial::class, 'aliado_comercial_id'); }
    public function tipoCombustible(): BelongsTo { return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id'); }
}