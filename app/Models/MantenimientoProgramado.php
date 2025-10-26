<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoProgramado extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos_programados';

    protected $fillable = [
        'vehiculo_id',
        'plan_id',
        'fecha',
        'semana',
        'tipo',
        'nro_orden',
        'orden_id',
        'km',
        'status',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Relación con el vehículo.
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * Relación con la Orden de Trabajo (si ha sido generada).
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class);
    }

    /**
     * Relación con el usuario que creó la planificación.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanMantenimiento::class, 'plan_id');
    }

    
}