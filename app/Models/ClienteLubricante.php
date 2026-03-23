<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteLubricante extends Model
{
    use HasFactory;

    protected $table = 'clientes_lubricantes';

    protected $fillable = [
        'razon_social',
        'rif',
        'email',
        'telefono',
    ];
}