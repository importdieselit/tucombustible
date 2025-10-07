<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use App\Models\Perfil;
use App\Models\Persona;
use App\Models\PermisoUsuario;
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_perfil',
        'id_persona',
        'cliente_id',
        'fcm_token',
        'id_master',
        'status'

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

    public function canAccess(string $action, int $moduleId): bool
    {
        // 1. Verificar Permiso Asignado DIRECTAMENTE (tabla 'accesos')
        // La tabla 'accesos' es tu tabla de Permisos Específicos por Usuario.
        
        $hasDirectAccess = DB::table('accesos')
                            ->where('id_usuario', $this->id)
                            ->where('id_modulo', $moduleId)
                            ->where($action, 1) // Verifica si la columna 'read'/'update'/etc. es true (1)
                            ->exists();

        if ($hasDirectAccess) {
            return true; // Si el permiso específico está en la tabla 'accesos', SE SUMA y es TRUE.
        }

        // 2. Verificar Permiso Base del Perfil (Asumiremos que tienes una tabla para esto)
        // Ejemplo: `permisos_perfiles` tiene 'id_perfil', 'id_modulo', 'read', 'update', etc.
        
        // Obtener el ID del perfil base del usuario (asumiendo que $this->id_perfil existe)
        $profileId = $this->id_perfil ?? null; 

        if ($profileId) {
            $hasProfileAccess = DB::table('permiso_perfil')
                                  ->where('id_perfil', $profileId)
                                  ->where('id_modulo', $moduleId)
                                  ->where($action, 1)
                                  ->exists();
            
            if ($hasProfileAccess) {
                return true; // Si el permiso está en el perfil base, es TRUE.
            }
        }

        // 3. Ningún permiso encontrado
        return false;
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

    public function index()
    {
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para ver el dashboard de usuarios.');
        }
        
        // Obtener el ID del cliente del usuario autenticado
        $clienteId = auth()->user()->cliente_id;
        
        // 1. Consulta el conteo de usuarios por perfil.
        // Usamos la tabla 'users' directamente.
        $perfilesConteo = DB::table('users')
            ->select('perfil', DB::raw('COUNT(*) as total'))
            // 2. Aplicar el filtro de seguridad de cliente
            // Esto asume que tienes la columna 'cliente_id' en tu tabla 'users'
            ->when($clienteId !== 0, function ($query) use ($clienteId) {
                // Lógica de seguridad: solo mostrar usuarios del mismo cliente (y sus hijos, si aplica)
                // (Esta lógica debería coincidir con la de BaseController::list)
                $query->where('cliente_id', $clienteId); 
                
                // NOTA: Si necesitas incluir clientes hijos, la lógica debe ser más compleja, 
                // pero para el dashboard, un filtro directo por cliente_id es más seguro.
            })
            ->groupBy('perfil')
            ->orderBy('total', 'desc')
            ->get();

        // 3. Obtener el total general para la card principal.
        $totalGeneral = $perfilesConteo->sum('total');

        return view('usuarios.index', compact('perfilesConteo', 'totalGeneral'));
    }
}