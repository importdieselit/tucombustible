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

    /**
     * Relación: La placa pertenece a un Cliente (Sede de registro)
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Scope para filtrar solo placas activas rápidamente
     * Uso: PlacaVehiculo::activa()->get();
     */
    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }
}