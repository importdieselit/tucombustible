<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbastecimientoTanque extends Model
{
    use HasFactory;

    protected $table = 'abastecimientos_tanques';

    protected $fillable = [
        'id_sede',
        'id_vehiculo',
        'id_deposito',
        'id_tipo_combustible',
        'id_usuario',
        'id_precarga_origen',
        'cantidad_litros',
        'fecha_hora',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hora'      => 'datetime',
        'cantidad_litros' => 'float',
    ];

    public function sede()
    {
        return $this->belongsTo(Sedes::class, 'id_sede');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }

    public function deposito()
    {
        return $this->belongsTo(Deposito::class, 'id_deposito');
    }

    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustible');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function precargaOrigen()
    {
        return $this->belongsTo(VehiculoPrecargado::class, 'id_precarga_origen');
    }
}