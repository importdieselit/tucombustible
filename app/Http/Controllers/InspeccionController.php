<?php
// app/Http/Controllers/InspeccionController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Checklist;
use App\Models\Inspeccion;
use App\Models\Vehiculo;
use App\Models\Alerta;
use Illuminate\Support\Facades\Auth;

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

        // Obtener datos del vehículo (para pre-rellenar el formulario)
        $vehiculo = Vehiculo::findOrFail($vehiculo_id);

        return view('checklist.salida', [
            'checklist' => $checklist,
            'vehiculo' => $vehiculo,
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

        // 1. Determinar el Estatus General
        foreach ($respuestaJson['sections'] as $section) {
            
            // Función auxiliar para procesar los items, ya sea directamente o dentro de subsecciones
            $processItems = function ($items) use (&$estatusGeneral, &$warningFound) {
                foreach ($items as $item) {
                    // Si es booleano, y es falso -> WARNING
                    if ($item['response_type'] === 'boolean' && $item['value'] === false) {
                        $estatusGeneral = 'WARNING';
                        $warningFound = true;
                        return; // Detiene la función auxiliar
                    }
                    // Si es compuesto, y el estado es falso -> WARNING
                    if ($item['response_type'] === 'composite' && isset($item['value']['status']) && $item['value']['status'] === false) {
                        $estatusGeneral = 'WARNING';
                        $warningFound = true;
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
        $inspeccion = Inspeccion::create([
            'vehiculo_id' => $data['vehiculo_id'],
            'checklist_id' => $checklistId,
            'usuario_id' => Auth::id(),
            'estatus_general' => $estatusGeneral,
            // Asegúrate de guardar el JSON como string, si la columna `respuesta_json` no es un tipo JSON nativo.
            'respuesta_json' => json_encode($respuestaJson), 
        ]);
        
        // 3. Sistema de Alertas y Notificaciones (Si no está OK)
        if ($estatusGeneral !== 'OK') {
            $vehiculo = Vehiculo::find($data['vehiculo_id']);
            $placa = $vehiculo ? $vehiculo->placa : 'N/A';
            
            // Crear Alerta en la tabla de Alertas (para administradores)
            // Usando tu modelo Alerta y AlertaController
            Alerta::create([
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => "Inspección de salida para vehículo {$placa} con estado **{$estatusGeneral}**. Requiere revisión.",
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
}