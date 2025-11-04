<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoFoto extends Model
{
    use HasFactory;

    protected $table = 'vehiculo_fotos'; // Nombre de la tabla

    protected $fillable = [
        'vehiculo_id',
        'ruta', // La ruta del archivo en el storage
        'es_principal', // Para saber qué foto mostrar como miniatura (opcional)
    ];

    /**
     * Relación con el vehículo.
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
}