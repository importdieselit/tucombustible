<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeoDepositoDetalle extends Model
{
    use HasFactory;

    protected $table = 'chequeos_depositos_detalles';

    protected $fillable = [
        'id_chequeo',
        'id_deposito',
        'id_tipos_combustible',
        'centimetros_medidos',
        'litros_calculados',
        'litros_teoricos',
        'merma_calculada',
    ];

    // Casteamos los valores para que Eloquent los devuelva siempre como floats y no como strings
    protected $casts = [
        'id_tipos_combustible' => 'integer',
        'centimetros_medidos' => 'float',
        'litros_calculados' => 'float',
        'litros_teoricos' => 'float',
        'merma_calculada' => 'float',
    ];

    /**
     * El detalle pertenece a una cabecera de chequeo general.
     */
    public function chequeo()
    {
        return $this->belongsTo(ChequeoDeposito::class, 'id_chequeo');
    }

    /**
     * El detalle corresponde a un Tanque (Depósito) físico específico.
     */
    public function deposito()
    {
        return $this->belongsTo(Deposito::class, 'id_deposito');
    }

    /**
     * El detalle corresponde a un tipo de combustible específico.
     */
    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipos_combustible');
    }
}