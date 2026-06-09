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
        'centimetros_medidos',
        'litros_calculados',
    ];

    // Casteamos los valores para que Eloquent los devuelva siempre como floats y no como strings
    protected $casts = [
        'centimetros_medidos' => 'float',
        'litros_calculados' => 'float',
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
}