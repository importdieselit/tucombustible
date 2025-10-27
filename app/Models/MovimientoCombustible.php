<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Deposito;
use App\Models\Proveedor;
use App\Models\Cliente;

// Modelo para la tabla 'movimientos_combustible'
class MovimientoCombustible extends Model
{
    use HasFactory;

    protected $table = 'movimientos_combustible';

    protected $fillable = [
        'tipo_movimiento',
        'deposito_id',
        'proveedor_id',
        'cliente_id',
        'cantidad_litros',
        'observaciones',
        'vehiculo_id',
        'created_at',
        'cisterna_id',
        'cant_inicial',
        'cant_final'
    ];

    /**
     * Relación con el depósito.
     */
    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id');
    }

    /**
     * Relación con el proveedor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación con el cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Define la relación: un movimiento de despacho para servicio pertenece a una cisterna.
     * Esta es la relación para la cisterna.
     */
    public function cisterna(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * Define la relación: un movimiento de consumo pertenece a un vehículo.
     * Esta es la relación para el vehículo.
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
    /**
     * Scope para filtrar por tipo de movimiento.
     */
    public function scopeTipoMovimiento($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo); 
    }
    /**
     * Scope para filtrar por depósito.
     */
    public function scopeDeposito($query, $depositoId)
    {
        return $query->where('deposito_id', $depositoId);
    }
    /**
     * Scope para filtrar por proveedor.
     */
    public function scopeProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }
    /**
     * Scope para filtrar por cliente.
     */
    public function scopeCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId); 
    }
    /**
     * Scope para filtrar por vehículo.
     */
    public function scopeVehiculo($query, $vehiculoId)
    {
        return $query->where('vehiculo_id', $vehiculoId);
    }
    /**
     * Scope para filtrar por fecha.
     */
    public function scopeFecha($query, $fecha)
    {
        return $query->whereDate('created_at', $fecha);
    }
    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopeRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }
    
}