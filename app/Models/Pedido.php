<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    // Se eliminaron los campos marítimos porque los pedidos del portal son exclusivos para Diesel
    protected $fillable = [
        'cliente_id', 'user_id', 'deposito_id', 'vehiculo_id', 'chofer_id',
        'cantidad_solicitada', 'cantidad_aprobada', 'cantidad_recibida',
        'estado', 'observaciones', 'observaciones_admin',
        'fecha_solicitud', 'fecha_aprobacion', 'fecha_completado',
        'fecha_entrega', 'direccion_despacho', 
        'calificacion', 'comentario_calificacion'
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:2',
        'cantidad_aprobada'   => 'decimal:2',
        'cantidad_recibida'   => 'decimal:2',
        'fecha_solicitud'     => 'datetime',
        'fecha_aprobacion'    => 'datetime',
        'fecha_completado'    => 'datetime',
        'fecha_entrega'       => 'date',
        'calificacion'        => 'integer',
    ];

    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class, 'cliente_id'); }
    public function deposito(): BelongsTo { return $this->belongsTo(Deposito::class, 'deposito_id'); }
    public function vehiculo(): BelongsTo { return $this->belongsTo(Vehiculo::class, 'vehiculo_id'); }
    public function chofer(): BelongsTo { return $this->belongsTo(Chofer::class, 'chofer_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function getEstadoTextAttribute(): string
    {
        $map = [
            'pendiente'  => 'Pendiente',
            'aprobado'   => 'Aprobado',
            'rechazado'  => 'Rechazado',
            'en_proceso' => 'En Proceso',
            'completado' => 'Completado',
            'cancelado'  => 'Cancelado',
        ];
        return $map[$this->estado] ?? ucfirst($this->estado);
    }

    public function getEstadoColorAttribute(): string
    {
        switch ($this->estado) {
            case 'pendiente': return '#FFA500';
            case 'aprobado': return '#4CAF50';
            case 'rechazado': return '#F44336';
            case 'en_proceso': return '#2196F3';
            case 'completado': return '#4CAF50';
            case 'cancelado': return '#9E9E9E';
            default: return '#9E9E9E';
        }
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCombustible::class, 'pedido_id');
    }
}