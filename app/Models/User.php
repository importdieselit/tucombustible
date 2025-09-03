<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_perfil',
        'id_persona',
        'id_cliente',
        'fcm_token',
        'id_master'

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Un usuario tiene un perfil principal.
     */
    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'id_perfil');
    }

    /**
     * Un usuario está asociado a una persona.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    // ELIMINADO: La relación conductor() ya no va aquí, Conductor se relaciona con Persona.
    // public function conductor() { return $this->hasOne(Conductor::class); }

    /**
     * Un usuario puede tener permisos especiales directos.
     */
    public function permisosDirectos()
    {
        return $this->hasMany(PermisoUsuario::class, 'users_id');
    }

    // --- Relaciones existentes (mantener) ---
    public function vehicles()
    {
        // Si la relación current_driver_id en vehicles apunta a users.id,
        // y quieres que apunte a personas.id, necesitarás otra migración y ajuste aquí.
        // Por ahora, asumimos que current_driver_id en vehicles sigue apuntando a users.id
        // y que User::find(driver_id)->persona->conductor es la forma de acceder al conductor.
        return $this->hasMany(Vehicle::class, 'current_driver_id');
    }

    public function vehicleStatusLogs()
    {
        return $this->hasMany(VehicleStatusLog::class, 'user_id');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class, 'performed_by_user_id');
    }

    public function approvedMaintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class, 'approved_by_user_id');
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'inspected_by_user_id');
    }

    public function createdPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by_user_id');
    }

    public function requestedOutgoingOrders()
    {
        return $this->hasMany(OutgoingOrder::class, 'requested_by_user_id');
    }

    public function approvedOutgoingOrders()
    {
        return $this->hasMany(OutgoingOrder::class, 'approved_by_user_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'user_id');
    }

    public function stockCounts()
    {
        return $this->hasMany(StockCount::class, 'counted_by_user_id');
    }

    /**
     * Get the cliente that owns the user.
     * Los clientes son usuarios con id_perfil = 3 (cliente)
     * La información del cliente está en la tabla personas relacionada
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
    }



    /**
     * Get the master user.
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_master');
    }

    

    /**
     * Get the full name of the user.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->persona) {
            return $this->persona->first_name . ' ' . $this->persona->last_name;
        }
        return $this->name;
    }

    /**
     * Get the role of the user.
     */
    public function getRoleAttribute(): string
    {
        return $this->perfil ? $this->perfil->nombre : 'Usuario';
    }
}
