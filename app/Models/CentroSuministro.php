<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentroSuministro extends Model
{
    use HasFactory;

    protected $table = 'centro_suministro';
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'direccion',
    ];
}
