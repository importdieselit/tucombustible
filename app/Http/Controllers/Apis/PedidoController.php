<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Deposito;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Obtener todos los pedidos del cliente autenticado
     */
    public function getMisPedidos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $query = Pedido::with(['deposito'])
                ->where('cliente_id', $user->id_cliente);

            // Filtrar por mes si se proporciona
            if ($request->has('year') && $request->has('month')) {
                $year = $request->input('year');
                $month = $request->input('month');
                
                \Log::info("Filtrando pedidos por mes: $year-$month");
                
                $query->whereYear('fecha_solicitud', $year)
                      ->whereMonth('fecha_solicitud', $month);
            }

            $pedidos = $query->orderBy('fecha_solicitud', 'desc')->get();

            \Log::info("Pedidos encontrados: " . $pedidos->count());

            return response()->json([
                'success' => true,
                'data' => $pedidos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un pedido específico
     */
    public function getPedido(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $pedido = Pedido::with(['deposito'])
                ->where('id', $id)
                ->where('cliente_id', $user->id_cliente)
                ->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $pedido
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo pedido
     */
    public function crearPedido(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'deposito_id' => 'required|exists:depositos,id',
                'cantidad_solicitada' => 'required|numeric|min:0.01',
                'observaciones' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el depósito pertenece al cliente
            $deposito = Deposito::whereHas('movimientosCombustible', function($query) use ($user) {
                $query->where('cliente_id', $user->id_cliente);
            })->where('id', $request->deposito_id)->first();

            if (!$deposito) {
                return response()->json([
                    'success' => false,
                    'message' => 'Depósito no encontrado o no pertenece al cliente'
                ], 404);
            }

            // Calcular capacidad disponible
            $capacidadTotal = $deposito->capacidad_litros;
            $nivelActual = $deposito->nivel_actual_litros ?? 0;
            $capacidadDisponible = $capacidadTotal - $nivelActual;
            
            // Debug: Log de valores para verificar
            \Log::info('Debug Pedido - Depósito ID: ' . $deposito->id);
            \Log::info('Debug Pedido - Capacidad Total: ' . $capacidadTotal);
            \Log::info('Debug Pedido - Nivel Actual: ' . $nivelActual);
            \Log::info('Debug Pedido - Capacidad Disponible: ' . $capacidadDisponible);
            \Log::info('Debug Pedido - Cantidad Solicitada: ' . $request->cantidad_solicitada);

            if ($request->cantidad_solicitada > $capacidadDisponible) {
                return response()->json([
                    'success' => false,
                    'message' => "La cantidad solicitada excede la capacidad disponible. Máximo permitido: {$capacidadDisponible} litros"
                ], 422);
            }

            // Crear el pedido
            $pedido = Pedido::create([
                'cliente_id' => $user->id_cliente,
                'deposito_id' => $request->deposito_id,
                'cantidad_solicitada' => $request->cantidad_solicitada,
                'observaciones' => $request->observaciones,
                'estado' => 'pendiente',
                'fecha_solicitud' => now(),
            ]);

            $pedido->load('deposito');

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'data' => $pedido
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calificar un pedido completado
     */
    public function calificarPedido(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'calificacion' => 'required|integer|min:1|max:5',
                'comentario_calificacion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pedido = Pedido::where('id', $id)
                ->where('cliente_id', $user->id_cliente)
                ->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            if (!$pedido->puede_calificar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido no puede ser calificado'
                ], 422);
            }

            $pedido->update([
                'calificacion' => $request->calificacion,
                'comentario_calificacion' => $request->comentario_calificacion,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido calificado exitosamente',
                'data' => $pedido
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calificar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar un pedido (solo si está pendiente)
     */
    public function cancelarPedido(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $pedido = Pedido::where('id', $id)
                ->where('cliente_id', $user->id_cliente)
                ->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            if ($pedido->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar pedidos pendientes'
                ], 422);
            }

            $pedido->update([
                'estado' => 'cancelado',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente',
                'data' => $pedido
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de pedidos del cliente
     */
    public function getEstadisticasPedidos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            // Base query para el cliente
            $baseQuery = Pedido::where('cliente_id', $user->id_cliente);

            // Filtrar por mes si se proporciona
            if ($request->has('year') && $request->has('month')) {
                $year = $request->input('year');
                $month = $request->input('month');
                
                \Log::info("Filtrando estadísticas por mes: $year-$month");
                
                $baseQuery->whereYear('fecha_solicitud', $year)
                          ->whereMonth('fecha_solicitud', $month);
            }

            $estadisticas = [
                'total_pedidos' => (clone $baseQuery)->count(),
                'pedidos_pendientes' => (clone $baseQuery)->where('estado', 'pendiente')->count(),
                'pedidos_aprobados' => (clone $baseQuery)->where('estado', 'aprobado')->count(),
                'pedidos_completados' => (clone $baseQuery)->where('estado', 'completado')->count(),
                'pedidos_cancelados' => (clone $baseQuery)->where('estado', 'cancelado')->count(),
                'total_litros_solicitados' => (clone $baseQuery)->sum('cantidad_solicitada'),
                'promedio_calificacion' => (clone $baseQuery)
                    ->whereNotNull('calificacion')
                    ->avg('calificacion'),
            ];

            \Log::info("Estadísticas calculadas para cliente {$user->id_cliente}");

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
