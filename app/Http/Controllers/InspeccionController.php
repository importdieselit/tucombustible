<?php
// app/Http/Controllers/InspeccionController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Checklist;
use App\Models\Inspeccion;
use App\Models\Vehiculo;
use App\Models\Viaje;
use App\Models\Alerta;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Orden;
use App\Models\User;
use App\Services\FcmNotificationService;
use App\Services\TelegramNotificationService;  

class InspeccionController extends Controller
{
    // ID del checklist de vehículos (hardcodeado por tu requerimiento)
    const CHECKLIST_VEHICULOS_ID = 1;
    protected $fcmService;
    protected $telegramService;


    public function __construct(
        FcmNotificationService $fcmService, 
        TelegramNotificationService $telegramService
    ) {
        $this->fcmService = $fcmService;
        $this->telegramService = $telegramService;
    }

    public function create($vehiculo_id, $tipo='salida'){
        // Obtener el blueprint del checklist
        $checklist = Checklist::find(self::CHECKLIST_VEHICULOS_ID);
        if (!$checklist) {
            abort(404, 'Checklist de vehículos no encontrado.');
        }

        $viajePrevioId = null;
        $inspeccion = Inspeccion::where('vehiculo_id', $vehiculo_id)
                         ->whereNull('respuesta_in') // <-- CORRECCIÓN AQUÍ
                         ->first();
        if($inspeccion){
            $tipo='entrada';
            $dataResponse = is_array($inspeccion->respuesta_json) 
                    ? $inspeccion->respuesta_json 
                    : json_decode($inspeccion->respuesta_json, true);

            if(isset($dataResponse['sections'][0]['items'])) {
                // Buscamos si ya existe el campo de ruta en el JSON guardado de la salida
                foreach($dataResponse['sections'][0]['items'] as $item) {
                    if($item['label'] == 'Seleccione Ruta a Cubrir') {
                        $viajePrevioId = $item['value'];
                        break;
                    }
                }
            }
        } else {
            // Lo mismo para el objeto $checklist
            $dataResponse = is_array($checklist->checklist) 
                            ? $checklist->checklist 
                            : json_decode($checklist->checklist, true);
          //  $tipo='salida';
        }

        // Obtener datos del vehículo (para pre-rellenar el formulario)
        $vehiculo = Vehiculo::with(['tipoVehiculo', 'isMarca', 'isModelo'])->findOrFail($vehiculo_id);
        $viajes = Viaje::where('vehiculo_id', $vehiculo_id)
                ->whereDate('fecha_salida', '>=', now())
                ->get();

                    // --- BLOQUE 1: Inyectar Rutas/Viajes en "Información General" ---
                    if ($viajes->count() > 0 && is_null($viajePrevioId)) {
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
                            "col_width" => 12,
                            "readonly" => $tipo === 'entrada' // Solo editable en salida
                        ];
                        // Lo insertamos al final de la primera sección
                        $dataResponse['sections'][0]['items'][] = $campoViaje;
                    } elseif (!is_null($viajePrevioId)) {
                        // Si ya hay un viaje (Entrada), lo dejamos como texto estático o radio deshabilitado
                        foreach($dataResponse['sections'][0]['items'] as &$item) {
                            if($item['label'] == 'Seleccione Ruta a Cubrir') {
                                $item['readonly'] = true; // El JS debe manejar este atributo
                            }
                        }
                    }

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
                        }
                    }
                    $checklist->checklist=$dataResponse;
    
        return view('checklist.salida', [
            'checklist' => $checklist,
            'vehiculo' => $vehiculo,
            'tipo' => $tipo,
            'inspeccion'=>$inspeccion
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'respuesta_json' => 'required|array', // JSON completo serializado desde JS
        ]);
        
        //$chofer= 'n/a';
        $respuestaJson = $data['respuesta_json'];
        $viajeIdSeleccionado = null;
        $checklistId = self::CHECKLIST_VEHICULOS_ID;
        $estatusGeneral = 'OK';
        $warningFound = false;
        $fail=0;
        $user = auth()->user();
        $nombre = $user->persona->nombre ?? 'Usuario'.$user->id;

        $isCriticalFailure = false;
            
        $criticalItems = [
            'Vehiculo Operativo?',
            'Apto para Carga de Combustible?'
        ];

        $vehiculo = Vehiculo::find($data['vehiculo_id']);
        $old_inspeccion = Inspeccion::where('vehiculo_id', $data['vehiculo_id'])
            ->whereIn('checklist_id', [1,3])
            ->whereNull('respuesta_in')
            ->first();

        

        $chofer = $respuestaJson['sections'][2]['items'][0]['value'] ?? null;
        $observaciones=$respuestas['sections'][13]['items'][0]['value'] ?? null;
        // 1. Determinar el Estatus General
        foreach ($respuestaJson['sections'] as $section) {
            // Función auxiliar para procesar los items, ya sea directamente o dentro de subsecciones
            $processItems = function ($items) use (&$estatusGeneral, &$warningFound, &$fail,&$vehiculo,&$chofer,&$isCriticalFailure,&$criticalItems) {
                foreach ($items as $item) {
                    $value=$item['value'];

                    if($item['label']=='Nombre'){
                        $chofer=$item['value'];
                    }

                    if ($item['label'] == 'Seleccione Ruta a Cubrir' && !empty($item['value'])) {
                        // El valor viene como "ID-45 | Ruta: Caracas"
                        preg_match('/ID-(\d+)/', $item['value'], $matches);
                        $viajeIdSeleccionado = $matches[1] ?? null;
                    }
                    
                    if ($item['label'] == 'Km. Recorridos' ) {
                        $kmRecorridos = is_numeric($value) ? (int)$value : 0;
                        $kmVehiculo = $vehiculo->kilometraje ?? 0;
                                
                        if (is_numeric($value) && $value > 0 && $value > $kmVehiculo) {
                            $km = $kmRecorridos - $kmVehiculo;
                            $vehiculo->kilometraje = $value;
                            $vehiculo->km_contador += $km;
                            $vehiculo->km_mantt += $km;
                        }
                    }
                    // Si es booleano, y es falso -> WARNING
                    if ($item['response_type'] === 'boolean' && $item['value'] === false) {
                        $estatusGeneral = 'WARNING';
                        $warningFound = true;
                        $fail++;
                        if ($fail >= 5) {
                            $estatusGeneral = 'ALERT';
                        }
                    }
                    if (in_array($item['label'], $criticalItems)) {
                        $normalizedValue = is_string($value) ? strtolower($value) : $value;
                        if ($normalizedValue === 'no' || $normalizedValue === false || $normalizedValue === 0) {
                            $isCriticalFailure = true;
                            $estatusGeneral = 'ALERT';
                            $warningFound = true;
                            break;
                        }
                    }
                    // Si es compuesto, y el estado es falso -> WARNING
                    if ($item['response_type'] === 'composite' && isset($item['value']['status']) && $item['value']['status'] === false) {
                        $estatusGeneral = 'WARNING';
                        $warningFound = true;
                        $fail++;
                        if ($fail >= 5) {
                            $estatusGeneral = 'ALERT';
                        }
                        return; // Detiene la función auxiliar
                    }
                }
            };
            
            if (isset($section['items'])) {
                $processItems($section['items']);
            } elseif (isset($section['subsections'])) {
                foreach ($section['subsections'] as $subsection) {
                    if (isset($subsection['items'])) {
                        $processItems($subsection['items']);
                    }
                    if ($warningFound) break;
                }
            }

            if ($warningFound) {
                break; // Salir del bucle principal de sections
            }
        }
        
        // 2. Guardar la Inspección

        
        if(is_null($old_inspeccion)){
            $inspeccion = Inspeccion::create([
                'vehiculo_id' => $data['vehiculo_id'],
                'checklist_id' => $checklistId,
                'usuario_id' => Auth::id(),
                'estatus_general' => $estatusGeneral,
                'respuesta_json' => json_encode($respuestaJson), 
            ]);
            if ($viajeIdSeleccionado) {
                        $viaje = Viaje::find($viajeIdSeleccionado);
                        if ($viaje) {
                            $viaje->status = 'EN RUTA';
                            $viaje->save();
                        }
                    }

            $tipoCheck='OUT';
            $vehiculo->estatus=2;
        }else{
            
            $tiempoTranscurrido = $old_inspeccion->created_at->diffInMinutes(now());
            if ($tiempoTranscurrido < 60) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede registrar la llegada todavía. Debe esperar al menos 1 hora desde la salida (Han pasado {$tiempoTranscurrido} min)."
                ], 422);
            }

            $latSede = 10.48834308128781;
            $lngSede = -66.82329619185627;
            $radioSede = 0.200; // 200 metros en km (aprox)

            if (isset($vehiculo->latitud) && isset($vehiculo->longitud)) {
                $distancia = $this->calcularDistancia($vehiculo->latitud, $vehiculo->longitud, $latSede, $lngSede);
                
                if ($distancia > $radioSede) {
                    return response()->json([
                        'success' => false,
                        'message' => "Validación GPS fallida: El vehículo se encuentra a " . round($distancia, 2) . " km. Debe estar en la sede para cerrar el checklist."
                    ], 422);
                }
            }

            $old_inspeccion->respuesta_in=json_encode($respuestaJson);
            $old_inspeccion->estatus_general=$estatusGeneral;
            $old_inspeccion->save();
            $createdAt = $old_inspeccion->created_at; 
            $updatedAt = now();
            $tipoCheck='IN';            
            $horasDuracion = $updatedAt->diffInHours($createdAt);
            $vehiculo->horas_trabajo  += $horasDuracion;
            $vehiculo->hrs_mantt  += $horasDuracion;
            $vehiculo->hrs_contador   += $horasDuracion;    
            $vehiculo->estatus = 1;

            $viaje=Viaje::where('vehiculo_id',$vehiculo->id)->where('status', 'EN RUTA')->first();
            if($viaje){
                $viaje->status='COMPLETADO';
                $viaje->save();         
            }
        }

        if ($isCriticalFailure) {
                // 🔴 CONDICIÓN CRÍTICA: Prioridad alta, pasa a No Operativo (3)
            $vehiculo->estatus = 5; 
        }
        $vehiculo->save();
        // 3. Sistema de Alertas y Notificaciones (Si no está OK)



        $alertaAction = "/inspecciones/{$inspeccion->id}";

            // 2. Determinar el NUEVO estado del vehículo y el mensaje base
            if ($isCriticalFailure) {
                // 🔴 CONDICIÓN CRÍTICA: Prioridad alta, pasa a No Operativo (3)
                $observacionAlerta = "Inspección para vehículo {$vehiculo->placa} con estado **No Operativo**. Requiere revisión.";
                $notifTitle = "Unidad {$vehiculo->flota} Marcada No Operativa en Inspeccion";
                $notifBody = "Unidad {$vehiculo->flota} requiere Revisión de Mantenimiento. Fue marcada como no operativa durante la inspección.";
                $telegramMessage = "🚨 *ALERTA CRÍTICA* - Unidad: **{$vehiculo->placa}** ({$vehiculo->flota}) marcada como **NO OPERATIVA**. Motivo: Fallo Crítico en Inspección. Revisar: {$alertaAction} ";

            } elseif ($tipoCheck == 'IN') {
                // 🟢 UNIDAD INGRESANDO: Estaba en ruta (2) y pasa a Operativo/Disponible (1)
               
                $observacionAlerta = "Ingreso de Unidad {$vehiculo->flota} {$vehiculo->placa} a Patio. Inspección completada.";
                $notifTitle = "Unidad {$vehiculo->flota} Ingresando a Patio";
                $notifBody = "Unidad {$vehiculo->flota} ingresando a Patio con {$chofer}.";
                $telegramMessage = "📥 *CHECKIN* REALIZADO POR: {$nombre} - Unidad: **{$vehiculo->placa}** ({$vehiculo->flota}) ingresa a patio. Nuevo Estatus: **Operativo**. Chofer: {$chofer}. Revisar: {$alertaAction} \n OBSERVACIONES: {$observaciones}";
                
            } else {
                // 🟡 UNIDAD SALIENDO: No está en ruta (probablemente 1 - Operativo) y pasa a En Ruta (2)
                $observacionAlerta = "Salida de vehículo {$vehiculo->placa}. Inspección completada.";
                $notifTitle = "Salida de Unidad {$vehiculo->flota} en Inspeccion";
                $notifBody = "Unidad {$vehiculo->flota} Saliendo a Ruta con {$chofer}.";
                $telegramMessage = "📤 *CHECKOUT* REALIZADO POR: {$nombre} * - Unidad: **{$vehiculo->placa}** ({$vehiculo->flota}) saliendo a ruta . Nuevo Estatus: **En Ruta**  Chofer: {$chofer}. Revisar: {$alertaAction} \n OBSERVACIONES: {$observaciones}";
            }

            $alertaData = [
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => $observacionAlerta,
                'estatus' => 0,
                'accion' => $alertaAction
            ];

            Alerta::create($alertaData);
            
            FcmNotificationService::enviarNotification(
                $notifTitle,
                $notifBody,
                $alertaData 
            );

            $this->telegramService->sendMessage($telegramMessage);



        return response()->json([
            'success' => true, 
            'message' => "Inspección guardada con estado: {$estatusGeneral}",
            'estatus' => $estatusGeneral
        ]);
    }

    public function storeMantt(Request $request)
    {
        $data = $request->validate([
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'respuesta_json' => 'required|array', // JSON completo serializado desde JS
        ]);
        

        $respuestaJson = $data['respuesta_json'];
        $checklistId = 2;
        $estatusGeneral = 'OK';
        $warningFound = false;
        $fail=0;

        $vehiculo = Vehiculo::find($data['vehiculo_id']);
        $old_inspeccion = Inspeccion::where('vehiculo_id', $data['vehiculo_id'])
            ->whereIn('checklist_id',2)
            ->whereNull('respuesta_in')
            ->first();

        // 1. Determinar el Estatus General
        foreach ($respuestaJson['sections'] as $section) {
            $processItems = function ($items) use (&$estatusGeneral, &$warningFound, &$fail,&$vehiculo) {
                foreach ($items as $item) {
                    if ($item['label'] == 'Km. Recorridos' ) {
                        $value=$item['value'];
                        $kmRecorridos = is_numeric($value) ? (int)$value : 0;
                        $kmVehiculo = $vehiculo->kilometraje ?? 0;
                                
                        if (is_numeric($value) && $value > 0 && $value > $kmVehiculo) {
                            $km = $kmRecorridos - $kmVehiculo;
                            $vehiculo->kilometraje = $value;
                            $vehiculo->km_contador += $km;
                            $vehiculo->km_mantt += $km;
                        }
                    }
                    // Si es booleano, y es falso -> WARNING
                    if ($item['response_type'] === 'boolean' && $item['value'] === false) {
                        $estatusGeneral = 'WARNING';
                        $warningFound = true;
                        $fail++;
                        if ($fail >= 5) {
                            $estatusGeneral = 'ALERT';
                        }
                    }
                    // Si es compuesto, y el estado es falso -> WARNING
                    if ($item['response_type'] === 'composite' && isset($item['value']['status']) && $item['value']['status'] === false) {
                        $estatusGeneral = 'WARNING';
                        $warningFound = true;
                        $fail++;
                        if ($fail >= 5) {
                            $estatusGeneral = 'ALERT';
                        }
                        return; // Detiene la función auxiliar
                    }
                }
            };
            
            if (isset($section['items'])) {
                $processItems($section['items']);
            } elseif (isset($section['subsections'])) {
                foreach ($section['subsections'] as $subsection) {
                    if (isset($subsection['items'])) {
                        $processItems($subsection['items']);
                    }
                    if ($warningFound) break;
                }
            }

            if ($warningFound) {
                break; // Salir del bucle principal de sections
            }
        }
        
        // 2. Guardar la Inspección
        if(!$old_inspeccion){
            $inspeccion = Inspeccion::create([
                'vehiculo_id' => $data['vehiculo_id'],
                'checklist_id' => $checklistId,
                'usuario_id' => Auth::id(),
                'estatus_general' => $estatusGeneral,
                'respuesta_json' => json_encode($respuestaJson), 
            ]);
            $orden=Orden::where('id_vehiculo',$data['vehiculo_id'])->where('estatus',2)->where('inspeccion_id',null)->first();
            if($orden){
                $orden->inspeccion_id=$inspeccion->id;
                $orden->save();
            }

        }else{
            $old_inspeccion->respuesta_in=json_encode($respuestaJson);
            $old_inspeccion->estatus_general=$estatusGeneral;
            $old_inspeccion->save();
        }
        $vehiculo->save();
        // 3. Sistema de Alertas y Notificaciones (Si no está OK)
        if ($estatusGeneral !== 'OK') {
            $placa = $vehiculo ? $vehiculo->placa : 'N/A';
            
            Alerta::create([
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => "Inspección de Mantenimiento para vehículo {$placa} con estado **{$estatusGeneral}**. Requiere revisión.",
                'estatus' => 0,
                'accion' => "/inspecciones/{$inspeccion->id}" 
            ]);

        }

        return response()->json([
            'success' => true, 
            'message' => "Inspección guardada con estado: {$estatusGeneral}",
            'estatus' => $estatusGeneral
        ]);
    }


    public function show(int $inspeccion_id)
    {
        // Carga la inspección y el vehículo relacionado
        $inspeccion = Inspeccion::with('vehiculo')->findOrFail($inspeccion_id);
        $imagenes = $inspeccion->imagenes()->get();
        
        $respuesta = is_string($inspeccion->respuesta_json) 
                    ? json_decode($inspeccion->respuesta_json, true) 
                    : $inspeccion->respuesta_json;
        
        $titulo = $respuesta['checklist_name'] ?? 'Inspección de Vehículo';

        return view('checklist.show', compact('inspeccion', 'imagenes','respuesta', 'titulo'));
    }

    public function exportPdf(int $inspeccion_id)
    {
        $inspeccion = Inspeccion::with('vehiculo')->findOrFail($inspeccion_id);
        
        $respuesta = is_string($inspeccion->respuesta_json) 
                    ? json_decode($inspeccion->respuesta_json, true) 
                    : $inspeccion->respuesta_json;

        $titulo = $respuesta['checklist_name'] ?? 'Inspección de Vehículo';
        $pdf = Pdf::loadView('checklist.pdf_template', compact('inspeccion', 'respuesta', 'titulo'));
        $placa = $inspeccion->vehiculo->placa ?? 'SINPLACA';
        $fecha = \Carbon\Carbon::parse($inspeccion->created_at)->format('Ymd');
        
        return $pdf->download("Inspeccion_Salida_{$placa}_{$fecha}.pdf");
    }

     public function list()
    {
        // 1. Obtener las inspecciones
        $inspecciones = Inspeccion::with(['vehiculo', 'usuario'])
                                  ->orderBy('created_at', 'desc')
                                  ->paginate(15); // Paginamos para listas grandes
        
        // 2. Definir los colores/estilos para el estatus (opcional pero muy visual)
        $estatusColores = [
            'OK' => 'success',
            'WARNING' => 'warning',
            'ALERT' => 'danger',
            'N/A' => 'secondary',
        ];

        return view('checklist.list', compact('inspecciones', 'estatusColores'));
    }

      public function index()
    {
        $resumenAlertas = [
            'warnings' => Inspeccion::whereIn('estatus_general', ['WARNING','ALERT'])->count(),
            'ordenes_abiertas' => Orden::where('estatus', 2)->count(),
            'vehiculos_mantenimiento' => Vehiculo::where('estatus', 3)->count(),
        ];
        $inspeccionesRecientes = Inspeccion::with('vehiculo')->whereNull('respuesta_in')
                                        ->orderBy('created_at', 'desc')
                                        ->get();
        $user = auth()->user();
        $vehiculosDisponibles = Vehiculo::where('es_flota',true)->get()->mapWithKeys(function ($v) {
            return [$v->id => "{$v->flota} - {$v->placa}"];
        });
        return view('checklist.index', compact('resumenAlertas','vehiculosDisponibles', 'inspeccionesRecientes','user'));
    }

    private function calcularDistancia(mixed $lat1, mixed $lon1, mixed $lat2, mixed $lon2)
    {
        $earthRadius = 6371; // Radio de la tierra en km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

}