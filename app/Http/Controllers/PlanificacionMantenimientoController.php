<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MantenimientoProgramado;
use App\Models\Vehiculo;
use App\Models\Orden;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PlanificacionMantenimientoController extends Controller
{
    /**
     * Muestra la vista del calendario de planificación de mantenimiento.
     */
    public function index()
    {
        // Asumiendo que necesitas la lista de vehículos para el formulario del modal
        $vehiculos = Vehiculo::where('es_flota', true)->get(['id', 'flota', 'placa']);
        
        // Asumiendo que tienes una forma de obtener los tipos de mantenimiento (M1, M2, etc.)
        // Si no los tienes en BD, puedes pasarlos fijos o crear un modelo Parametro
        $tiposMantenimiento = [
            1=> 'M1 (5000km/200hrs)', 
            2 => 'M2 (Intermedio)', 
            3 => 'M3 (Mayor)', 
            4 => 'M4'
        ];

        return view('planificacion.planificacion', compact('vehiculos', 'tiposMantenimiento'));
    }

    /**
     * Devuelve los eventos de planificación para FullCalendar.
     */
    public function getEventos(Request $request)
    {
        // Se asume que FullCalendar envía 'start' y 'end' para el rango de la vista.
        $start = $request->get('start');
        $end = $request->get('end');

        $planificaciones = MantenimientoProgramado::whereBetween('fecha_programada', [$start, $end])
            ->with('vehiculo:id,flota,placa')
            ->get();

        $eventos = $planificaciones->map(function ($plan) {
            $title = "[{$plan->vehiculo->flota}] {$plan->tipo_mantenimiento}";
            
            // Colores basados en el estatus
            $color = match ($plan->estatus) {
                1 => '#4e73df', // Programado (Azul - Pendiente de OT)
                2 => '#1cc88a', // OT Generada (Verde - Ya es una orden)
                default => '#f6c23e', // Otros/Cancelado (Amarillo)
            };

            return [
                'id' => $plan->id,
                'title' => $title,
                'start' => $plan->fecha_programada->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'vehiculo_id' => $plan->vehiculo_id,
                    'placa' => $plan->vehiculo->placa,
                    'tipo' => $plan->tipo_mantenimiento,
                    'descripcion' => $plan->descripcion_plan,
                    'estatus' => $plan->estatus,
                    'orden_id' => $plan->orden_id,
                ]
            ];
        });

        return response()->json($eventos);
    }
    
    /**
     * Crea una nueva planificación y genera la Orden de Trabajo inmediata si se solicita.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'fecha' => 'required|date|after_or_equal:today',
            'tipo' => 'required|string|max:50',
    ]);

        $userId = auth()->id();
        
        DB::beginTransaction();

        try {
            $vehiculo= Vehiculo::findOrFail($request->vehiculo_id);
            // 1. Crear la Planificación de Mantenimiento
            $planificacion = MantenimientoProgramado::create([
                'vehiculo_id' => $request->vehiculo_id,
                'plan_id' => $request->plan_id ?? null ,
                'fecha' => $request->fecha_programada,
                'tipo' => $request->tipo_mantenimiento,
                'km' => $vehiculo->km_mantt ?? null,
                'status' => 1, // Programado
            ]);

            // 2. Generar la Orden de Trabajo (OT) inmediatamente
            // La OT se crea con estatus 'Programada' o 'Pendiente' (ej: 1) 
            // y con la fecha de entrada como la fecha_programada.
            $orden = Orden::create([
                'vehiculo_id' => $request->vehiculo_id,
                'titulo' => "Mantenimiento Programado: {$request->tipo_mantenimiento}",
                'descripcion' => "Planificado para el {$request->fecha_programada}. Tipo de servicio: {$request->tipo_mantenimiento}. Descripción: {$request->descripcion_plan}",
                'prioridad' => 'Media', // Por ser planificado
                'estatus' => 1, // 1: Programada (para no sacarla de servicio aún)
                'fecha_in' => $request->fecha_programada, // La fecha clave es la programada
                'usuario_id' => $userId
            ]);

            // 3. Actualizar la Planificación con el ID de la OT y cambiar estatus
            $planificacion->update([
                'orden_id' => $orden->id,
                'nro_orden' => $orden->nro_orden,
                'estatus' => 2, // OT Generada
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento planificado y Orden de Trabajo generada exitosamente.',
                'orden_id' => $orden->id,
                'planificacion_id' => $planificacion->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al planificar mantenimiento y generar OT: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error en el servidor al procesar la solicitud.'], 500);
        }
    }
}
