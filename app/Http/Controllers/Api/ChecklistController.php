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
use App\Services\FcmNotificationService;



class ChecklistController extends Controller
{
    /**
     * Obtener todos los checklists activos
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

            return response()->json([
                'success' => true,
                'message' => 'Checklist obtenido exitosamente',
                'data' => $checklist
            ]);

        } catch (\Exception $e) {
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

         $usuarios = User::whereIn('id', $usuarioIds)
                          ->whereNotNull('fcm_token')
                          ->get();

        DB::beginTransaction();
        try {
            
            // 1. PRIMERO: Parsear respuestas si viene como string
            $respuestas = $request->respuestas;
            if (is_string($respuestas)) {
                $respuestas = json_decode($respuestas, true);
            }

            // 2. AHORA: Procesar lógica de negocio con respuestas ya decodificadas
            $vehiculo = Vehiculo::find($request->vehiculo_id);
            $isCriticalFailure = false;
            
            // Nombres de los ítems críticos a verificar
            $criticalItems = [
                'Vehiculo Operativo?',
                'Apto para Carga de Combustible?'
            ];

            // Verificar respuestas críticas y kilometraje
            if (is_array($respuestas)) {
                foreach ($respuestas as $seccion) {
                    if (isset($seccion['items'])) {
                        foreach ($seccion['items'] as $item) {
                            $label = $item['label'] ?? null;
                            $value = $item['value'] ?? null;
                            
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
            $inspeccion = Inspeccion::create([
                'vehiculo_id' => $request->vehiculo_id,
                'checklist_id' => $request->checklist_id,
                'usuario_id' => auth()->id(),
                'estatus_general' => $request->estatus_general,
                'respuesta_json' => json_encode($respuestas)
            ]);

            if($vehiculo->km_mantt>4800){
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
                        "Unidad {$vehiculo->flota} requiere planificacion para Servicio de Mantenimiento. presenta acumulados {$vehiculo->km_mantt}km",
                        $data
                    );
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

            // 5. Actualizar estatus del vehículo si hubo fallo crítico
            if ($isCriticalFailure && $vehiculo && $vehiculo->estatus != 3) {
                $vehiculo->estatus = 3; // 3 = No Operativo

                Alerta::create([
                    'id_usuario' => null, // null para todos los admins
                    'id_rel' => $inspeccion->id,
                    'fecha' => now(),
                    'observacion' => "Inspección para vehículo {$vehiculo->placa} con estado **No Operativo o apto para El Viaje**. Requiere revisión.",
                    'estatus' => 0,
                    'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
                ]);
                $data=[
                    'id_usuario' => null, // null para todos los admins
                    'id_rel' => $inspeccion->id,
                    'fecha' => now(),
                    'observacion' => "Inspección para vehículo {$vehiculo->placa} con estado **No Operativo o apto para El Viaje**. Requiere revisión.",
                    'estatus' => 0,
                    'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
                ];
                FcmNotificationService::enviarNotification(
                        "Unidad {$vehiculo->flota} Marcada No Operativa en Inspeccion",  
                        "Unidad {$vehiculo->flota} requiere Revision de Mantenimiento. Fue marcada como no operativa durante la inspeccion",
                        $data
                    );

            }elseif($vehiculo->estatus==2){
                $vehiculo->estatus=1;
                Alerta::create([
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => "Ingreso de Unidad {$vehiculo->flota} {$vehiculo->placa}",
                'estatus' => 0,
                'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
            ]);
            }else{
                $vehiculo->estatus=2;
                Alerta::create([
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => "Salida de vehículo {$vehiculo->placa}.",
                'estatus' => 0,
                'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
            ]);
            }
            $vehiculo->save();
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
            \Log::error('Error al guardar inspección: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
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
     */
    public function getVehiculoCompleto($id)
    {
        try {
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