<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitoCaptacion extends Model
{
    protected $table = 'requisitos_captacion';
    protected $guarded = [];
    protected $fillable = [
        'tipo_cliente',
        'codigo',
        'descripcion',
        'obligatorio'
    ];
}
