<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespachoViaje extends Model
{
    use HasFactory;

    protected $table = 'despachos_viajes';

    protected $fillable = [
        'viaje_id',
        'cliente_id',
        'otro_cliente', // Para clientes no registrados
        'litros',
    ];

    /**
     * Relación con el Viaje al que pertenece el despacho.
     */
    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class, 'viaje_id'); 
    }

    /**
     * Relación con el Cliente registrado (si aplica).
     */
    public function cliente(): BelongsTo
    {
        // Asumiendo que existe un modelo 'Cliente' y la clave foránea es 'cliente_id'
        return $this->belongsTo(Cliente::class, 'cliente_id'); 
    }
}
