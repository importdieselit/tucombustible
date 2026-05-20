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

    public const STATUS_PENDIENTE  = 'pendiente';
    public const STATUS_APROBADO   = 'aprobado';
    public const STATUS_RECHAZADO  = 'rechazado';
    public const STATUS_CANCELADO  = 'cancelado';

    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class, 'cliente_id'); }
    public function deposito(): BelongsTo { return $this->belongsTo(Deposito::class, 'deposito_id'); }
    public function vehiculo(): BelongsTo { return $this->belongsTo(Vehiculo::class, 'vehiculo_id'); }
    public function chofer(): BelongsTo { return $this->belongsTo(Chofer::class, 'chofer_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function getEstadoTextAttribute(): string
    {
        $map = [
            self::STATUS_PENDIENTE  => 'Pendiente',
            self::STATUS_APROBADO   => 'Aprobado',
            self::STATUS_RECHAZADO  => 'Rechazado',
            self::STATUS_CANCELADO  => 'Cancelado',
        ];
        return $map[$this->estado] ?? ucfirst($this->estado);
    }

    public function getEstadoColorAttribute(): string
    {
        switch ($this->estado) {
            case self::STATUS_PENDIENTE: return '#FFA500'; // Naranja
            case self::STATUS_APROBADO:  return '#4CAF50'; // Verde
            case self::STATUS_RECHAZADO: return '#F44336'; // Rojo
            case self::STATUS_CANCELADO: return '#4B5563'; // Gris
            default: return '#9E9E9E';
        }
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCombustible::class, 'pedido_id');
    }

    public function tipoCombustible() 
    { 
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id'); 
    }
}