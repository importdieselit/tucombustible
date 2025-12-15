<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\Viaje;


class Guia extends Model
{
    use HasFactory;
    protected $table = 'guias';
    protected $fillable = [
        'numero_guia',
        'viaje_id',
        'fecha_emision',
        'cliente',
        'rif',
        'ruta',
        'direccion',
        'buque',
        'muelle',
        'precintos',
        'unidad',
        'cisterna',
        'conductor',
        'cedula',
        'cantidad',
        'producto',
        'observaciones'
    ];



    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function viaje()
    {
        return $this->belongsTo(Viaje::class);
    }

    public function boletas()
    {
        return $this->hasOne(Boleta::class);
    }
    public function nominaciones()
    {
        return $this->hasOne(Nominacion::class);
    }

}
