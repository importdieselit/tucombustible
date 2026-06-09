<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeoDeposito extends Model
{
    use HasFactory;

    protected $table = 'chequeos_depositos';

    protected $fillable = [
        'id_sede',
        'id_usuario',
        'fecha',
        'turno',
    ];

    /**
     * Un chequeo pertenece a una Sede específica.
     */
    public function sede()
    {
        return $this->belongsTo(Sedes::class, 'id_sede');
    }

    /**
     * Un chequeo fue registrado por un Usuario (operador/supervisor).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Un chequeo de turno tiene un desglose de muchas mediciones de tanques.
     */
    public function detalles()
    {
        return $this->hasMany(ChequeoDepositoDetalle::class, 'id_chequeo');
    }
}