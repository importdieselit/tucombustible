<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoPrecargado extends Model
{
    use HasFactory;

    protected $table = 'vehiculos_precargados';

    protected $fillable = [
        'id_vehiculo',
        'id_sede',
        'id_deposito',
        'id_tipo_combustible',
        'id_usuario',
        'cantidad_litros',
        'esta_precintado',
        'observaciones',
        'fecha_hora_carga',
        'estatus',
    ];

    protected $casts = [
        'fecha_hora_carga' => 'datetime',
        'estatus'          => 'integer',
        'cantidad_litros'  => 'float',
        'esta_precintado'  => 'boolean',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sedes::class, 'id_sede');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'id_deposito');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustible');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}