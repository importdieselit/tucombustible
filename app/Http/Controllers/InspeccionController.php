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
            $checklist=$inspeccion->respuesta_json;
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
        

        $respuestaJson = $data['respuesta_json'];
        $checklistId = self::CHECKLIST_VEHICULOS_ID;
        $estatusGeneral = 'OK';
        $warningFound = false;
        $fail=0;

            $vehiculo = Vehiculo::find($data['vehiculo_id']);
             $old_inspeccion = Inspeccion::where('vehiculo_id', $data['vehiculo_id'])
                         ->whereNull('respuesta_in') // <-- CORRECCIÓN AQUÍ
                         ->first();

        
        // 1. Determinar el Estatus General
        foreach ($respuestaJson['sections'] as $section) {
           
            // Función auxiliar para procesar los items, ya sea directamente o dentro de subsecciones
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
                        $estatusGeneral = 'ADVERTENCIA';
                        $warningFound = true;
                        $fail++;
                        if ($fail >= 5) {
                            $estatusGeneral = 'ALERTA';
                        }
                    }
                    // Si es compuesto, y el estado es falso -> WARNING
                    if ($item['response_type'] === 'composite' && isset($item['value']['status']) && $item['value']['status'] === false) {
                        $estatusGeneral = 'ADVERTENCIA';
                        $warningFound = true;
                        $fail++;
                        if ($fail >= 5) {
                            $estatusGeneral = 'ALERTA';
                        }
                        return; // Detiene la función auxiliar
                    }
                }
            };
            
            // Lógica para manejar la estructura de la sección:
            if (isset($section['items'])) {
                // Caso 1: Secciones normales (Ej: 1.- SISTEMA ELÉCTRICO)
                $processItems($section['items']);
            } elseif (isset($section['subsections'])) {
                // Caso 2: Secciones con subsecciones (Ej: 8.- DOCUMENTACIÓN Y EQUIPO)
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
        // ... (Tu código de guardado sigue igual)
        if(!$old_inspeccion){
            $inspeccion = Inspeccion::create([
                'vehiculo_id' => $data['vehiculo_id'],
                'checklist_id' => $checklistId,
                'usuario_id' => Auth::id(),
                'estatus_general' => $estatusGeneral,
                // Asegúrate de guardar el JSON como string, si la columna `respuesta_json` no es un tipo JSON nativo.
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
            
            // Crear Alerta en la tabla de Alertas (para administradores)
            // Usando tu modelo Alerta y AlertaController
            Alerta::create([
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => "Inspección de {$request->tipo} para vehículo {$placa} con estado **{$estatusGeneral}**. Requiere revisión.",
                'estatus' => 0,
                'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
            ]);

            // ... (Llamada a FcmNotificationService si la implementas)
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
        
        // Si la columna respuesta_json no está casteada, asegúrate de decodificarla.
        $respuesta = is_string($inspeccion->respuesta_json) 
                    ? json_decode($inspeccion->respuesta_json, true) 
                    : $inspeccion->respuesta_json;
        
        // El título del documento para la vista
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
        
        // Carga la vista 'checklistpdf_template' con los datos
        $pdf = Pdf::loadView('checklist.pdf_template', compact('inspeccion', 'respuesta', 'titulo'));
        
        // Descarga el PDF con un nombre claro
        $placa = $inspeccion->vehiculo->placa ?? 'SINPLACA';
        $fecha = \Carbon\Carbon::parse($inspeccion->created_at)->format('Ymd');
        
        return $pdf->download("Inspeccion_Salida_{$placa}_{$fecha}.pdf");
    }

     public function list()
    {
        // 1. Obtener las inspecciones
        // Cargamos las relaciones del vehículo y el usuario que inspeccionó para mostrar sus nombres/placas.
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
        // 1. Obtener datos de resumen (KPIs)
        $resumenAlertas = [
            // Contar inspecciones con estatus WARNING
            'warnings' => Inspeccion::whereIn('estatus_general', ['WARNING','ALERT'])->count(),
            
            // Contar órdenes de trabajo que no han sido cerradas (ej. estatus 'Abierta', 'En Revisión')
            'ordenes_abiertas' => Orden::where('estatus', 2)->count(),
            
            // Contar vehículos con estatus de mantenimiento (asumiendo estatus=2)
            'vehiculos_mantenimiento' => Vehiculo::where('estatus', 3)->count(),
        ];
        $user = auth()->user();
        $vehiculosDisponibles = Vehiculo::where('es_flota',true)->get();
        

        // 2. Puedes agregar datos adicionales si tienes gráficos o tablas de resumen.

        // Retornar la vista con los datos
        return view('checklist.index', compact('resumenAlertas','vehiculosDisponibles'));
    }

}