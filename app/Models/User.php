<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use NotificationChannels\WebPush\HasPushSubscriptions;
use App\Models\Perfil;
use App\Models\Persona;
use App\Models\PermisoUsuario;
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_perfil',
        'id_persona',
        'id_sede',
        'cliente_id',
        'fcm_token',
        'id_master',
        'status',      
        'telegram_id',
        'telegram_username',
        'ultimo_login'

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

    public function getPersonaAttribute()
    {
        return $this->persona()->first();
    }
    public function getPerfilAttribute()
    {
        return $this->perfil()->first();
    }
    public function personalData()
    {
        return $this->getPersonaAttribute()->personal()->first();
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

    public function canAccess(string $action, int $moduleId): bool
    {
        // 1. El súper usuario (ID 1) es el dueño del sistema. Pasa directo.
        // Cero consultas a la base de datos, cero registros necesarios en 'permiso_perfil'.
        if ($this->id_perfil === 1) {
            return true; 
        }

        // Si ni siquiera tiene perfil, para afuera.
        if (!$this->id_perfil) {
            return false;
        }

        // 2. Verificar Permiso del Perfil Base
        // Si lo encuentra, retorna true y corta la ejecución (ahorrando la siguiente consulta)
        $hasProfileAccess = DB::table('permiso_perfil')
            ->where('id_perfil', $this->id_perfil)
            ->where('id_modulo', $moduleId)
            ->where($action, 1)
            ->exists();

        if ($hasProfileAccess) {
            return true;
        }

        // 3. Verificar Permiso Directo (Específico del usuario en 'accesos')
        // Si llegó hasta aquí, solo retorna el resultado de esta última validación
        return DB::table('accesos')
            ->where('id_usuario', $this->id)
            ->where('id_modulo', $moduleId)
            ->where($action, 1)
            ->exists();
    }



    /**
     * Método para asignar permisos extra a un usuario específico.
     * @param array $permissions Array de permisos (ej: ['create-flota', 'view-finanzas']).
     */
     public function syncSpecificPermissions(array $modulePermissions): void
    {
        // 1. Eliminar todos los permisos específicos existentes para este usuario
        DB::table('accesos')->where('id_usuario', $this->id)->delete();

        $dataToInsert = [];
        $now = now();

        foreach ($modulePermissions as $moduleId => $actions) {
            // Inicializar todas las acciones a 0 (FALSE)
            $row = [
                'id_usuario' => $this->id,
                'id_modulo'  => $moduleId,
                'read'       => 0,
                'update'     => 0,
                'create'     => 0,
                'delete'     => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Establecer a 1 (TRUE) solo las acciones presentes en el array $actions
            foreach ($actions as $action) {
                if (array_key_exists($action, $row)) {
                    $row[$action] = 1;
                }
            }
            
            $dataToInsert[] = $row;
        }

        // 2. Insertar los nuevos registros específicos
        if (!empty($dataToInsert)) {
            DB::table('accesos')->insert($dataToInsert);
        }
    }


    // Relación con el filtro de permisos específicos por usuario (tabla 'accesos')
    public function permisosAdicionales()
    {
        // Asume que la tabla 'accesos' es tu tabla pivote para permisos extra
        return $this->hasMany(Acceso::class, 'id_usuario', 'id');
    }

    /**
     * Get the cliente that owns the user.
     * Los clientes son usuarios con id_perfil = 3 (cliente)
     * La información del cliente está en la tabla personas relacionada
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
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
        if ($this->persona && $this->persona->first_name) {
            return $this->persona->first_name . ' ' . $this->persona->last_name;
        }
    
        // Si es un prospecto o no tiene persona vinculada, usa el nombre de la tabla users
        return $this->name ?? 'Cliente Nuevo';
    }

    /**
     * Get the role of the user.
     */
    public function getRoleAttribute(): string
    {
        return $this->perfil ? $this->perfil->nombre : 'Usuario';
    }
    
    public function modulos()
    {
        // Mapeamos a la tabla 'accesos' definiendo las columnas del pivote
        return $this->belongsToMany(Modulo::class, 'accesos', 'id_usuario', 'id_modulo')
                    ->withPivot('read', 'update', 'create', 'delete')
                    ->withTimestamps();
    }


    public function getPersonaParentAttribute()
    {
        if ($this->persona) {
            return $this->persona->parent;
        }
        return null; // Si no hay persona, devuelve null en lugar de dar error
    }

    public function hijos()
    {
        return $this->hasMany(Modulo::class, 'id_padre', 'id');
    }

    /**
     * Obtiene el módulo padre al que pertenece.
     */
    public function padre()
    {
        return $this->belongsTo(Modulo::class, 'id_padre', 'id');
    }
}