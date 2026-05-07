<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    use HasFactory;
    protected $table = 'bitacora';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $increments = true;
    public $fillable = [
        'usuario_id',
        'evento',
        'modelo',
        'modelo_id',
        'accion',
        'datos',
        'antes',
        'despues',
        'ip',
        'user_agent',
        'descripcion',
    ];

}
