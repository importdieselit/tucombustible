<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GascoCupoMensual extends Model
{
    protected $table = 'gasco_cupos_mensuales';

    protected $fillable = [
        'cliente_id',
        'mes',
        'anio',
        'litros_autorizados',
        'litros_consumidos'
    ];

    // Relación con el cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Atributo calculado para saber cuánto queda del cupo GASCO
    public function getSaldoDisponibleAttribute()
    {
        return $this->litros_autorizados - $this->litros_consumidos;
    }
}