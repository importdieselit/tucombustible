<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Vehiculo;
use App\Models\Personal;
use App\Models\InspeccionItemRespuesta;

// Modelo para la tabla 'inspecciones'
class Inspeccion extends Model
{
    use HasFactory;

    protected $table = 'inspecciones';

    protected $fillable = [
        'vehiculo_id',
        'fecha',
        'inspector_id',
        'aprobado',
        'observaciones',
    ];

    protected $casts = [
        'aprobado' => 'boolean',
    ];

    /**
     * Relación con el vehículo.
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    /**
     * Relación con el personal que inspecciona.
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'inspector_id');
    }
    
    /**
     * Relación con las respuestas de la inspección.
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(InspeccionItemRespuesta::class, 'inspeccion_id');
    }
}