<?php
// app/Http/Controllers/InspeccionController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Checklist;
use App\Models\Inspeccion;
use App\Models\Vehiculo;
use App\Models\Alerta;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FcmNotificationService; // Asegúrate de tener este servicio implementado
use App\Models\Orden;
use App\Models\User;

class InspeccionController extends Controller
{
    // ID del checklist de vehículos (hardcodeado por tu requerimiento)
    const CHECKLIST_VEHICULOS_ID = 1;

    public function create($vehiculo_id)
    {
        // Obtener el blueprint del checklist
        $checklist = Checklist::find(self::CHECKLIST_VEHICULOS_ID);
        if (!$checklist) {
            abort(404, 'Checklist de vehículos no encontrado.');
        }
        $inspeccion = Inspeccion::where('vehiculo_id', $vehiculo_id)
                         ->whereNull('respuesta_in') // <-- CORRECCIÓN AQUÍ
                         ->first();

        // Obtener datos del vehículo (para pre-rellenar el formulario)
        $vehiculo = Vehiculo::findOrFail($vehiculo_id);
        if($inspeccion){
            $tipo='entrada';
            $checklist=json_decode($inspeccion->respuesta_json);
        }else{
            $tipo='salida';
        }

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
        
        $chofer= 'n/a';
        $respuestaJson = $data['respuesta_json'];
        $checklistId = self::CHECKLIST_VEHICULOS_ID;
        $estatusGeneral = 'OK';
        $warningFound = false;
        $fail=0;

        $vehiculo = Vehiculo::find($data['vehiculo_id']);
        $old_inspeccion = Inspeccion::where('vehiculo_id', $data['vehiculo_id'])
            ->where('checklist_id',1)
            ->whereNull('respuesta_in')
            ->first();

        
        // 1. Determinar el Estatus General
        foreach ($respuestaJson['sections'] as $section) {
           
            // Función auxiliar para procesar los items, ya sea directamente o dentro de subsecciones
            $processItems = function ($items) use (&$estatusGeneral, &$warningFound, &$fail,&$vehiculo,&$chofer) {
                foreach ($items as $item) {
                    if($item['label']=='Nombre'){
                        $chofer=$item['value'];
                    }
                    
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
            $vehiculo->estatus=1;
        }else{
            $old_inspeccion->respuesta_in=json_encode($respuestaJson);
            $old_inspeccion->estatus_general=$estatusGeneral;
            $old_inspeccion->save();
            $createdAt = $old_inspeccion->created_at; 
            $updatedAt = now();
            
            $horasDuracion = $updatedAt->diffInHours($createdAt);
            $vehiculo->horas_trabajo  += $horasDuracion;
            $vehiculo->hrs_mantt  += $horasDuracion;
            $vehiculo->hrs_contador   += $horasDuracion;    
            $vehiculo->estatus = 2;
        }
        $vehiculo->save();
        // 3. Sistema de Alertas y Notificaciones (Si no está OK)
        if ($estatusGeneral !== 'OK') {
            $placa = $vehiculo ? $vehiculo->placa : 'N/A';
            
            Alerta::create([
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => "Inspección de {$request->tipo} para vehículo {$placa} con estado **{$estatusGeneral}**. Requiere revisión.",
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
            ->where('checklist_id',2)
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


    public function show($inspeccion_id)
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

    public function exportPdf($inspeccion_id)
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
        $user = auth()->user();
        $vehiculosDisponibles = Vehiculo::where('es_flota',true)->get();
      
        return view('checklist.index', compact('resumenAlertas','vehiculosDisponibles'));
    }

}