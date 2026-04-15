<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Checklist;
use App\Models\Inspeccion;
use App\Models\InspeccionImagen;
use App\Models\Vehiculo;
use App\Models\Alerta;
use App\Models\Viaje;
use App\Models\Orden;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmNotificationService;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class ChecklistController extends Controller
{

    protected $fcmService;
    protected $telegramService;

    public function __construct(
        FcmNotificationService $fcmService, 
        TelegramNotificationService $telegramService
    ) {
        $this->fcmService = $fcmService;
        $this->telegramService = $telegramService;
    }

    /**
     * Obtener todos los checklists activos
     * 
     * 
     */
    public function index()
    {
        try {         

            $checklists = Checklist::where('activo', true)
                ->select('id', 'titulo', 'checklist')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Checklists obtenidos exitosamente',
                'data' => $checklists
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener checklists: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un checklist específico con su estructura completa
     */
    public function show($id)
    {
        try {

            $cacheKey = "inspeccion_user_" . auth()->id();
            $vehiculoId = null;
            
            // --- LÓGICA DE REINTENTOS (Polling interno) ---
            $intentos = 0;
            $maxIntentos = 10; //
            
            $checklist = Checklist::where('id', $id)
                ->where('activo', true)
                ->select('id', 'titulo', 'checklist')
                ->first();

            if (!$checklist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checklist no encontrado'
                ], 404);
            }

            $dataResponse = is_array($checklist->checklist) 
            ? $checklist->checklist 
            : json_decode($checklist->checklist, true);

            
                while ($intentos < $maxIntentos) {
                    $vehiculoId = Cache::get($cacheKey);
                    
                    if ($vehiculoId) {
                        break; // ¡Lo encontramos! Salimos del bucle

                    }

                    $intentos++;
                    usleep(500000); // Esperar 0.5 segundos (500,000 microsegundos)
                }

                // Si después de los reintentos no hay vehículoId, 
                // puedes decidir si dar error o enviar el checklist vacío de viajes.
                if ($vehiculoId) {
                    // Consultar datos completos del vehículo
                    $vehiculo = $this->getVehiculoCompleto($vehiculoId);
                    $viajes = Viaje::where('vehiculo_id', $vehiculoId)
                                ->whereDate('fecha_salida', '>=', now())
                                ->get();

                    // --- BLOQUE 1: Inyectar Rutas/Viajes en "Información General" ---
                    if ($viajes->count() > 0) {
                        // IMPORTANTE: Flutter espera List<String>, así que aplanamos a un string simple
                        $opcionesViajes = $viajes->map(function($v) {
                            return "ID-{$v->id} | Ruta: " . ($v->destino_ciudad ?? 'Sin Destino');
                        })->toArray();

                        $valorInicial = $opcionesViajes[0] ?? "";

                        $campoViaje = [
                            "label" => "Seleccione Ruta a Cubrir",
                            "response_type" => "radio",
                            "options" => $opcionesViajes, // Esto es ["String1", "String2"]
                            "value" => (string)$valorInicial, // Forzamos cast a string
                            "col_width" => 12
                        ];
                        // Lo insertamos al final de la primera sección
                        $dataResponse['sections'][0]['items'][] = $campoViaje;
                      //  Log::info("Checklist ID {$id}: Se inyectó campo de selección de ruta con " . count($opcionesViajes) . " opciones para Vehículo ID {$vehiculoId}.");
                    }
                    Log::info("Checklist data ". json_encode($dataResponse) . " para Checklist ID {$id} y Vehículo ID {$vehiculoId} después de inyectar rutas.");

                    // --- BLOQUE 2: Auto-completar "Datos del Vehículo" ---
                    foreach ($dataResponse['sections'][1]['items'] as &$item) {
                        if (isset($item['data_source'])) {
                            // Caso para campos simples (Vehiculo.placa, etc)
                            if (is_string($item['data_source'])) {
                                $campo = str_replace('Vehiculo.', '', $item['data_source']);
                                
                                // Mapeo manual de nombres si los de la API difieren del JSON
                                $mapaAtributos = [
                                    'marca' => 'marca_nombre',
                                    'modelo' => 'modelo_nombre',
                                    'tipo_vehiculo' => 'tipo_nombre',
                                    'version' => 'modelo_nombre', // O el campo que uses para versión
                                    'serial_motor' => 'serial_motor',
                                    'serial_carroceria' => 'serial_carroceria'
                                ];

                                $key = $mapaAtributos[$campo] ?? $campo;
                                $item['value'] = $vehiculo->$key ?? "";
                            }
                        
                            // Caso para campos compuestos (Seguros, Verificación)
                            // if (is_array($item['data_source']) && $item['data_source']['model'] == 'Vehiculo') {
                            //     $statusField = $item['data_source']['status_field'];
                            //     $dateField = $item['data_source']['date_field'];
                                
                            //     $item['value'] = [
                            //         "status" => $vehiculo->$statusField ?? false,
                            //         "vigencia" => $vehiculo->$dateField ?? ""
                            //     ];
                            // }
                        }
                    }
                }

            Log::info("Checklist ID {$id} solicitado por Usuario ID " . auth()->id() . ". Vehículo ID en cache: " . ($vehiculoId ?? 'No encontrado') . ". Intentos de polling: {$intentos}");

            return response()->json([
                'success' => true,
                'message' => 'Checklist obtenido exitosamente',
                'data' => $checklist
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener checklist: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener checklist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar y guardar la inspección
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'checklist_id' => 'required|exists:checklist,id',
            'respuestas' => 'required',
            'estatus_general' => 'required|in:OK,WARNING,ALERT',
            'imagenes.*' => 'nullable|image|max:5120', // 5MB máximo por imagen
            'descripciones.*' => 'nullable|string|max:255'
        ]);

        
        DB::beginTransaction();
        try {
        
            $chofer='N/A';
            // 1. PRIMERO: Parsear respuestas si viene como string
            $respuestas = $request->respuestas;
            if (is_string($respuestas)) {
                $respuestas = json_decode($respuestas, true);
            }
            

            // 2. AHORA: Procesar lógica de negocio con respuestas ya decodificadas
            $vehiculo = Vehiculo::find($request->vehiculo_id);
            $km = 0;
            $horasDuracion = 0;

            $old_inspeccion = Inspeccion::where('vehiculo_id', $request->vehiculo_id)->where('checklist_id',1)
                            ->whereNull('respuesta_in')
                            ->orderByDesc('created_at')
                            ->first();
            $isCriticalFailure = false;
            
            // Nombres de los ítems críticos a verificar
            $criticalItems = [
                'Vehiculo Operativo?',
                'Apto para Carga de Combustible?'
            ];
            // Verificar respuestas críticas y kilometraje
            if (is_array($respuestas)) {
                $chofer = $respuestas['sections'][2]['items'][0]['value'] ?? null;
                $observaciones=$respuestas['sections'][13]['items'][0]['value'] ?? null;
                
                foreach ($respuestas as $seccion) {
                    if (isset($seccion['items'])) {
                        foreach ($seccion['items'] as $item) {

                            $label = $item['label'] ?? null;
                            $value = $item['value'] ?? null;
                            
                            if($label =='Nombre'){
                                $chofer=$value;
                            }
                            
                            // Actualizar kilometraje
                            if ($label == 'Km. Recorridos' && $vehiculo) {
                                $kmRecorridos = is_numeric($value) ? (int)$value : 0;
                                $kmVehiculo = $vehiculo->kilometraje ?? 0;
                                
                                if (is_numeric($value) && $value > 0 && $value > $kmVehiculo) {
                                    $km = $kmRecorridos - $kmVehiculo;
                                    $vehiculo->kilometraje = $value;
                                    $vehiculo->km_contador += $km;
                                    $vehiculo->km_mantt += $km;
                                    
                                }
                            }
                            
                            // Verificar ítems críticos
                            if (in_array($label, $criticalItems)) {
                                $normalizedValue = is_string($value) ? strtolower($value) : $value;
                                
                                if ($normalizedValue === 'no' || $normalizedValue === false || $normalizedValue === 0) {
                                    $isCriticalFailure = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            // 3. Crear la inspección
            $payloadJson = json_encode($respuestas);

            $shouldUpdateExisting = $old_inspeccion 
                && (empty($old_inspeccion->respuesta_in) || empty($old_inspeccion->respuesta_json));
            
           if (is_null($old_inspeccion)) {
                // Nunca ha salido → primera salida
                $tipoCheck = 'OUT'; // salida
            } elseif (is_null($old_inspeccion->respuesta_in)) {
                // Tiene OUT pendiente → ahora está entrando
                $tipoCheck = 'IN'; // entrada
            } else {
                // Tiene OUT e IN → próximo movimiento: salida
                $tipoCheck = 'OUT'; 
            }

            if($tipoCheck == 'OUT'){
                $inspeccion = Inspeccion::create([
                    'vehiculo_id' => $request->vehiculo_id,
                    'checklist_id' => $request->checklist_id,
                    'usuario_id' => auth()->id(),
                    'estatus_general' => $request->estatus_general ?? 'OK',
                    'respuesta_json' => $payloadJson
                ]);

                if($request->checklist_id==2){
                    $orden=Orden::where('id_vehiculo',$request->vehiculo_id)->where('estatus',2)->where('inspeccion_id',null)->first();
                    if($orden){
                        $orden->inspeccion_id=$inspeccion->id;
                        $orden->save();
                    }
                }   

            }else{
                $old_inspeccion->respuesta_in = $payloadJson;
                //$old_inspeccion->respuesta_json = $payloadJson;
                $old_inspeccion->estatus_general = $request->estatus_general ?? 'OK';
                $old_inspeccion->save();
                $createdAt = $old_inspeccion->created_at; 
                $updatedAt = now();
                if($request->checklist_id==1){
                    $horasDuracion = $updatedAt->diffInHours($createdAt);
                    $vehiculo->horas_trabajo  += $horasDuracion;
                    $vehiculo->hrs_mantt  += $horasDuracion;
                    $vehiculo->hrs_contador   += $horasDuracion;    
                   // $vehiculo->estatus = 2;
                }
                $inspeccion=$old_inspeccion;
            }           

            // 4. Procesar y guardar imágenes si existen
            if ($request->hasFile('imagenes')) {
                $imagenes = $request->file('imagenes');
                $descripciones = $request->input('descripciones', []);

                foreach ($imagenes as $index => $imagen) {
                    $ruta = $imagen->store('inspecciones/' . $inspeccion->id, 'public');

                    InspeccionImagen::create([
                        'inspeccion_id' => $inspeccion->id,
                        'ruta_imagen' => $ruta,
                        'descripcion' => $descripciones[$index] ?? null,
                        'tipo_evidencia' => 'general',
                        'orden' => $index
                    ]);
                }
            }

            $alertaAction = "/inspecciones/{$inspeccion->id}";
            $condicion=null;
            // 2. Determinar el NUEVO estado del vehículo y el mensaje base
            if ($isCriticalFailure) {
                // 🔴 CONDICIÓN CRÍTICA: Prioridad alta, pasa a No Operativo (3)
                $nuevoEstatus = 5; 
                
                $observacionAlerta = "Inspección para vehículo {$vehiculo->placa} con estado **No Operativo**. Requiere revisión.";
                $notifTitle = "Unidad {$vehiculo->flota} Marcada No Operativa en Inspeccion";
                $notifBody = "Unidad {$vehiculo->flota} requiere Revisión de Mantenimiento. Fue marcada como no operativa durante la inspección. OBSERVACIONES: {$observaciones}";
                $telegramMessage = "🚨 *ALERTA CRÍTICA* - Unidad: **{$vehiculo->placa}** ({$vehiculo->flota}) marcada como **NO OPERATIVA**. Motivo: Fallo Crítico en Inspección. Revisar: {$alertaAction}\n OBSERVACIONES: {$observaciones}";

            } elseif ($tipoCheck == 'IN') {
                // 🟢 UNIDAD INGRESANDO: Estaba en ruta (2) y pasa a Operativo/Disponible (1)
                $nuevoEstatus = 1;
                $nuevoEstatusViaje = 'COMPLETADO';
                $condicion="EN RUTA";
                $observacionAlerta = "Ingreso de Unidad {$vehiculo->flota} {$vehiculo->placa} a Patio. Inspección completada.";
                $notifTitle = "Unidad {$vehiculo->flota} Ingresando a Patio";
                $notifBody = "Unidad {$vehiculo->flota} ingresando a Patio con {$chofer}.";
                $telegramMessage = "📥 *INGRESO* - Unidad: **{$vehiculo->placa}** ({$vehiculo->flota}) ingresa a patio. Nuevo Estatus: **Operativo**. Chofer: {$chofer}. Revisar: {$alertaAction}\n OBSERVACIONES: {$observaciones}";

            } else {
                // 🟡 UNIDAD SALIENDO: No está en ruta (probablemente 1 - Operativo) y pasa a En Ruta (2)
                $nuevoEstatus = 2;
                $nuevoEstatusViaje = 'EN RUTA';
                $condicion = "Programado";
                $observacionAlerta = "Salida de vehículo {$vehiculo->placa}. Inspección completada.";
                $notifTitle = "Salida de Unidad {$vehiculo->flota} en Inspeccion";
                $notifBody = "Unidad {$vehiculo->flota} Saliendo a Ruta con {$chofer}.";
                $telegramMessage = "📤 *SALIDA* - Unidad: **{$vehiculo->placa}** ({$vehiculo->flota}) saliendo a ruta . Nuevo Estatus: **En Ruta**  Chofer: {$chofer}. Revisar: {$alertaAction}\n OBSERVACIONES: {$observaciones}";
            }


            // 3. Aplicar el cambio de estatus (Solo si el estatus cambia)
            if ($nuevoEstatus == 5) {
                $vehiculo->estatus = $nuevoEstatus;
                
            }

            $vehiculo->save();

            if(!is_null($condicion)){
                $viaje = Viaje::where('vehiculo_id', $vehiculo->id)
                        ->where('status', $condicion)
                        ->first();
                if ($viaje && $viaje->status != $nuevoEstatusViaje) {
                    $viaje->status = $nuevoEstatusViaje;
                    $viaje->save();
                    $cisterna= $viaje->cisterna;
                    if(!is_null($cisterna)){
                        $vehiculoCisterna=Vehiculo::find($cisterna);
                        $vehiculoCisterna->estatus=$nuevoEstatus;
                        $vehiculoCisterna->kilometraje+=$km;
                        $vehiculoCisterna->horas_trabajo+=$horasDuracion;
                        $vehiculoCisterna->hrs_trabajo+=$horasDuracion;
                        $vehiculoCisterna->hrs_contador+=$horasDuracion;
                        $vehiculoCisterna->km_contador+=$km;
                        $vehiculoCisterna->km_mantt+=$km;
                        $vehiculoCisterna->save();
                        
                    }
                }
            }



            if($vehiculo->km_mantt>4800 || $vehiculo->hrs_mantt > 180){
                Alerta::create([
                    'id_usuario' => null, // null para todos los admins
                    'id_rel' => $inspeccion->id,
                    'fecha' => now(),
                    'observacion' => "Unidad {$vehiculo->flota} requiere planificacion para Servicio de Mantenimiento.",
                    'estatus' => 0,
                    'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
                ]);
                $data=[
                    'id_usuario' => null, // null para todos los admins
                    'id_rel' => $inspeccion->id,
                    'fecha' => now(),
                    'observacion' => "Unidad {$vehiculo->flota} requiere planificacion para Servicio de Mantenimiento.",
                    'estatus' => 0,
                    'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
                ];
                
                 FcmNotificationService::enviarNotification(
                        "Unidad {$vehiculo->flota} requiere Mantenimiento",  
                        "Unidad {$vehiculo->flota} requiere planificacion para Servicio de Mantenimiento. presenta acumulados {$vehiculo->km_mantt}km y {$vehiculo->hrs_mantt} horas de trabajo",
                        $data
                    );
                    
                    
            }

                        // 4. Crear los datos de la Alerta/Notificación (Estructura centralizada)
            $alertaData = [
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => $observacionAlerta,
                'estatus' => 0,
                'accion' => $alertaAction
            ];

            // 5. Crear la Alerta en la Base de Datos
            Alerta::create($alertaData);


            // 6. Enviar Notificación Push (Usando la misma data para la carga útil)
            FcmNotificationService::enviarNotification(
                $notifTitle,
                $notifBody,
                $alertaData // Usamos el array $alertaData como $data para la notificación
            );

            // 7. Enviar Notificación a Telegram (Asumiendo que tienes un servicio para esto)
            // Si utilizas el TelegramNotificationService que hemos trabajado antes:
            // Asegúrate de que este servicio se inyecte o esté disponible en el contexto.
            $this->telegramService->sendMessage($telegramMessage); // O TelegramNotificationService::sendMessageStatic(...)

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspección guardada exitosamente',
                'data' => [
                    'inspeccion_id' => $inspeccion->id,
                    'fecha' => $inspeccion->created_at->format('Y-m-d H:i:s'),
                    'estatus' => $inspeccion->estatus_general,
                    'imagenes_guardadas' => $inspeccion->imagenes()->count(),
                    'vehiculo_estatus_actualizado' => $isCriticalFailure
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log del error para debug
            Log::error('Error al guardar inspección: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la inspección: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de inspecciones del usuario
     */
    public function historial(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            $inspecciones = Inspeccion::with(['vehiculo:id,placa,marca,modelo', 'checklist:id,titulo'])
                ->where('usuario_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Historial obtenido exitosamente',
                'data' => $inspecciones->items(),
                'pagination' => [
                    'current_page' => $inspecciones->currentPage(),
                    'last_page' => $inspecciones->lastPage(),
                    'per_page' => $inspecciones->perPage(),
                    'total' => $inspecciones->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Obtener la última inspección registrada para un vehículo y checklist específicos
     */
    public function getUltimaInspeccion(Request $request)
    {
        try {
            $vehiculoId = $request->query('vehiculo_id');
            $checklistId = $request->query('checklist_id');

            if (!$vehiculoId || !$checklistId) {
                return response()->json([
                    'success' => false,
                    'message' => 'vehiculo_id y checklist_id son requeridos'
                ], 422);
            }

            $inspeccion = Inspeccion::where('vehiculo_id', $vehiculoId)
                ->where('checklist_id', $checklistId)
                ->orderByDesc('created_at')
                ->first();

            if (!$inspeccion) {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron inspecciones previas para este vehículo y checklist',
                    'data' => null,
                ]);
            }

            $respuestas = null;
            if (!empty($inspeccion->respuesta_in)) {
                $respuestas = json_decode($inspeccion->respuesta_in, true);
            } elseif (!empty($inspeccion->respuesta_json)) {
                $respuestas = json_decode($inspeccion->respuesta_json, true);
            }

            return response()->json([
                'success' => true,
                'message' => 'Inspección encontrada',
                'data' => [
                    'id' => $inspeccion->id,
                    'vehiculo_id' => $inspeccion->vehiculo_id,
                    'checklist_id' => $inspeccion->checklist_id,
                    'estatus_general' => $inspeccion->estatus_general,
                    'respuesta_json' => $inspeccion->respuesta_json,
                    'respuesta_in' => $inspeccion->respuesta_in,
                    'respuestas' => $respuestas,
                    'created_at' => $inspeccion->created_at,
                    'updated_at' => $inspeccion->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la inspección: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles de una inspección específica
     */
    public function showInspeccion($id)
    {
        try {
            $inspeccion = Inspeccion::with([
                    'vehiculo:id,placa,marca,modelo,color', 
                    'checklist:id,titulo,checklist',
                    'imagenes' => function($query) {
                        $query->orderBy('orden');
                    }
                ])
                ->where('id', $id)
                ->where('usuario_id', auth()->id())
                ->first();

            if (!$inspeccion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspección no encontrada'
                ], 404);
            }

            // Decodificar las respuestas JSON
            $inspeccion->respuestas_decodificadas = json_decode($inspeccion->respuesta_json, true);
            
            // Agregar URLs completas a las imágenes
            $inspeccion->imagenes->each(function($imagen) {
                $imagen->url_completa = asset('storage/' . $imagen->ruta_imagen);
            });

            return response()->json([
                'success' => true,
                'message' => 'Inspección obtenida exitosamente',
                'data' => $inspeccion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener inspección: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos completos del vehículo con relaciones para checklist
     * @param int $id
     */
    public function getVehiculoCompleto($id)
    {
        try {

            $cacheKey = "inspeccion_user_" . auth()->id();
            Cache::put($cacheKey, $id, 300);
            
            $vehiculo = DB::table('vehiculos as v')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->leftJoin('modelos as modelo', 'v.modelo', '=', 'modelo.id')
                ->leftJoin('tipo_vehiculos as tv', 'v.tipo', '=', 'tv.id')
                ->select([
                    'v.id',
                    'v.id_cliente',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.marca as marca_nombre',
                    'v.modelo',
                    'modelo.modelo as modelo_nombre',
                    'v.placa',
                    'v.tipo',
                    'tv.tipo as tipo_nombre',
                    'v.tipo_diagrama',
                    'v.serial_motor',
                    'v.serial_carroceria',
                    'v.transmision',
                    'v.HP',
                    'v.CC',
                    'v.altura',
                    'v.ancho',
                    'v.largo',
                    'v.consumo',
                    'v.created_at',
                    'v.updated_at',
                    'v.kilometraje',
                    'v.horas_trabajo',
                    'v.hrs_mantt',
                    'v.hrs_contador',
                    'v.km_mantt',
                    'v.km_contador',
                    'v.color',
                ])
                ->where('v.id', $id)
                //->where('v.estatus', 1)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            // Agregar información adicional formateada
            $vehiculoCompleto = [
                'id' => $vehiculo->id,
                'id_cliente' => $vehiculo->id_cliente,
                'estatus' => $vehiculo->estatus,
                'flota' => $vehiculo->flota,
                'marca' => $vehiculo->marca,
                'marca_nombre' => $vehiculo->marca_nombre,
                'modelo' => $vehiculo->modelo,
                'modelo_nombre' => $vehiculo->modelo_nombre,
                'placa' => $vehiculo->placa,
                'tipo' => $vehiculo->tipo,
                'tipo_nombre' => $vehiculo->tipo_nombre,
                'tipo_diagrama' => $vehiculo->tipo_diagrama,
                'serial_motor' => $vehiculo->serial_motor,
                'serial_carroceria' => $vehiculo->serial_carroceria,
                'transmision' => $vehiculo->transmision,
                'HP' => $vehiculo->HP,
                'CC' => $vehiculo->CC,
                'altura' => $vehiculo->altura,
                'ancho' => $vehiculo->ancho,
                'largo' => $vehiculo->largo,
                'consumo' => $vehiculo->consumo,
                'created_at' => $vehiculo->created_at,
                'updated_at' => $vehiculo->updated_at,
                'kilometraje' => $vehiculo->kilometraje,
                'horas_trabajo' => $vehiculo->horas_trabajo,
                'hrs_mantt' => $vehiculo->hrs_mantt,
                'hrs_contador' => $vehiculo->hrs_contador,
                'km_mantt' => $vehiculo->km_mantt,
                'km_contador' => $vehiculo->km_contador,
                'color' => $vehiculo->color
            ];

            return response()->json([
                'success' => true,
                'message' => 'Vehículo obtenido exitosamente',
                'data' => $vehiculoCompleto
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículo: ' . $e->getMessage()
            ], 500);
        }
    }
}
