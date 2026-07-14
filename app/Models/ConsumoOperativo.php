<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumoOperativo extends Model {
    protected $table = 'consumos_operativos';
    protected $fillable = ['vehiculo_id', 'deposito_id', 'tipo_combustible_id', 'cantidad_litros', 'user_id', 'observaciones'];

    public function tipoCombustible(): BelongsTo { return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id'); }
}