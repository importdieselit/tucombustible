<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\Vehiculo;
use App\Models\User;
use Carbon\Carbon;

class ConductorController extends Controller
{
    /**
     * Obtiene los datos completos del conductor (perfil + vehículo + pedido actual)
     * 
     * @param int $conductorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function datos($conductorId)
    {
        try {
            $conductor = User::find($conductorId);
            
            if (!$conductor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conductor no encontrado'
                ], 404);
            }
            
            // Obtener vehículo asignado (si existe)
            $vehiculoAsignado = null;
            // TODO: Implementar relación con vehículos cuando esté disponible
            // Por ahora retornar null
            
            // Obtener chofer del conductor desde la tabla choferes
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            // Obtener pedido actual (primer pedido en proceso o aprobado del chofer)
            $pedidoActual = null;
            if ($chofer) {
                $pedidoActual = Pedido::where('chofer_id', $chofer->id)
                    ->whereIn('estado', ['aprobado', 'en_proceso'])
                    ->with(['cliente', 'deposito', 'vehiculo'])
                    ->orderBy('fecha_aprobacion', 'asc')
                    ->first();
            }
            
            $pedidoActualData = null;
            if ($pedidoActual) {
                $pedidoActualData = [
                    'id' => $pedidoActual->id,
                    'cliente_id' => $pedidoActual->cliente_id,
                    'cliente_nombre' => $pedidoActual->cliente->nombre ?? '',
                    'cantidad_solicitada' => $pedidoActual->cantidad_solicitada,
                    'cantidad_aprobada' => $pedidoActual->cantidad_aprobada,
                    'estado' => $pedidoActual->estado,
                    'fecha_solicitud' => $pedidoActual->fecha_solicitud,
                    'fecha_aprobacion' => $pedidoActual->fecha_aprobacion,
                    'observaciones' => $pedidoActual->observaciones,
                    'deposito_nombre' => $pedidoActual->deposito->serial ?? '',
                    'direccion_entrega' => $pedidoActual->cliente->direccion ?? '',
                ];
            }
            
            $conductorData = [
                'id' => $conductor->id,
                'nombre' => $conductor->name ?? '',
                'telefono' => $conductor->telefono ?? null,
                'email' => $conductor->email,
                'licencia' => null, // TODO: Agregar campo licencia si existe
                'fecha_vencimiento_licencia' => null,
                'activo' => $conductor->activo ?? true,
                'vehiculo_asignado' => $vehiculoAsignado,
                'pedido_actual' => $pedidoActualData,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $conductorData
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del conductor: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene el historial de pedidos del conductor (compatibilidad con sistema antiguo)
     * 
     * @param int $conductorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function historialPedidos($conductorId)
    {
        try {
            $conductor = User::find($conductorId);
            
            if (!$conductor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conductor no encontrado'
                ], 404);
            }
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }
            
            // Buscar historial de pedidos del chofer
            $pedidos = Pedido::where('chofer_id', $chofer->id)
                ->whereIn('estado', ['completado', 'cancelado'])
                ->with(['cliente', 'deposito', 'vehiculo'])
                ->orderBy('fecha_completado', 'desc')
                ->limit(50)
                ->get();
            
            $pedidosFormateados = $pedidos->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'cliente_id' => $pedido->cliente_id,
                    'cliente_nombre' => $pedido->cliente->nombre ?? '',
                    'cantidad_solicitada' => $pedido->cantidad_solicitada,
                    'cantidad_aprobada' => $pedido->cantidad_aprobada,
                    'estado' => $pedido->estado,
                    'fecha_solicitud' => $pedido->fecha_solicitud,
                    'fecha_aprobacion' => $pedido->fecha_aprobacion,
                    'fecha_completado' => $pedido->fecha_completado,
                    'observaciones' => $pedido->observaciones,
                    'deposito_nombre' => $pedido->deposito->serial ?? '',
                    'direccion_entrega' => $pedido->cliente->direccion ?? '',
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $pedidosFormateados
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene el dashboard del conductor con estadísticas y próximo despacho
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        try {
            $conductor = Auth::user();
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'pendientes' => 0,
                        'en_proceso' => 0,
                        'completados_hoy' => 0,
                        'completados_mes' => 0,
                        'proximo_despacho' => null,
                    ]
                ], 200);
            }
            
            // Obtener estadísticas usando chofer_id
            $estadisticas = [
                'pendientes' => Pedido::where('chofer_id', $chofer->id)
                    ->where('estado', 'aprobado')
                    ->count(),
                'en_proceso' => Pedido::where('chofer_id', $chofer->id)
                    ->where('estado', 'en_proceso')
                    ->count(),
                'completados_hoy' => Pedido::where('chofer_id', $chofer->id)
                    ->where('estado', 'completado')
                    ->whereDate('fecha_completado', Carbon::today())
                    ->count(),
                'completados_mes' => Pedido::where('chofer_id', $chofer->id)
                    ->where('estado', 'completado')
                    ->whereMonth('fecha_completado', Carbon::now()->month)
                    ->whereYear('fecha_completado', Carbon::now()->year)
                    ->count(),
            ];
            
            // Obtener próximo despacho (pedido más antiguo aprobado o en proceso)
            $proximoDespacho = Pedido::where('chofer_id', $chofer->id)
                ->whereIn('estado', ['aprobado', 'en_proceso'])
                ->with(['cliente', 'deposito', 'vehiculo'])
                ->orderBy('fecha_aprobacion', 'asc')
                ->first();
            
            $proximoDespachoData = null;
            if ($proximoDespacho) {
                $proximoDespachoData = $this->formatPedidoDetalle($proximoDespacho);
            }
            
            return response()->json([
                'success' => true,
                'data' => array_merge($estadisticas, [
                    'proximo_despacho' => $proximoDespachoData,
                ])
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene la lista de pedidos asignados al conductor
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function pedidosAsignados(Request $request)
    {
        try {
            $conductor = Auth::user();
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }
            
            // Buscar pedidos asignados a este chofer
            $pedidos = Pedido::where('chofer_id', $chofer->id)
                ->whereIn('estado', ['aprobado', 'en_proceso'])
                ->with([
                    'cliente', 
                    'deposito', 
                    'vehiculo',
                    'chofer'
                ])
                ->orderBy('fecha_solicitud', 'desc')
                ->get();
            
            // Formatear todos los pedidos (cliente y deposito son opcionales ahora)
            $pedidosFormateados = $pedidos->map(function($pedido) {
                return $this->formatPedidoDetalle($pedido);
            });
            
            return response()->json([
                'success' => true,
                'data' => $pedidosFormateados
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos asignados: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene el detalle completo de un pedido específico
     * 
     * @param int $pedidoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function detallePedido($pedidoId)
    {
        try {
            $conductor = Auth::user();
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chofer no encontrado'
                ], 404);
            }
            
            // Buscar pedido asignado a este chofer
            $pedido = Pedido::where('id', $pedidoId)
                ->where('chofer_id', $chofer->id)
                ->with(['cliente', 'deposito', 'vehiculo', 'chofer'])
                ->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado o no asignado a este conductor'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $this->formatPedidoDetalle($pedido)
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle del pedido: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Acepta un pedido asignado (marca como en proceso)
     * 
     * @param Request $request
     * @param int $pedidoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function aceptarPedido(Request $request, $pedidoId)
    {
        try {
            $conductor = Auth::user();
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chofer no encontrado'
                ], 404);
            }
            
            // Buscar pedido asignado a este chofer
            $pedido = Pedido::where('id', $pedidoId)
                ->where('chofer_id', $chofer->id)
                ->where('estado', 'aprobado')
                ->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado o no disponible para aceptar'
                ], 404);
            }
            
            // Actualizar estado a "en_proceso"
            $pedido->estado = 'en_proceso';
            $pedido->save();
            
            // TODO: Enviar notificación al admin
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido aceptado exitosamente'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aceptar pedido: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Inicia el viaje (actualiza estado si es necesario)
     * 
     * @param Request $request
     * @param int $pedidoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function iniciarViaje(Request $request, $pedidoId)
    {
        try {
            $conductor = Auth::user();
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chofer no encontrado'
                ], 404);
            }
            
            // Buscar pedido asignado a este chofer
            $pedido = Pedido::where('id', $pedidoId)
                ->where('chofer_id', $chofer->id)
                ->whereIn('estado', ['aprobado', 'en_proceso'])
                ->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }
            
            // Asegurar que está en proceso
            if ($pedido->estado !== 'en_proceso') {
                $pedido->estado = 'en_proceso';
                $pedido->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Viaje iniciado exitosamente'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar viaje: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Completa la entrega de un pedido
     * 
     * @param Request $request
     * @param int $pedidoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function completarEntrega(Request $request, $pedidoId)
    {
        try {
            $conductor = Auth::user();
            
            $validated = $request->validate([
                'cantidad_entregada' => 'required|numeric|min:0',
                'nombre_recibe' => 'required|string|max:255',
                'cedula_recibe' => 'required|string|max:255',
                'hora_inicio_descarga' => 'required|string',
                'hora_fin_descarga' => 'required|string',
                'firma_digital' => 'required|string',
                'fotos' => 'required|array|min:1',
                'fotos.*' => 'string',
                'observaciones' => 'nullable|string',
            ]);
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chofer no encontrado'
                ], 404);
            }
            
            // Buscar pedido asignado a este chofer
            $pedido = Pedido::where('id', $pedidoId)
                ->where('chofer_id', $chofer->id)
                ->where('estado', 'en_proceso')
                ->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado o no está en proceso'
                ], 404);
            }
            
            // Actualizar el pedido
            $pedido->cantidad_recibida = $validated['cantidad_entregada'];
            $pedido->estado = 'completado';
            $pedido->fecha_completado = Carbon::now();
            
            // Guardar datos de entrega en observaciones_admin (temporalmente)
            $datosEntrega = [
                'nombre_recibe' => $validated['nombre_recibe'],
                'cedula_recibe' => $validated['cedula_recibe'],
                'hora_inicio_descarga' => $validated['hora_inicio_descarga'],
                'hora_fin_descarga' => $validated['hora_fin_descarga'],
                'fecha_entrega' => Carbon::now()->toDateTimeString(),
            ];
            
            if (isset($validated['observaciones'])) {
                $datosEntrega['observaciones_conductor'] = $validated['observaciones'];
            }
            
            $pedido->observaciones_admin = json_encode($datosEntrega);
            
            // TODO: Guardar firma digital y fotos
            // Nota: Por ahora no guardamos las imágenes en base de datos
            // Se pueden guardar en storage y referenciar, o en una tabla separada
            
            $pedido->save();
            
            // TODO: Enviar notificación al admin y cliente
            
            return response()->json([
                'success' => true,
                'message' => 'Entrega completada exitosamente'
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al completar entrega: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reporta una incidencia sobre un pedido
     * 
     * @param Request $request
     * @param int $pedidoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportarIncidencia(Request $request, $pedidoId)
    {
        try {
            $conductor = Auth::user();
            
            $validated = $request->validate([
                'tipo' => 'required|string|in:problema_mecanico,documentacion,problema_personal,direccion_incorrecta,otros',
                'descripcion' => 'required|string|min:10',
                'nivel_urgencia' => 'required|string|in:baja,media,alta',
                'fotos' => 'nullable|array',
                'fotos.*' => 'string',
            ]);
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chofer no encontrado'
                ], 404);
            }
            
            // Buscar pedido asignado a este chofer
            $pedido = Pedido::where('id', $pedidoId)
                ->where('chofer_id', $chofer->id)
                ->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }
            
            // Guardar incidencia en observaciones del pedido
            // Nota: Sin modificar la BD, guardamos en observaciones
            $incidencia = [
                'tipo' => $validated['tipo'],
                'descripcion' => $validated['descripcion'],
                'nivel_urgencia' => $validated['nivel_urgencia'],
                'fecha_reporte' => Carbon::now()->toDateTimeString(),
                'conductor_id' => $conductor->id,
                'estado' => 'pendiente',
            ];
            
            // Obtener incidencias existentes
            $observacionesActuales = $pedido->observaciones;
            $incidencias = [];
            
            if ($observacionesActuales) {
                $decoded = json_decode($observacionesActuales, true);
                if (isset($decoded['incidencias'])) {
                    $incidencias = $decoded['incidencias'];
                }
            }
            
            $incidencias[] = $incidencia;
            
            $pedido->observaciones = json_encode(['incidencias' => $incidencias]);
            $pedido->save();
            
            // TODO: Enviar notificación al admin
            
            return response()->json([
                'success' => true,
                'message' => 'Incidencia reportada exitosamente'
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reportar incidencia: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene el historial de pedidos del conductor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function historial(Request $request)
    {
        try {
            $conductor = Auth::user();
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total' => 0,
                        'per_page' => 20,
                    ]
                ], 200);
            }
            
            // Buscar historial de pedidos del chofer
            $query = Pedido::where('chofer_id', $chofer->id)
                ->whereIn('estado', ['completado', 'cancelado']);
            
            // Filtros opcionales
            if ($request->has('fecha_desde')) {
                $query->where('fecha_solicitud', '>=', $request->fecha_desde);
            }
            
            if ($request->has('fecha_hasta')) {
                $query->where('fecha_solicitud', '<=', $request->fecha_hasta);
            }
            
            $pedidos = $query->with(['cliente', 'deposito', 'vehiculo'])
                ->orderBy('fecha_completado', 'desc')
                ->paginate($request->input('limite', 20));
            
            $pedidosFormateados = $pedidos->map(function($pedido) {
                return $this->formatPedidoDetalle($pedido);
            });
            
            return response()->json([
                'success' => true,
                'data' => $pedidosFormateados,
                'pagination' => [
                    'current_page' => $pedidos->currentPage(),
                    'total' => $pedidos->total(),
                    'per_page' => $pedidos->perPage(),
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene estadísticas del conductor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticas(Request $request)
    {
        try {
            $conductor = Auth::user();
            $periodo = $request->input('periodo', 'mes'); // hoy, semana, mes, año
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_completados' => 0,
                        'litros_entregados' => 0,
                        'incidencias_reportadas' => 0,
                        'viajes_en_tiempo' => 0,
                    ]
                ], 200);
            }
            
            $fechaInicio = $this->getFechaInicioPeriodo($periodo);
            
            // Estadísticas del chofer
            $estadisticas = [
                'total_completados' => Pedido::where('chofer_id', $chofer->id)
                    ->where('estado', 'completado')
                    ->where('fecha_completado', '>=', $fechaInicio)
                    ->count(),
                'litros_entregados' => Pedido::where('chofer_id', $chofer->id)
                    ->where('estado', 'completado')
                    ->where('fecha_completado', '>=', $fechaInicio)
                    ->sum('cantidad_recibida'),
                'incidencias_reportadas' => 0, // TODO: Contar incidencias
                'viajes_en_tiempo' => 0, // TODO: Calcular
            ];
            
            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualiza la disponibilidad del conductor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function actualizarDisponibilidad(Request $request)
    {
        try {
            $conductor = Auth::user();
            
            $validated = $request->validate([
                'disponible' => 'required|boolean',
            ]);
            
            // Nota: Asumiendo que hay un campo 'activo' en la tabla users
            // Si no existe, este endpoint simplemente retorna success
            $conductor->activo = $validated['disponible'];
            $conductor->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Disponibilidad actualizada exitosamente'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar disponibilidad: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene las incidencias reportadas por el conductor
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function incidencias()
    {
        try {
            $conductor = Auth::user();
            
            // Obtener chofer del conductor
            $chofer = DB::table('choferes')
                ->where('persona_id', $conductor->id_persona)
                ->first();
            
            if (!$chofer) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }
            
            // Buscar pedidos con incidencias del chofer
            $pedidos = Pedido::where('chofer_id', $chofer->id)
                ->whereNotNull('observaciones')
                ->get();
            
            $incidencias = [];
            foreach ($pedidos as $pedido) {
                $observaciones = json_decode($pedido->observaciones, true);
                if (isset($observaciones['incidencias'])) {
                    foreach ($observaciones['incidencias'] as $incidencia) {
                        $incidencia['pedido_id'] = $pedido->id;
                        $incidencias[] = $incidencia;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $incidencias
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener incidencias: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Formatea un pedido con toda su información relacionada
     * 
     * @param Pedido $pedido
     * @return array
     */
    private function formatPedidoDetalle(Pedido $pedido)
    {
        return [
            'pedido' => [
                'id' => $pedido->id,
                'estado' => $pedido->estado,
                'cantidad_solicitada' => $pedido->cantidad_solicitada,
                'cantidad_aprobada' => $pedido->cantidad_aprobada,
                'cantidad_recibida' => $pedido->cantidad_recibida,
                'fecha_solicitud' => $pedido->fecha_solicitud,
                'fecha_aprobacion' => $pedido->fecha_aprobacion,
                'fecha_completado' => $pedido->fecha_completado,
                'observaciones' => $pedido->observaciones,
                'observaciones_admin' => $pedido->observaciones_admin,
            ],
            'cliente' => $pedido->cliente ? [
                'id' => $pedido->cliente->id,
                'nombre' => $pedido->cliente->nombre ?? '',
                'contacto' => $pedido->cliente->contacto ?? '',
                'telefono' => $pedido->cliente->telefono ?? '',
                'email' => $pedido->cliente->email ?? '',
                'rif' => $pedido->cliente->rif ?? '',
                'direccion' => $pedido->cliente->direccion ?? '',
                'sector' => $pedido->cliente->sector ?? '',
            ] : [
                'id' => 0,
                'nombre' => 'Sin cliente',
                'contacto' => '',
                'telefono' => '',
                'email' => '',
                'rif' => '',
                'direccion' => '',
                'sector' => '',
            ],
            'deposito' => $pedido->deposito ? [
                'id' => $pedido->deposito->id,
                'serial' => $pedido->deposito->serial ?? '',
                'producto' => $pedido->deposito->producto ?? '',
                'ubicacion' => $pedido->deposito->ubicacion ?? '',
            ] : [
                'id' => 0,
                'serial' => 'Sin asignar',
                'producto' => '',
                'ubicacion' => '',
            ],
            'vehiculo' => $pedido->vehiculo ? [
                'id' => $pedido->vehiculo->id,
                'placa' => $pedido->vehiculo->placa ?? '',
                'marca' => (string)($pedido->vehiculo->marca ?? ''),
                'modelo' => (string)($pedido->vehiculo->modelo ?? ''),
                'estado' => $pedido->vehiculo->estado ?? 'operativo',
            ] : null,
        ];
    }
    
    /**
     * Obtiene la fecha de inicio según el período solicitado
     * 
     * @param string $periodo
     * @return Carbon
     */
    private function getFechaInicioPeriodo($periodo)
    {
        switch ($periodo) {
            case 'hoy':
                return Carbon::today();
            case 'semana':
                return Carbon::now()->startOfWeek();
            case 'mes':
                return Carbon::now()->startOfMonth();
            case 'año':
                return Carbon::now()->startOfYear();
            default:
                return Carbon::now()->startOfMonth();
        }
    }
}
