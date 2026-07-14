<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReversoCombustible extends Model {
    protected $table = 'reversos_combustible';
    protected $fillable = ['viaje_id', 'cliente_id', 'tipo_combustible_id', 'cantidad_litros', 'motivo_reverso', 'user_id'];

    public function tipoCombustible(): BelongsTo { return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id'); }
}