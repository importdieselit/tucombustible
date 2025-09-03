<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MovimientoCombustible;

class Deposito extends Model
{
    use HasFactory;

    protected $table = 'depositos';

    protected $fillable = [
        'capacidad_litros',
        'nivel_actual_litros',
        'nivel_alerta_litros',
        'ubicacion', 
        'serial',
        'producto'
    ];
    protected $casts = [
        'capacidad_litros' => 'float',
        'nivel_actual_litros' => 'float',
        'nivel_alerta_litros' => 'float',
    ];
    /**
     * Relación con los movimientos de combustible entrantes.
     */
    public function movimientosEntrantes(): HasMany
    {
        return $this->hasMany(MovimientoCombustible::class, 'deposito_id')
            ->where('tipo_movimiento', 'entrada');
    }
    /**
     * Relación con los movimientos de combustible salientes.
     */
    public function movimientosSalientes(): HasMany
    {
        return $this->hasMany(MovimientoCombustible::class, 'deposito_id')
            ->where('tipo_movimiento', 'salida');
    }   

    /**
     * Relación con los movimientos de combustible por proveedor.
     */
    public function movimientosPorProveedor(): HasMany
    {
        return $this->hasMany(MovimientoCombustible::class, 'deposito_id')
            ->whereNotNull('proveedor_id');
    }           

    /**
     * Relación con los movimientos de combustible por usuario.
     */
    public function movimientosPorUsuario(): HasMany
    {
        return $this->hasMany(MovimientoCombustible::class, 'deposito_id')
            ->whereNotNull('user_id');
    }

    
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCombustible::class, 'deposito_id');
    }

    public function movimientosCombustible(): HasMany
    {
        return $this->hasMany(MovimientoCombustible::class, 'deposito_id');
    }

    
}