<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'tipo'
    ];

    protected $casts = [
        'fecha_salida' => 'datetime',
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
}
