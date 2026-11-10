<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrecioBunkerHistorico extends Model
{
    use HasFactory;

    protected $table = 'precios_bunker_historico';

   protected $fillable = [
        'fecha',
        'combustible',
        'precio',
    ];
}