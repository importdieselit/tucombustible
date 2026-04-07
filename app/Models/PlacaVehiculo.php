<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlacaVehiculo extends Model
{
    use HasFactory;

    protected $table = 'placas_vehiculos';

    protected $fillable = [
        'cliente_id',
        'placa',
        'activo',
    ];

    // -------------------------------------------------------
    // RELACIONES
    // -------------------------------------------------------

    /**
     * La placa pertenece a un Cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // -------------------------------------------------------
    // SCOPES
    // -------------------------------------------------------

    public function scopeActivas($query)
    {
        return $query->where('activo', 1);
    }
}