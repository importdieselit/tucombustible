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
        'dni',
        'telefono',
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

    const STATUS_INACTIVO  = 0;
    const STATUS_EN_REGISTRO = 1;
    const STATUS_APROBADO  = 2;
    const STATUS_RECHAZADO = 3;

    const TOTAL_PASOS = 5;

    // -------------------------------------------------------
    // ACCESSORS
    // -------------------------------------------------------

    /**
     * Devuelve el nombre del paso actual consultando la BD.
     * Usa eager loading si ya se cargó la relación, evita N+1.
     */
    public function getNombrePasoActualAttribute(): string
    {
        return $this->registroPaso?->nombre ?? 'Paso Desconocido';
    }

    /**
     * Porcentaje de avance en el proceso de registro (sobre 5 pasos).
     */
    public function getPorcentajeRegistroAttribute(): float
    {
        return ($this->registro_paso / self::TOTAL_PASOS) * 100;
    }

    /**
     * Indica si el cliente es un Cliente Padre.
     */
    public function getEsPadreAttribute(): bool
    {
        return $this->parent == 0 || is_null($this->parent);
    }

    /**
     * Indica si el cliente está aprobado.
     */
    public function getEsAprobadoAttribute(): bool
    {
        return $this->status === self::STATUS_APROBADO;
    }

    /**
     * Etiqueta legible del status actual.
     */
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

    /**
     * Color CSS asociado al status (para badges en vistas).
     */
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

    /**
     * El cliente tiene un usuario asociado.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'cliente_id');
    }

    /**
     * El cliente tiene muchos documentos.
     * (Lógica interna — no expuesta en vistas por ahora)
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'cliente_id');
    }

    /**
     * El cliente pertenece a un estado.
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    /**
     * El cliente pertenece a una ciudad.
     */
    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id');
    }

    /**
     * Un Cliente Padre tiene muchas Sucursales.
     */
    public function sucursales()
    {
        return $this->hasMany(Cliente::class, 'parent', 'id');
    }

    /**
     * Un Cliente Sucursal pertenece a un Cliente Padre.
     */
    public function padre()
    {
        return $this->belongsTo(Cliente::class, 'parent', 'id');
    }

    /**
     * El cliente tiene muchos cupos de combustible.
     */
    public function cupos()
    {
        return $this->hasMany(ClienteCupo::class, 'cliente_id');
    }

    /**
     * El cliente tiene muchas placas de vehículos registradas.
     */
    public function placas()
    {
        return $this->hasMany(PlacaVehiculo::class, 'cliente_id');
    }

    /**
     * El cliente tiene muchos choferes registrados.
     */
    public function choferes()
    {
        return $this->hasMany(ChoferCliente::class, 'cliente_id');
    }

    /**
     * El cliente pertenece a un paso del registro.
     */
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

    public function movimientosCombustible()
    {
        return $this->hasMany(MovimientoCombustible::class, 'cliente_id','id');
    }
}