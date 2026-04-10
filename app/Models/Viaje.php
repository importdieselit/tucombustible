<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Chofer;
use App\Models\ViaticoViaje;
use App\Models\Vehiculo;
use App\Models\Cliente;
use App\Models\DespachoViaje;
use App\Models\User;
use App\Models\Producto;
use App\Models\CompraCombustible;


class Viaje extends Model
{
    use HasFactory;

    protected $table = 'viajes';

    protected $fillable = [
        'destino_ciudad',
        'chofer_id',
        'ayudante',
        'custodia_count',
        'fecha_salida',
        'fecha_llegada',
        'fecha_salida_real',
        'status',
        'vehiculo_id', 
        'litros',
        'has_viatico',
        'cliente_id',
        'otro_cliente',
        'usuario_id',
        'otro_vehiculo',
        'otro_chofer',
        'otro_ayudante',
        'chofer_externo',
        'ayudante_externo',
        'cisterna_externo',
        'vehiculo_externo',
        'es_transporte_externo',
        'sede_id',
        'tipo_planificacion',
        'observacion',
        'tipo',
        'tipo_remolque',
        'punto_salida',
        'punto_llegada',
        'codigo_sap',
        'nombre_cliente_externo'
    ];

    protected $casts = [
        'fecha_salida' => 'datetime',
        'fecha_llegada' => 'datetime',
        'fecha_salida_real' => 'datetime',
    ];

    /**
     * Relación con el Chofer asignado (un usuario).
     */
    public function chofer(): BelongsTo
    {
        // Asume que la tabla de usuarios se llama 'users'
        return $this->belongsTo(Chofer::class, 'chofer_id'); 
    }

    public function ayudante_chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'ayudante', 'id'); 
    }

    /**
     * Relación con el cuadro de viáticos generados para este viaje.
     */
    public function viaticos(): HasMany
    {
        return $this->hasMany(ViaticoViaje::class, 'viaje_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function cisternaAcoplada(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'cisterna', 'id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
    /**
     * Relación con los despachos asociados a este viaje.
     */
    public function despachos(): HasMany
    {
        return $this->hasMany(DespachoViaje::class, 'viaje_id');
    } 
  
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'tipo', 'id');
    }
    public function compraCombustible(): HasMany
    {
        return $this->hasMany(CompraCombustible::class, 'viaje_id');
    }
    public function inspecciones(): HasOne
    {
        return $this->hasOne(Inspeccion::class, 'viaje_id');
    }
    public function tipoCombustible() { 
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id'); 
    }
    
    public function detalles() { 
        return $this->hasMany(DespachoViaje::class, 'viaje_id'); 
    }
}
