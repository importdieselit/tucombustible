<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoPrecargado extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehiculos_precargados';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_vehiculo',
        'cantidad_cargada',
        'fecha_hora_carga',
        'estatus',
        'fecha_hora_despacho',
        'tipo_producto',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'fecha_hora_carga' => 'datetime',
        'fecha_hora_despacho' => 'datetime',
        'estatus' => 'integer',
        'cantidad_cargada' => 'decimal:2',
    ];

    /**
     * Get the vehiculo that owns the carga.
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }
}
