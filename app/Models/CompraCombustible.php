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
        'planta_proveedor_id',
        'cantidad_litros',
        'cantidad_recibida',
        'planta_destino_id',
        'fecha',
        'tipo',
        'sap',
        'numero_factura',
        'factura_path',
        'usuario_id',
        'vehiculo_id',
        'cisterna',
        'monto_usd',
        'monto_bs',
        'observaciones',
        'estatus',
        'viaje_id',
        'flete',
        'otro_vehiculo',
        'otro_chofer',
        'otro_proveedor',
        'otro_ayudante',
    ];

    protected $casts = [
        'fecha' => 'date',
        'flete' => 'boolean',
        'monto_usd' => 'decimal:2',
        'monto_bs' => 'decimal:2',
    ];

    /**
     * Relación con la Planta Proveedora.
     */
    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class, 'planta_proveedor_id');
    }

    /**
     * Relación directa con Proveedor / Planta por foreign key proveedor_id.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'planta_proveedor_id');
    }

    /**
     * Relación con la Planta de Destino.
     */
    public function plantaDestino(): BelongsTo
    {
        return $this->belongsTo(Sedes::class, 'planta_destino_id');
    }

    /**
     * Relación con el Viaje.
     */
    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class, 'viaje_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function cisterna(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'cisterna');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function historialFacturas()
    {
        return $this->hasMany(HistorialFacturaCompra::class, 'compra_id')->orderBy('created_at', 'desc');
    }
}