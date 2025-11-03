<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa una solicitud de compra de combustible a un proveedor.
 */
class CompraCombustible extends Model
{
    use HasFactory;

    protected $table = 'compras_combustible';

    protected $fillable = [
        'proveedor_id',
        'cantidad_litros',
        'planta_destino_id',
        'fecha',
        'vehiculo_id',
        'cisterna',
        'estatus', // Ej: PROGRAMADA, ASIGNADA, COMPRADA, COMPLETADA, CANCELADA
        'viaje_id', // Enlace a la planificación de viaje de entrega/carga
    ];

    protected $casts = [
        'fecha_requerida' => 'date',
    ];

    /**
     * Relación con el Proveedor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Relación con la Planta de Destino (donde se cargará o entregará el combustible).
     */
    public function plantaDestino(): BelongsTo
    {
        return $this->belongsTo(Planta::class, 'planta_destino_id');
    }

    /**
     * Relación con el Viaje (la planificación logística para esta solicitud).
     */
    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class);
    }

    public function vehiculo(){
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function cisterna(){
        return $this->belongsTo(Vehiculo::class, 'cisterna');
    }

}
