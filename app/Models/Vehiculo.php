<?php

namespace  App\Models; // O el namespace donde tengas tus modelos, por ejemplo, App\Models si usas Laravel 7+

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Asegúrate de que el modelo User esté correctamente importado
use App\Models\Marca; // Asegúrate de que el modelo Marca esté correctamente importado
use App\Models\Modelo; // Asegúrate de que el modelo Modelo esté correctamente importado
use App\Models\TipoVehiculo; // Asegúrate de que el modelo  
use App\Models\Cliente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\FiltroPorCliente;
use App\Models\PlanMantenimiento;
use App\Models\Viaje;
use App\Models\CompraCombustible;
use Google\Service\ApigeeRegistry\Build;
use Illuminate\Database\Eloquent\Builder;

class Vehiculo extends Model
{
    use FiltroPorCliente;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehiculos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int'; // bigint unsigned puede ser int o bigint dependiendo de cómo lo maneje Eloquent internamente.

    /**
     * Indicates if the model should be timestamped.
     * En tu tabla tienes `created_at` y `updated_at`.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_cliente',
        'estatus',
        'flota',
        'marca',
        'modelo',
        'placa',
        'tipo', // Asumiendo que es un campo separado del id_tipo_vehiculo
        'tipo_diagrama',
        'serial_motor',
        'serial_carroceria',
        'transmision',
        'color',
        'anno',
        'kilometraje',
        'sucursal',
        'ubicacion',
        'ubicacion_1',
        'poliza_numero',
        'poliza_fecha_in',
        'poliza_fecha_out',
        'agencia',
        'observacion',
        'salida_fecha',
        'salida_motivo',
        'salida_id_usuario',
        'fecha_in',
        'vol',
        'km_contador',
        'condicion',
        'km_mantt',
        'cobertura',
        'tipo_poliza',
        'id_poliza',
        'certif_reg',
        'disp',
        'carga_max',
        'fuel',
        'tipo_combustible',
        'HP',
        'CC',
        'altura',
        'ancho',
        'largo',
        'consumo',
        'oil',
        'rotc', // Ejemplo de agregar una nueva columna
        'rotc_venc', 
        'rcv', 
        'racda', 
        'semcamer', 
        'homologacion_intt',
        'permiso_intt',
        'hrs_mantt',
        'hrs_contador',
        'horas_trabajo'
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id_usuario' => 'integer', // bigint unsigned
        'marca' => 'integer', // bigint unsigned
        'modelo' => 'integer', // bigint unsigned
        'estatus' => 'integer',
        'kilometraje' => 'integer',
        'sucursal' => 'integer',
        'ubicacion' => 'integer',
        'salida_id_usuario' => 'integer',
        'km_contador' => 'integer',
        'km_mantt' => 'integer',
        'HP' => 'integer',
        'CC' => 'integer',
        'vol' => 'float',
        'carga_max' => 'float',
        'fuel' => 'float',
        'cobertura' => 'float',
        'altura' => 'float',
        'ancho' => 'float',
        'largo' => 'float',
        'consumo' => 'float',
        'poliza_fecha_in' => 'date',
        'poliza_fecha_out' => 'date',
        'salida_fecha' => 'date',
        'fecha_in' => 'date',
        'semcamer' => 'string',
        'homologacion_intt' => 'string',
        'permiso_intt' => 'string',
        'rotc' => 'date',
        'rotc_venc' => 'date',
        'rcv' => 'date',
        'racda' => 'date',
        'facturacion_completa' => 'boolean', // Si aplicara, basado en otro contexto si no fuera booleano nativo
    ];

    // Relaciones (si es necesario y tienes los modelos correspondientes)

    /**
     * Get the user that owns the vehiculo.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id'); // Ajusta 'App\User::class' al nombre de tu modelo de Usuario/User
    }

    /**
     * Get the brand associated with the vehiculo.
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'marca', 'id')->first() ; // Ajusta 'App\Marca::class' al nombre de tu modelo de Marca
    }

        public function ordenes()
        {
            // Ajusta 'id_vehiculo' si el nombre de la llave foránea en la tabla 'ordenes' es diferente
            return $this->hasMany(Orden::class, 'id_vehiculo'); 
        }
    public function viajes()
    {
        return $this->hasMany(Viaje::class, 'id_vehiculo');
    }

    public function compraCombustible()
    {
        return $this->hasMany(CompraCombustible::class, 'id_vehiculo');
    }

     public static function countVehiculosEnMantenimiento(): int
    {
        // Llama al Scope de negocio y al Scope de seguridad del cliente, luego cuenta.
        return self::porCliente()->vehiculosEnMantenimiento()->count();
    }

     public function scopeVehiculosEnMantenimiento(Builder $query): void
    {
        $tiposMantenimiento = ['Preventivo', 'Mantenimiento'];

        // $query->whereHas('ordenes', function ($q) use ($tiposMantenimiento) {
        //     // Estatus de orden abierta y tipo de mantenimiento
        //     $q->where('estatus', 5)->where('es_flota', true)
        //       ->whereIn('tipo', $tiposMantenimiento);
        // });
        
         $query->where('estatus', 5)->where('es_flota', true);
    }

    /**
     * Scope: Filtra vehículos con CUALQUIER tipo de orden abierta (estatus = 2).
     * Uso: Vehiculo::vehiculosConOrdenAbierta()
     */
    public function scopeVehiculosConOrdenAbierta(Builder $query): void
    {
        // $query->whereHas('ordenes', function ($q) {
        //     $q->whereIn('estatus', [3,5])->where('es_flota', true);
        // });
        $query->whereIn('estatus', [3,5])->where('es_flota', true);
    }

    /**
     * Scope: Filtra vehículos que están listos para trabajar (asumimos estatus = 1).
     * Uso: Vehiculo::disponibles()
     */
    public function scopeDisponibles(Builder $query): void
    {
        // Ajustar el estatus según tu lógica de "Disponible"
        $query->where('estatus', 1)->where('es_flota', true);
    }

    public function scopeEnServicio(Builder $query): void
    {
        // Ajustar el estatus según tu lógica de "Disponible"
        $query->where('estatus', 2)->where('es_flota', true);
    }
    
    /**
     * Scope: Filtra vehículos que tienen documentos vencidos, próximos a vencer o sin registrar (S/P).
     * Nota: La lógica es compleja y asumo las columnas de fecha más importantes.
     * Uso: Vehiculo::conDocumentosEnAlerta()
     */
    public function scopeConDocumentosEnAlerta(Builder $query): void
    {
        $today = Carbon::now()->toDateString();
        $date30Days = Carbon::now()->addDays(30)->toDateString();
        $dateFields = ['rotc_venc', 'poliza_fecha_out', 'rcv', 'racda', 'permiso_intt']; 

        $query->where(function ($q) use ($dateFields, $today, $date30Days) {
            foreach ($dateFields as $field) {
                // OR: Vencidos (fecha < hoy) O Próximos a vencer (entre hoy y +30 días)
                $q->orWhere($field, '<', $today);
                $q->orWhereBetween($field, [$today, $date30Days]);
                // OR: No registrado/Alerta de texto (Nulo o S/P)
                $q->orWhereNull($field); 
                $q->orWhere($field, 'like', '%S/P%');
            }
        });
    }

    /**
     * Get the model associated with the vehiculo.
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class, 'modelo', 'id')->first(); // Ajusta 'App\Modelo::class' al nombre de tu modelo de Modelo
    }

    public function tipoVehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo', 'id'); // Ajusta 'App\TipoVehiculo::class' al nombre de tu modelo de TipoVehiculo
    } 

    public static function misVehiculos()
    {
        // Llama al Scope 'porCliente' ANTES de realizar el conteo.
        // El Scope ya tiene toda la lógica de seguridad y jerarquía.
        return self::porCliente();
    }
    public static function miFlota()
    {
        // Llama al Scope 'porCliente' ANTES de realizar el conteo.
        // El Scope ya tiene toda la lógica de seguridad y jerarquía.
        return self::porCliente()->where('es_flota',true);
    }
    
    

      /**
     * Evalúa el estatus de un documento basado en su campo de fecha o texto.
     * @param string|null $dateField Nombre del campo de fecha (ej: 'poliza_fecha_out').
     * @param string|null $textField Nombre del campo de texto (ej: 'sencammer').
     * @param string $docName Nombre del documento para el título (ej: 'Póliza').
     * @return array
     */
  
    /**
     * Evalúa el estatus de un documento basado en su campo de fecha o texto.
     */
    public function getDocumentStatus(string $docName, ?string $dateField = null, ?string $textField = null): array
    {
        // 1. Obtener el valor crudo del campo, ya sea de fecha o texto
        $rawValue = $dateField ? ($this->{$dateField} ?? '') : ($this->{$textField} ?? '');
        $statusValue = trim(mb_strtoupper($rawValue));
        // ==========================================================
        // 2. VERIFICACIÓN DEFENSIVA Y MANEJO DE ESTATUS DE TEXTO
        //    (Maneja S/P y N/A primero, sin importar si es dateField o textField)
        // ==========================================================
        if (in_array($statusValue, ['S/P', 'SIN PERMISO', 'NO REGISTRADO'])) {
            return [
                'class' => 'bg-danger', 
                'icon' => 'bi-x-octagon-fill', 
                'title' => "$docName: ¡Sin Permiso (S/P)! Dato: {$rawValue}",
            ];
        }
        
        if (in_array($statusValue, ['N/A', 'NO APLICA', 'NO VENCE', 'OK', 'VIGENTE'])) {
            return [
                'class' => 'bg-success', 
                'icon' => 'bi-check-circle', 
                'title' => "$docName: Vigente / No aplica / Status OK. Dato: {$rawValue}",
            ];
        }

        // Si es un campo de texto y el valor no fue un estatus conocido, lo marcamos como indefinido
        if ($textField && !empty($statusValue)) {
            return [
                'class' => 'bg-secondary', 
                'icon' => 'bi-question-circle', 
                'title' => "$docName: Estatus de texto no definido: {$rawValue}",
            ];
        }

        // ==========================================================
        // 3. MANEJO DE ESTATUS POR FECHA (Solo si no fue un estatus de texto conocido)
        // ==========================================================
        
        // Si no hay valor o no es un campo de fecha, salimos
        if (!$dateField || empty($rawValue)) {
            return [
                'class' => 'bg-secondary', 
                'icon' => 'bi-slash-circle', 
                'title' => "$docName: Fecha de vigencia no registrada",
            ];
        }

        // INTENTAR PARSEAR LA FECHA
        try {
            $date = Carbon::parse($rawValue)->startOfDay();
        } catch (\Exception $e) {
            // CATCH: Si Carbon falla aquí (ej. la fecha está en un formato raro), marcamos error.
            return [
                'class' => 'bg-danger', 
                'icon' => 'bi-x-circle', 
                'title' =>  "$docName: Error de Formato. El valor '{$rawValue}' no es una fecha válida.",
            ];
        }

        // Lógica de fechas (Vigente, Warning, Vencida)
        $now = Carbon::now()->startOfDay();
        $oneMonthFromNow = $now->copy()->addMonth();

        if ($date->lessThan($now)) {
            // Vencida
            return [
                'class' => 'bg-danger', 
                'icon' => 'bi-x-circle', 
                'title' => "$docName: Vencida desde el {$date->format('d/m/Y')}",
            ];
        } elseif ($date->lessThan($oneMonthFromNow)) {
            // Próximo a vencer (Warning)
            return [
                'class' => 'bg-warning', 
                'icon' => 'bi-exclamation-triangle-fill', 
                'title' => "$docName: Vence pronto el {$date->format('d/m/Y')}",
            ];
        } else {
            // Vigente
            return [
                'class' => 'bg-success', 
                'icon' => 'bi-check-circle', 
                'title' => "$docName: Vigente hasta {$date->format('d/m/Y')}",
            ];
        }
    }

    public static function getUnidadesConDocumentosVencidos($user)
    {
        $cliente = Cliente::find($user);
          
        // 1. Definir los límites de tiempo para la consulta SQL
        $today = Carbon::now()->toDateString();
        $date30Days = Carbon::now()->addDays(30)->toDateString();

        // 2. Definir los campos por tipo
        // Campos que contienen FECHAS (pueden tener 'S/P' como texto)
        $dateFields = ['poliza_fecha_out', 'rcv', 'racda', 'rotc_venc', 'permiso_intt'];
        // Campos de ESTATUS TEXTUAL (SENCAMMER, Homologación INTT)
        $textFields = ['semcamer', 'homologacion_intt'];
        $statusOk = ['N/A', 'NO APLICA', 'NO VENCE', 'OK', 'VIGENTE','',null]; // Estatus que NO requieren atención

        $totalUnidadesConAlertas = Vehiculo::where(function ($query) use ($dateFields, $textFields, $today, $date30Days, $statusOk) {
            
            // --- LÓGICA DE DOCUMENTOS CON FECHA ---
            foreach ($dateFields as $field) {
                $query->orWhere(function ($q) use ($field, $today, $date30Days) {
                    
                    // 1. CONDICIÓN DE VENCIMIENTO/WARNING (Fechas en el pasado o dentro de 30 días)
                    $q->where(function ($subQ) use ($field, $today, $date30Days) {
                        $subQ->where($field, '<', $today) // Vencido (Danger)
                            ->orWhereBetween($field, [$today, $date30Days]); // Próximo a vencer (Warning)
                    });

                    // 2. CONDICIÓN DE TEXTO DE PELIGRO ('S/P' o 'SIN PERMISO')
                    $q->orWhere($field, 'like', '%S/P%'); 
                    $q->orWhere($field, 'like', '%SIN PERMISO%');

                    // 3. CONDICIÓN DE NO REGISTRADO (Secondary)
                    $q->orWhereNull($field); 
                });
            }

            // --- LÓGICA DE DOCUMENTOS DE ESTATUS TEXTUAL (S/P, Vacíos, o no OK) ---
            foreach ($textFields as $field) {
                $query->orWhere(function ($q) use ($field, $statusOk) {
                    
                    // 1. CONDICIÓN DE TEXTO DE PELIGRO ('S/P')
                    $q->where($field, 'like', '%S/P%');
                    $q->orWhere($field, 'like', '%SIN PERMISO%');
                    
                    // 2. CONDICIÓN DE NO REGISTRADO (Secondary)
                    $q->orWhereNull($field); 

                    // 3. CONDICIÓN DE TEXTO NO VÁLIDO (Existe un valor, pero NO es un estatus OK)
                    $q->orWhere(function ($subQ) use ($field, $statusOk) {
                        $subQ->whereNotNull($field)
                            // Comparamos el valor de la columna en MAYÚSCULAS y sin espacios para mayor robustez
                            ->whereNotIn(DB::raw('UPPER(TRIM(' . $field . '))'), $statusOk);
                    });
                });
            }
        });
         if ($user === 0) {
                // 1. SUPER USUARIO (cliente_id == 0)
                // No se aplica ningún filtro, obtiene todos los registros.
            } elseif ($cliente && $cliente->parent === 0) {
                // 2. CLIENTE PRINCIPAL / PADRE
                $subClientIds = Cliente::where('parent', $user)->pluck('id'); 
                $allowedClientIds = $subClientIds->push($user);
                $totalUnidadesConAlertas->whereIn('id_cliente', $allowedClientIds);

            } else {
                // 3. CLIENTE HIJO o CLIENTE REGULAR SIN JERARQUÍA
                $totalUnidadesConAlertas->where('id_cliente', $user);
            }
    

     return $totalUnidadesConAlertas;
    }

    
}