<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCombustible extends Model
{
    use HasFactory;

    protected $table = 'tipos_combustible';

    protected $fillable = ['nombre'];

    /**
     * Relación: Un tipo de combustible puede estar en muchos cupos de clientes
     */
    public function clienteCupos()
    {
        return $this->hasMany(ClienteCupo::class, 'tipo_combustible_id');
    }
}