<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCombustible extends Model
{
    use HasFactory;

    protected $table = 'movimientos_combustible';

    protected $fillable = [
        'tipo_movimiento',
        'deposito_id',
        'proveedor_id',
        'cliente_id',
        'pedido_id',           // Nuevo de la migración
        'viaje_id',            // Nuevo de la migración
        'tipo_combustible_id', // Nuevo de la migración
        'otro_cliente',
        'cantidad_litros',
        'observaciones',
        'vehiculo_id',
        'cisterna_id',
        'cant_inicial',
        'cant_final',
        'nro_ticket',
        'created_at'           // Importante si vas a manipular fechas manualmente
    ];

    /* --- RELACIONES --- */

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /**
     * Relación para la cisterna (Vehículo de transporte)
     */
    public function cisterna(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'cisterna_id');
    }

    /**
     * Relación para el vehículo (Consumo operativo)
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    /* --- SCOPES DE FILTRADO (Manteniendo tu lógica original) --- */

    public function scopeTipoMovimiento($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo); 
    }

    public function scopeDeposito($query, $depositoId)
    {
        return $query->where('deposito_id', $depositoId);
    }

    public function scopeProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopeCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId); 
    }

    public function scopeVehiculo($query, $vehiculoId)
    {
        return $query->where('vehiculo_id', $vehiculoId);
    }

    public function scopeFecha($query, $fecha)
    {
        return $query->whereDate('created_at', $fecha);
    }

    public function scopeRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }
}