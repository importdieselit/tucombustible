<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trasegado extends Model 
{
    protected $table = 'trasegados';
    
    protected $fillable = [
        'tipo_trasegado', 
        'sede_origen_id', 
        'deposito_origen_id', 
        'bolsa_origen_tipo',
        'sede_destino_id', 
        'deposito_destino_id', 
        'bolsa_destino_tipo',
        'cliente_id', 
        'tipo_combustible_id', 
        'cantidad_litros', 
        'user_id', 
        'status', 
        'observaciones'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que registró el movimiento
     */
    public function user(): BelongsTo { 
        return $this->belongsTo(User::class, 'user_id'); 
    }

    /**
     * Relaciones del Origen
     */
    public function sedeOrigen(): BelongsTo { 
        return $this->belongsTo(Sedes::class, 'sede_origen_id'); 
    }
    
    public function depositoOrigen(): BelongsTo { 
        // Si tu modelo de tanques se llama 'Tanque', cambia Deposito::class por Tanque::class
        return $this->belongsTo(Deposito::class, 'deposito_origen_id'); 
    }

    /**
     * Relaciones del Destino
     */
    public function sedeDestino(): BelongsTo { 
        return $this->belongsTo(Sedes::class, 'sede_destino_id'); 
    }
    
    public function depositoDestino(): BelongsTo { 
        return $this->belongsTo(Deposito::class, 'deposito_destino_id'); 
    }

    public function aliado() {
        return $this->belongsTo(Cliente::class, 'cliente_id')
                    ->where('es_aliado_comercial', true);
    }
    
    public function tipoCombustible(): BelongsTo { 
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id'); 
    }

    /**
     * ACCESOR: Mapea automáticamente 'cantidad_litros' cuando la vista pida 'litros'
     */
    public function getLitrosAttribute()
    {
        return $this->cantidad_litros;
    }
}