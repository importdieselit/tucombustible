<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Chofer;
use App\Models\ViaticoViaje;


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
    ];

    /**
     * Relación con el Chofer asignado (un usuario).
     */
    public function chofer(): BelongsTo
    {
        // Asume que la tabla de usuarios se llama 'users'
        return $this->belongsTo(Chofer::class, 'chofer_id'); 
    }

    public function ayudante(): BelongsTo
    {
        // Asume que la tabla de usuarios se llama 'users'
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
}
