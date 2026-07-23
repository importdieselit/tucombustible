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
use Illuminate\Support\Facades\Log;

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
        'horas_trabajo',
        'facturacion_completa',
        'es_flota',
        'acoplado_id',
        'chofer_id',
        'latitud',
        'longitud',
        'fecha_salida_real',
        'fecha_llegada',
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
        'rotc' => 'string',
        'rotc_venc' => 'date',
        'rcv' => 'date',
        'racda' => 'string',
        'facturacion_completa' => 'boolean', // Si aplicara, basado en otro contexto si no fuera booleano nativo
        'acoplado_id' => 'integer', // bigint unsigned
        'chofer_id' => 'integer', // bigint unsigned
        'fecha_salida_real' => 'datetime',
        'fecha_llegada' => 'datetime',
    ];

    public $ignorarEnBitacora = ['latitud', 'longitud','updated_at'];

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

    public function isMarca()
    {
        return $this->belongsTo(Marca::class, 'marca', 'id'); // Ajusta 'App\Marca::class' al nombre de tu modelo de Marca
    }

    public function ordenes()
    {
        // Ajusta 'id_vehiculo' si el nombre de la llave foránea en la tabla 'ordenes' es diferente
        return $this->hasMany(Orden::class, 'id_vehiculo'); 
    }

    public function getDiasFueraServicioAttribute()
    {
        $orden = $this->ordenActiva;
        if ($orden && $orden->created_at) {
            return now()->diffInDays($orden->created_at);
        }
        return 0;
    }

    public function viajes()
    {
        return $this->hasMany(Viaje::class, 'vehiculo_id');
    }

    public function chofer()
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    public function ordenActiva()
    {
        // Asumiendo que tus órdenes tienen un campo 'estatus' (ej: 1 para abierta)
        return $this->hasOne(Orden::class, 'id_vehiculo')
                    ->where('estatus', 2) 
                    ->orderBy('created_at', 'desc');
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

    public function scopeNoDisponibles(Builder $query): void
    {
        $query->where('estatus', '<>', 1)->where('es_flota', true);
    }

    public function scopeEnServicio(Builder $query): void
    {
        // Ajustar el estatus según tu lógica de "Disponible"
        $query->where('estatus', 2)->where('es_flota', true);
    }

    public function scopeEsFlota(Builder $query): void
    {
       $query->where('es_flota', true);
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

    public function isModelo()
    {
        return $this->belongsTo(Modelo::class, 'modelo', 'id'); // Ajusta 'App\Modelo::class' al nombre de tu modelo de Modelo
    }

    public function tipoVehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo', 'id'); // Ajusta 'App\TipoVehiculo::class' al nombre de tu modelo de TipoVehiculo
    }

    public function planMayorItems()
{
    return $this->belongsToMany(MantenimientoItem::class, 'plan_mayor_controles', 'vehiculo_id', 'mantenimiento_item_id')
                ->withTimestamps();
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
    $rawValue = $dateField ? ($this->{$dateField} ?? '') : ($this->{$textField} ?? '');
    $statusValue = trim(mb_strtoupper($rawValue));

    // 1. MANEJO DE ESTATUS DE TEXTO (Añadimos PENDIENTE aquí)
    if (in_array($statusValue, ['S/P', 'SIN PERMISO', 'NO REGISTRADO', 'PENDIENTE'])) {
        return [
            'class' => 'bg-danger', 
            'icon' => 'bi-x-octagon-fill', 
            'title' => "$docName: ¡Atención! Estado: {$rawValue}",
        ];
    }
    
    if (in_array($statusValue, ['N/A', 'NO APLICA', 'NO VENCE', 'OK', 'VIGENTE'])) {
        return [
            'class' => 'bg-success', 
            'icon' => 'bi-check-circle', 
            'title' => "$docName: Vigente / No aplica. Dato: {$rawValue}",
        ];
    }

    // 2. VALIDACIÓN PRE-CARBON: Si no hay valor o no parece una fecha, salimos de forma segura
    if (!$dateField || empty($rawValue) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $rawValue)) {
        return [
            'class' => 'bg-secondary', 
            'icon' => 'bi-slash-circle', 
            'title' => "$docName: Información no disponible o formato inválido: {$rawValue}",
        ];
    }

    // 3. MANEJO DE FECHAS (Solo si pasó las validaciones anteriores)
    try {
        
        $date = \Carbon\Carbon::parse($rawValue)->startOfDay();
        $now = \Carbon\Carbon::now()->startOfDay();
        $oneMonthFromNow = $now->copy()->addMonth();

        if ($date->lessThan($now)) {
            return [
                'class' => 'bg-danger', 
                'icon' => 'bi-x-circle', 
                'title' => "$docName: Vencida desde el {$date->format('d/m/Y')}",
            ];
        } elseif ($date->lessThan($oneMonthFromNow)) {
            return [
                'class' => 'bg-warning', 
                'icon' => 'bi-exclamation-triangle-fill', 
                'title' => "$docName: Vence pronto el {$date->format('d/m/Y')}",
            ];
        } else {
            return [
                'class' => 'bg-success', 
                'icon' => 'bi-check-circle', 
                'title' => "$docName: Vigente hasta {$date->format('d/m/Y')}",
            ];
        }
    } catch (\Exception $e) {
        return [
            'class' => 'bg-secondary', 
            'icon' => 'bi-question-circle', 
            'title' => "$docName: Error en dato: {$rawValue}",
        ];
    }
}

    /**
 * Agrupa los documentos por su estado de alerta (vencidos o por vencer)
 * Reutiliza getDocumentStatus para mantener un solo punto de verdad.
 */
  
        public function getDocumentosAlertas()
        {
            // 1. Arreglo ordenado por PRIORIDAD
            // Estructura: 'Nombre' => [['campo1', 'campo2'], 'ABREVIATURA', 'TIPO_VALIDACION']
            // Tipos de validación disponibles: 'codigo', 'fecha', 'archivo'
           $documentos = [
                // Formato: [['campo_v', 'campo_c'], 'Abrev_Icono', 'Tipo_Validacion', 'Abrev_Archivo']
                'ROTC'                  => [['rotc_venc', null], 'ROTC', 'fecha', 'ROTC'],
                'RACDA'                 => [['racda', null], 'RACDA', 'codigo', 'RACDA'],
                'Certificado Registro'  => [[null, null], 'CERT', 'archivo', 'CR'],
                'Póliza'                => [['poliza_fecha_out', null], 'POL', 'fecha', 'PS'], // Si usas RCV en lugar de PS, cámbialo aquí a 'RCV'
                'Homologación INTT'     => [[null, 'homologacion_intt'], 'HINT', 'codigo', 'HI'],
                'Permiso INTT'          => [['permiso_intt', null], 'PINT', 'fecha', 'PINT'],
                'SENCAMER'              => [[null, 'semcamer'], 'SCMR', 'codigo', 'SENCAMER'],
            ];

            $alertas = collect();

            foreach ($documentos as $label => $data) {
                $fields = $data[0];
                $abreviatura = $data[1];
                $tipoValidacion = $data[2];
                $abreviaturaArchivo = $config[3] ?? $abreviatura;

                //Para Vehículos Ligeros (Tipo 6)
                // SOLO se validan Certificado (CERT) y Póliza (POL). Se ignoran los demás.
                if ($this->tipo == 6 && !in_array($abreviatura, ['CERT', 'POL'])) {
                    continue; // Saltamos a la siguiente iteración, ignorando este documento
                }

                // Regla: Para el resto de tipos, SENCAMER (SCMR) y Homologación INTT (HINT) 
                // SOLO aplican a Tipos 2 y 5 (Cisternas y Camiones)
                if (in_array($abreviatura, ['SCMR', 'HINT'])) {
                    if (!in_array($this->tipo, [2, 5])) {
                        continue; // No aplica a este vehículo
                    }
                }

                $class = 'bg-success'; // Por defecto asumimos correcto
                $diasText = "";

                // ---------------------------------------------------------
                // B. VALIDACIÓN TIPO: ARCHIVO FÍSICO (Certificado)
                // ---------------------------------------------------------
                if ($tipoValidacion === 'archivo') {
                    $filename = "{$abreviaturaArchivo}_{$this->id}";
                    $extensions = ['pdf', 'jpg', 'png', 'jpeg'];
                    $fileExists = false;
                    
                    // Variable para almacenar las rutas probadas y debuggear
                    $rutasProbadas = []; 

                    foreach ($extensions as $ext) {
                        // Ruta relativa
                        $testPath = "storage/app/public/vehiculos/{$this->id}/documentos/{$filename}.{$ext}";
                        
                        // Ruta absoluta en el servidor (la que usa PHP para verificar si existe)
                        $rutaAbsoluta = public_path($testPath); 
                        
                        $rutasProbadas[] = $rutaAbsoluta; // Guardamos la ruta en el historial

                        if (file_exists($rutaAbsoluta)) {
                            $fileExists = true;
                            break;
                        }
                    }

                    if (!$fileExists) {
                        $class = 'bg-secondary';
                        
                        // OPCIÓN 1: Debug visual (Muestra las rutas directamente en el SweetAlert)
                        $rutasHtml = implode('<br>', $rutasProbadas);
                        $diasText = "<br>Archivo físico no encontrado.<br><hr><div style='font-size: 10px; color: gray; text-align: left;'><b>DEBUG RUTAS:</b><br>{$rutasHtml}</div>";
                        
                        // OPCIÓN 2: Guardar en el Log de Laravel (storage/logs/laravel.log)
                        Log::warning("Archivo faltante para Vehículo ID: {$this->id}", [
                            'documento' => $abreviaturaArchivo,
                            'rutas_probadas' => $rutasProbadas
                        ]);
                    }
                }elseif ($tipoValidacion === 'codigo') {
                    $campoValidar = $fields[0] ?? $fields[1];
                    $rawValue = $this->{$campoValidar};
                    $valUpper = strtoupper(trim((string)$rawValue));

                    // Caso 1: N/A -> No Aplica (omitir completamente)
                    if ($valUpper === 'N/A') {
                        continue;
                    }

                    // Caso 2: PENDIENTE, N/T, NULL o Vacío -> Alerta de faltante
                    if (empty($rawValue) || in_array($valUpper, ['PENDIENTE', 'N/T', 'NULL'])) {
                        $class = 'bg-secondary';
                        $diasText = "<br>Estatus: " . ($valUpper ?: 'Sin registrar');
                    } else {
                        // Caso 3: Contiene un código válido (ej: N.03-04-TSP-AI-2022-10949)
                        $class = 'bg-success'; 
                    }

                // ---------------------------------------------------------
                // D. VALIDACIÓN TIPO: FECHA (ROTC, Póliza, Permiso INTT)
                // ---------------------------------------------------------
                } elseif ($tipoValidacion === 'fecha') {
                    $campoValidar = $fields[0] ?? $fields[1];
                    $rawValue = $this->{$campoValidar};
                    $valUpper = strtoupper(trim((string)$rawValue));

                    // Caso 1: N/A -> No Aplica (omitir)
                    if ($valUpper === 'N/A') {
                        continue;
                    }

                    // Caso 2: PENDIENTE, N/T, NULL o Vacío
                    if (empty($rawValue) || in_array($valUpper, ['PENDIENTE', 'N/T', 'NULL'])) {
                        $class = 'bg-secondary';
                        $diasText = "<br>Estatus: " . ($valUpper ?: 'Sin fecha registrada');
                    } else {
                        // Caso 3: Evaluación de Fecha
                        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $rawValue)) {
                            try {
                                $fecha = \Carbon\Carbon::parse($rawValue)->startOfDay();
                                $diferencia = \Carbon\Carbon::now()->startOfDay()->diffInDays($fecha, false);

                                if ($diferencia < 0) {
                                    $class = 'bg-danger';
                                    $diasText = "<br><span class='text-danger'>Vencido hace " . abs((int)$diferencia) . " días</span>";
                                } elseif ($diferencia <= 30) { // Margen de 30 días para aviso
                                    $class = 'bg-warning';
                                    $diasText = "<br><span class='text-warning'>Faltan " . (int)$diferencia . " días</span>";
                                } else {
                                    $class = 'bg-success';
                                }
                            } catch (\Exception $e) {
                                $class = 'bg-secondary';
                                $diasText = "<br>Fecha no válida: $rawValue";
                            }
                        } else {
                            $class = 'bg-secondary';
                            $diasText = "<br>Estatus: $rawValue";
                        }
                    }
                }

                // ---------------------------------------------------------
                // E. FILTRO FINAL
                // ---------------------------------------------------------
                // Si el estado es correcto (bg-success), se ignora y no se genera ícono
                if ($class === 'bg-success') {
                    continue;
                }

                $alertas->push((object)[
                    'label'       => $label,
                    'abreviatura' => $abreviatura,
                    'class'       => $class,
                    'tooltip'     => "<b>{$label}</b>" . $diasText
                ]);
            }

            return $alertas;
        }

    public static function getUnidadesConDocumentosVencidos(int $user)
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

    // Relación para el Chuto: Obtener su cisterna
    public function cisternaAcoplada()
    {
        return $this->belongsTo(Vehiculo::class, 'acoplado_id');
    }

    // Relación para la Cisterna: Saber qué chuto la tiene (Inversa)
    public function chutoAsignado()
    {
        return $this->hasOne(Vehiculo::class, 'acoplado_id');
    }
    
    public function choferes()
    {        
         return $this->belongsToMany(Chofer::class, 'vehiculo_chofer', 'vehiculo_id', 'chofer_id')->withTimestamps();
    }

    
}