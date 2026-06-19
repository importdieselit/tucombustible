<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MovimientoCombustible;
use App\Models\Sedes;
use App\Models\TipoCombustible;

class Deposito extends Model
{
    use HasFactory;

    protected $table = 'depositos';

    protected $fillable = [
        'serial',
        'id_sede',
        'capacidad_litros',
        'nivel_actual_litros',
        'nivel_cm',
        'nivel_alerta_litros',
        'tipo_combustible_id',
        'producto',
        'diametro',
        'longitud',
        'ancho',
        'alto',
        'capacidad_maxima',
        'forma',
        'rotacion',
    ];
    protected $casts = [
        'capacidad_litros' => 'float',
        'nivel_actual_litros' => 'float',
        'nivel_alerta_litros' => 'float',
    ];

    // Constantes para mapear las formas del ENUM de tu DDL
    const FORMA_CILINDRICO_HORIZONTAL = 'CH';
    const FORMA_CILINDRICO_VERTICAL   = 'CV';
    const FORMA_OVAL_HORIZONTAL       = 'OH';
    const FORMA_OVAL_VERTICAL         = 'OV';
    const FORMA_RECTANGULAR           = 'R';
    const FORMA_CUBICO                = 'C';
    const FORMA_ESFERICO              = 'E';

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

    /**
     * Relación con la Sede a la que pertenece el tanque.
     */
    public function sedes(): BelongsTo
    {
        return $this->belongsTo(Sedes::class, 'id_sede');
    }

    /**
     * Relación con el tipo de combustible asignado al tanque (Diesel / MGO).
     */
    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id');
    }    

    public function historialChequeos()
    {
        return $this->hasMany(ChequeoDepositoDetalle::class, 'id_deposito');
    }

    public function ultimaMedicion()
    {
        return $this->hasOne(ChequeoDepositoDetalle::class, 'id_deposito')
                    ->latestOfMany(); 
    }
}