<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'cliente_id',
        'deposito_id',
        'vehiculo_id',
        'cantidad_solicitada',
        'cantidad_aprobada',
        'cantidad_recibida',
        'estado',
        'observaciones',
        'observaciones_admin',
        'fecha_solicitud',
        'fecha_aprobacion',
        'fecha_completado',
        'calificacion',
        'comentario_calificacion',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:2',
        'cantidad_aprobada' => 'decimal:2',
        'cantidad_recibida' => 'decimal:2',
        'fecha_solicitud' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'fecha_completado' => 'datetime',
        'calificacion' => 'integer',
    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
 public function getEstadoTextAttribute(): string
    {
        switch ($this->estado) {
            case 'pendiente':
                return 'Pendiente';
            case 'aprobado':
                return 'Aprobado';
            case 'rechazado':
                return 'Rechazado';
            case 'en_proceso':
                return 'En Proceso';
            case 'completado':
                return 'Completado';
            case 'cancelado':
                return 'Cancelado';
            default:
                return 'Desconocido';
        }
    }

         
    public function getEstadoColorAttribute(): string
    {
        switch ($this->estado) {
            case 'pendiente':
                return '#FFA500'; // Naranja
            case 'aprobado':
                return '#4CAF50'; // Verde
            case 'rechazado':
                return '#F44336'; // Rojo
            case 'en_proceso':
                return '#2196F3'; // Azul
            case 'completado':
                return '#4CAF50'; // Verde
            case 'cancelado':
                return '#9E9E9E'; // Gris
            default:
                return '#9E9E9E';
        }
    }

    public function getPuedeCalificarAttribute(): bool
    {
        return $this->estado === 'completado' && $this->calificacion === null;
    }

    public function getFechaSolicitudFormateadaAttribute(): string
    {
        return $this->fecha_solicitud->format('d/m/Y H:i');
    }

     public function getFechaAprobacionFormateadaAttribute(): string
    {
        // Usamos un 'if' para ser más explícitos y manejar cualquier posible valor no válido.
        if ($this->fecha_aprobacion instanceof Carbon) {
            return $this->fecha_aprobacion->format('d/m/Y H:i');
        }

        // Si el valor es null, no es una instancia de Carbon, o es inválido,
        // devolvemos 'N/A'.
        return 'N/A';
    }

    public function getFechaCompletadoFormateadaAttribute(): string
    {
        if ($this->fecha_completado instanceof Carbon) {
            return $this->fecha_completado->format('d/m/Y H:i');
        }

        // Si el valor es null, no es una instancia de Carbon, o es inválido,
        // devolvemos 'N/A'.
        return 'N/A';
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
