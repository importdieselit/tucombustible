<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabuladorViatico extends Model
{
    use HasFactory;

    protected $table = 'tabulador_viaticos';

    protected $fillable = [
        'destino',
        'tipo_viaje',
        'pago_chofer_ejecutivo',
        'pago_chofer',
        'pago_ayudante',
        'peajes',
        'viatico_desayuno',
        'viatico_almuerzo',
        'viatico_cena',
        'costo_pernocta',
        
    ];
}
