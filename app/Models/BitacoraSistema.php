<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraSistema extends Model
{
    use HasFactory;
    protected $table = 'bitacora_sistema';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $increments = true;
    public $audit = false;
    public $fillable = [
        'id_usuario',
        'tipo',
        'actividad',
        'metodo_accion',
        'parametros_request',
        'data_antes',
        'data_despues',
        'ip',
        'user_agent',
        'mensaje'
    ];

}
