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
        'observaciones',
        'estatus',
        'viaje_id',
        'flete',
        'otro_vehiculo',
        'otro_chofer',
        'otro_proveedor',
        'otro_ayudante'
    ];

    protected $casts = [
        'fecha_requerida' => 'date',
    ];

    /**
     * Relación con el Proveedor.
     */
    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_proveedor_id');
    }

    /**
     * Relación con la Planta de Destino (donde se cargará o entregará el combustible).
     */
    public function plantaDestino(): BelongsTo
    {
        return $this->belongsTo(Sedes::class, 'planta_destino_id');
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

    public function proveedor(){
        return $this->belongsTo(Proveedor::class, 'planta_proveedor_id');
    }    

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function historialFacturas()
    {
        return $this->hasMany(HistorialFacturaCompra::class, 'compra_id')->orderBy('created_at', 'desc');
    }

}
