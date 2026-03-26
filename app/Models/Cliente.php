<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'alias',
        'rif',
        'contacto',
        'telefono',
        'contacto_alt',
        'telefono_alt',
        'dni',
        'estado_id',
        'ciudad_id',
        'email',
        'direccion',
        'direccion_operativa',
        'ciiu',
        'sector',
        'periodo',
        'parent',
        'token_registro',
        'cupo',
        'disponible',
        'prepagado',
        'registro_paso',
        'status',
        'fecha_aprobacion',
        'telegram_id',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
    ];

    // -------------------------------------------------------
    // CONSTANTES DE STATUS
    // -------------------------------------------------------

    const STATUS_INACTIVO    = 0;
    const STATUS_EN_REGISTRO = 1;
    const STATUS_APROBADO    = 2;
    const STATUS_RECHAZADO   = 3;

    const TOTAL_PASOS = 5;

    // -------------------------------------------------------
    // ACCESSORS
    // -------------------------------------------------------

    public function getNombrePasoActualAttribute(): string
    {
        return $this->registroPaso?->nombre ?? 'Paso Desconocido';
    }

    public function getPorcentajeRegistroAttribute(): float
    {
        return ($this->registro_paso / self::TOTAL_PASOS) * 100;
    }

    public function getEsPadreAttribute(): bool
    {
        return $this->parent == 0 || is_null($this->parent);
    }

    public function getEsAprobadoAttribute(): bool
    {
        return $this->status === self::STATUS_APROBADO;
    }

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_INACTIVO    => 'Inactivo',
            self::STATUS_EN_REGISTRO => 'En Registro',
            self::STATUS_APROBADO    => 'Aprobado',
            self::STATUS_RECHAZADO   => 'Rechazado',
            default                  => 'Desconocido',
        };
    }

    public function getColorStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_INACTIVO    => 'bg-gray-500',
            self::STATUS_EN_REGISTRO => 'bg-blue-600',
            self::STATUS_APROBADO    => 'bg-green-600',
            self::STATUS_RECHAZADO   => 'bg-red-600',
            default                  => 'bg-gray-400',
        };
    }

    // -------------------------------------------------------
    // RELACIONES
    // -------------------------------------------------------

    public function user()
    {
        return $this->hasOne(User::class, 'cliente_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'cliente_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id');
    }

    public function sucursales()
    {
        return $this->hasMany(Cliente::class, 'parent', 'id');
    }

    public function padre()
    {
        return $this->belongsTo(Cliente::class, 'parent', 'id');
    }

    public function cupos()
    {
        return $this->hasMany(ClienteCupo::class, 'cliente_id');
    }

    public function placas()
    {
        return $this->hasMany(PlacaVehiculo::class, 'cliente_id');
    }

    public function choferes()
    {
        return $this->hasMany(ChoferCliente::class, 'cliente_id');
    }

    public function registroPaso()
    {
        return $this->belongsTo(RegistroPaso::class, 'registro_paso');
    }

    // -------------------------------------------------------
    // SCOPES
    // -------------------------------------------------------

    public function scopeAprobados($query)
    {
        return $query->where('status', self::STATUS_APROBADO);
    }

    public function scopeEnRegistro($query)
    {
        return $query->where('status', self::STATUS_EN_REGISTRO);
    }

    public function scopeInactivos($query)
    {
        return $query->where('status', self::STATUS_INACTIVO);
    }

    public function scopeRechazados($query)
    {
        return $query->where('status', self::STATUS_RECHAZADO);
    }

    public function scopePadres($query)
    {
        return $query->where('parent', 0);
    }

    public function scopeSucursales($query)
    {
        return $query->where('parent', '>', 0);
    }
}