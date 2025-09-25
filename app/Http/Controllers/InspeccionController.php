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

        // 1. Determinar el Estatus General
        // Iteramos el JSON de respuesta para buscar fallos
        foreach ($respuestaJson['sections'] as $section) {
            foreach ($section['items'] as $item) {
                // Si es booleano, y es falso -> WARNING
                if ($item['response_type'] === 'boolean' && $item['value'] === false) {
                    $estatusGeneral = 'WARNING';
                    break 2; // Salir de los bucles de items y sections
                }
                // Si es compuesto, y el estado es falso -> WARNING
                if ($item['response_type'] === 'composite' && isset($item['value']['status']) && $item['value']['status'] === false) {
                    $estatusGeneral = 'WARNING';
                    break 2;
                }
            }
        }
        // Nota: Para un estatus 'ALERT', se requeriría una lógica adicional (por ejemplo, un campo 'critico' en el JSON blueprint).

        // 2. Guardar la Inspección
        $inspeccion = Inspeccion::create([
            'vehiculo_id' => $data['vehiculo_id'],
            'checklist_id' => $checklistId,
            'usuario_id' => Auth::id(),
            'estatus_general' => $estatusGeneral,
            'respuesta_json' => $respuestaJson,
        ]);

        // 3. Sistema de Alertas y Notificaciones (Si no está OK)
        if ($estatusGeneral !== 'OK') {
            $vehiculo = Vehiculo::find($data['vehiculo_id']);
            $placa = $vehiculo ? $vehiculo->placa : 'N/A';
            
            // Crear Alerta en la tabla de Alertas (para administradores)
            Alerta::create([
                'id_usuario' => null, // null para todos los admins
                'id_rel' => $inspeccion->id,
                'fecha' => now(),
                'observacion' => "Inspección de salida para vehículo {$placa} con estado **{$estatusGeneral}**. Requiere revisión.",
                'estatus' => 0,
                'accion' => "/inspecciones/{$inspeccion->id}" // Ruta al detalle de la inspección
            ]);

            // Enviar Notificación Móvil (usando tu FcmNotificationService)
            // Asume que tienes un método para enviar a todos los admins
            // FcmNotificationService::sendInspectionStatusToAdmin($inspeccion, $placa, $estatusGeneral);
        }

        return response()->json([
            'success' => true, 
            'message' => "Inspección guardada con estado: {$estatusGeneral}",
            'estatus' => $estatusGeneral
        ]);
    }
}