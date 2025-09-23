<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Deposito;
use App\Models\User;
use App\Models\Cliente;
use App\Models\MovimientoCombustible;
use App\Services\FcmNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class PedidoController extends Controller
{
    /**
     * Obtener todos los pedidos del cliente autenticado y sus sucursales
     */
    public function getMisPedidos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            // Obtener el cliente principal del usuario
            $clientePrincipal = Cliente::where('id', $user->cliente_id)->first();
            
            if (!$clientePrincipal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Determinar qué clientes incluir en la consulta
            $clientesIds = [$user->cliente_id]; // Siempre incluir el cliente del usuario
            
            // Si es cliente padre (parent = 0), incluir también las sucursales
            if ($clientePrincipal->parent == 0) {
                $sucursales = Cliente::where('parent', $user->cliente_id)->pluck('id')->toArray();
                $clientesIds = array_merge($clientesIds, $sucursales);
                
                \Log::info("Cliente padre {$user->cliente_id} - Incluyendo sucursales: " . implode(', ', $sucursales));
            }

            $query = Pedido::with(['deposito', 'cliente'])
                ->whereIn('cliente_id', $clientesIds);

            // Filtrar por mes si se proporciona
            if ($request->has('year') && $request->has('month')) {
                $year = $request->input('year');
                $month = $request->input('month');
                
                \Log::info("Filtrando pedidos por mes: $year-$month");
                
                $query->whereYear('fecha_solicitud', $year)
                      ->whereMonth('fecha_solicitud', $month);
            }

            $pedidos = $query->orderBy('fecha_solicitud', 'desc')->get();

            \Log::info("Pedidos encontrados para cliente(s) " . implode(', ', $clientesIds) . ": " . $pedidos->count());

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
            
            if (!$user || !$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            // Obtener el cliente del usuario
            $clienteUsuario = Cliente::where('id', $user->cliente_id)->first();
            
            if (!$clienteUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente del usuario no encontrado'
                ], 404);
            }

            // Determinar qué clientes puede ver
            $clientesIds = [$user->cliente_id]; // Siempre incluir el cliente del usuario
            
            // Si es cliente padre, incluir también las sucursales
            if ($clienteUsuario->parent == 0) {
                $sucursales = Cliente::where('parent', $user->cliente_id)->pluck('id')->toArray();
                $clientesIds = array_merge($clientesIds, $sucursales);
            }

            $pedido = Pedido::with(['deposito', 'cliente'])
                ->where('id', $id)
                ->whereIn('cliente_id', $clientesIds)
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
            
            if (!$user || !$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'cantidad_solicitada' => 'required|numeric|min:0.01',
                'observaciones' => 'nullable|string|max:500',
                'cliente_id' => 'nullable|integer|exists:clientes,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Determinar el cliente a usar
            $clienteId = $request->cliente_id ?? $user->cliente_id;
            
            // Verificar que el usuario tiene permisos para crear pedidos para este cliente
            if ($clienteId != $user->cliente_id) {
                // Si es diferente, verificar que el cliente seleccionado es una sucursal del usuario
                $clientePrincipal = Cliente::where('id', $user->cliente_id)->first();
                if (!$clientePrincipal || $clientePrincipal->parent != 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para crear pedidos para este cliente'
                    ], 403);
                }
                
                // Verificar que el cliente seleccionado es una sucursal del cliente principal
                $clienteSeleccionado = Cliente::where('id', $clienteId)
                    ->where('parent', $user->cliente_id)
                    ->first();
                    
                if (!$clienteSeleccionado) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para crear pedidos para este cliente'
                    ], 403);
                }
            }

            // Obtener datos del cliente
            $cliente = Cliente::where('id', $clienteId)->first();

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar disponible del cliente
            $disponible = $cliente->disponible ?? 0;
            
            // Debug: Log de valores para verificar
            \Log::info('Debug Pedido - Cliente ID: ' . $cliente->id);
            \Log::info('Debug Pedido - Cliente Nombre: ' . $cliente->nombre);
            \Log::info('Debug Pedido - Disponible: ' . $disponible);
            \Log::info('Debug Pedido - Cantidad Solicitada: ' . $request->cantidad_solicitada);

            if ($request->cantidad_solicitada > $disponible) {
                return response()->json([
                    'success' => false,
                    'message' => "La cantidad solicitada excede tu disponible. Máximo permitido: {$disponible} litros"
                ], 422);
            }

            // Crear el pedido (sin depósito específico)
            $pedido = Pedido::create([
                'cliente_id' => $clienteId, // Usar el cliente seleccionado
                'deposito_id' => null, // Ya no se asigna un depósito específico
                'cantidad_solicitada' => $request->cantidad_solicitada,
                'observaciones' => $request->observaciones,
                'estado' => 'pendiente',
                'fecha_solicitud' => now(),
            ]);

            // Enviar notificación FCM al administrador sobre el nuevo pedido
            try {
                FcmNotificationService::sendNewPedidoNotificationToAdmin($pedido);
                \Log::info("Notificación FCM enviada al administrador sobre nuevo pedido del cliente {$pedido->cliente_id}");
            } catch (\Exception $e) {
                \Log::error("Error enviando notificación FCM al administrador: " . $e->getMessage());
                // No fallar la operación principal por error en notificación
            }

            // Si el pedido es para una sucursal diferente al usuario actual, notificar al usuario de la sucursal
            if ($pedido->cliente_id != $user->cliente_id) {
                try {
                    // Buscar el usuario asociado a la sucursal
                    $sucursalUser = User::where('cliente_id', $pedido->cliente_id)->first();
                    
                    if ($sucursalUser && $sucursalUser->fcm_token) {
                        // Enviar notificación al usuario de la sucursal
                        FcmNotificationService::sendPedidoNotificationToSucursal($pedido, $sucursalUser);
                        \Log::info("Notificación FCM enviada al usuario de sucursal {$sucursalUser->id} sobre nuevo pedido del cliente {$pedido->cliente_id}");
                    } else {
                        \Log::warning("No se encontró usuario con FCM token para la sucursal {$pedido->cliente_id}");
                    }
                } catch (\Exception $e) {
                    \Log::error("Error enviando notificación FCM a la sucursal: " . $e->getMessage());
                    // No fallar la operación principal por error en notificación
                }
            }

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
            
            if (!$user || !$user->cliente_id) {
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

            // Obtener el cliente del usuario
            $clienteUsuario = Cliente::where('id', $user->cliente_id)->first();
            
            if (!$clienteUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente del usuario no encontrado'
                ], 404);
            }

            // Determinar qué clientes puede calificar
            $clientesIds = [$user->cliente_id]; // Siempre incluir el cliente del usuario
            
            // Si es cliente padre, incluir también las sucursales
            if ($clienteUsuario->parent == 0) {
                $sucursales = Cliente::where('parent', $user->cliente_id)->pluck('id')->toArray();
                $clientesIds = array_merge($clientesIds, $sucursales);
            }

            $pedido = Pedido::where('id', $id)
                ->whereIn('cliente_id', $clientesIds)
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
     * Cancelar un pedido (solo si está pendiente o aprobado)
     */
    public function cancelarPedido(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            // Obtener el cliente del usuario
            $clienteUsuario = Cliente::where('id', $user->cliente_id)->first();
            
            if (!$clienteUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente del usuario no encontrado'
                ], 404);
            }

            // Determinar qué clientes puede cancelar
            $clientesIds = [$user->cliente_id]; // Siempre incluir el cliente del usuario
            
            // Si es cliente padre, incluir también las sucursales
            if ($clienteUsuario->parent == 0) {
                $sucursales = Cliente::where('parent', $user->cliente_id)->pluck('id')->toArray();
                $clientesIds = array_merge($clientesIds, $sucursales);
            }

            $pedido = Pedido::where('id', $id)
                ->whereIn('cliente_id', $clientesIds)
                ->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // Permitir cancelar pedidos en estado 'pendiente' o 'aprobado'
            if (!in_array($pedido->estado, ['pendiente', 'aprobado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar pedidos pendientes o aprobados'
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
     * Obtener estadísticas de pedidos del cliente y sus sucursales
     */
    public function getEstadisticasPedidos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            // Obtener el cliente principal del usuario
            $clientePrincipal = Cliente::where('id', $user->cliente_id)->first();
            
            if (!$clientePrincipal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Determinar qué clientes incluir en la consulta
            $clientesIds = [$user->cliente_id]; // Siempre incluir el cliente del usuario
            
            // Si es cliente padre (parent = 0), incluir también las sucursales
            if ($clientePrincipal->parent == 0) {
                $sucursales = Cliente::where('parent', $user->cliente_id)->pluck('id')->toArray();
                $clientesIds = array_merge($clientesIds, $sucursales);
                
                \Log::info("Estadísticas - Cliente padre {$user->cliente_id} - Incluyendo sucursales: " . implode(', ', $sucursales));
            }

            // Base query para los clientes (principal + sucursales si aplica)
            $baseQuery = Pedido::whereIn('cliente_id', $clientesIds);

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

            \Log::info("Estadísticas calculadas para cliente(s) " . implode(', ', $clientesIds));

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

    /**
     * Obtener todos los pedidos pendientes (para administradores)
     */
    public function getPedidosPendientes(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario es administrador (id_perfil = 2)
            if ($user->id_perfil != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado. Solo administradores pueden ver todos los pedidos'
                ], 403);
            }

            $pedidos = Pedido::with(['cliente', 'deposito'])
                ->where('estado', 'pendiente')
                ->orderBy('fecha_solicitud', 'asc')
                ->get();



            return response()->json([
                'success' => true,
                'data' => $pedidos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos pendientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los pedidos (para administradores)
     */
    public function getTodosLosPedidos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario es administrador (id_perfil = 2)
            if ($user->id_perfil != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado. Solo administradores pueden ver todos los pedidos'
                ], 403);
            }

            $query = Pedido::with(['cliente', 'deposito']);

            // Filtrar por estado si se proporciona
            if ($request->has('estado') && $request->estado !== 'todos') {
                $query->where('estado', $request->estado);
            }

            // Filtrar por cliente si se proporciona
            if ($request->has('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            $pedidos = $query->orderBy('fecha_solicitud', 'desc')->get();



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
     * Aprobar un pedido (para administradores)
     */
    public function aprobarPedido(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario es administrador (id_perfil = 2)
            if ($user->id_perfil != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado. Solo administradores pueden aprobar pedidos'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'cantidad_aprobada' => 'required|numeric|min:0.01',
                'observaciones_admin' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pedido = Pedido::with(['cliente', 'deposito'])->find($id);

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            if ($pedido->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar pedidos pendientes'
                ], 422);
            }

            // Verificar que la cantidad aprobada no exceda la solicitada
            if ($request->cantidad_aprobada > $pedido->cantidad_solicitada) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad aprobada no puede exceder la cantidad solicitada'
                ], 422);
            }

            // Verificar disponibilidad del cliente
            $cliente = Cliente::find($pedido->cliente_id);
            
            // Si el cliente no tiene disponibilidad registrada, verificar en movimientosCombustible
            if (!$cliente || $cliente->disponible === null) {
                // Buscar movimientos de combustible para este cliente
                $movimientosCliente = \App\Models\MovimientoCombustible::where('cliente_id', $pedido->cliente_id)->get();
                
                if ($movimientosCliente->isEmpty()) {
                    // No hay movimientos registrados para este cliente
                    return response()->json([
                        'success' => false,
                        'message' => 'El cliente no tiene historial de movimientos de combustible. Contacte al administrador para configurar disponibilidad inicial.'
                    ], 422);
                }
                
                // Calcular disponibilidad basada en movimientos
                $disponibilidadCalculada = $movimientosCliente->sum(function($movimiento) {
                    return $movimiento->tipo_movimiento === 'entrada' ? $movimiento->cantidad_litros : -$movimiento->cantidad_litros;
                });
                
                if ($disponibilidadCalculada < $request->cantidad_aprobada) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El cliente no tiene suficiente disponibilidad. Disponible: ' . $disponibilidadCalculada . ' litros'
                    ], 422);
                }
            } else {
                // Usar disponibilidad del modelo Cliente
                if ($cliente->disponible < $request->cantidad_aprobada) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El cliente no tiene suficiente disponibilidad. Disponible: ' . $cliente->disponible . ' litros'
                    ], 422);
                }
            }

            // Guardar el estado anterior para la notificación
            $oldStatus = $pedido->estado;
            
            // Actualizar el pedido (sin descontar del disponible del cliente)
            $pedido->update([
                'estado' => 'aprobado',
                'cantidad_aprobada' => $request->cantidad_aprobada,
                'observaciones_admin' => $request->observaciones_admin,
                'fecha_aprobacion' => now(),
            ]);

            \Log::info("Pedido {$pedido->id} aprobado por admin {$user->id} - Sin descuento de disponible");

            // Enviar notificación FCM al cliente
            try {
                FcmNotificationService::sendPedidoStatusNotification(
                    $pedido,
                    $oldStatus,
                    'aprobado',
                    $request->observaciones_admin
                );
                \Log::info("Notificación FCM enviada al cliente {$pedido->cliente_id} por aprobación de pedido");
            } catch (\Exception $e) {
                \Log::error("Error enviando notificación FCM: " . $e->getMessage());
                // No fallar la operación principal por error en notificación
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido aprobado exitosamente',
                'data' => $pedido->fresh(['cliente', 'deposito'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar un pedido (para administradores)
     */
    public function rechazarPedido(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario es administrador (id_perfil = 2)
            if ($user->id_perfil != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado. Solo administradores pueden rechazar pedidos'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'motivo' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pedido = Pedido::with(['cliente', 'deposito'])->find($id);

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            if ($pedido->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar pedidos pendientes'
                ], 422);
            }

            // Guardar el estado anterior para la notificación
            $oldStatus = $pedido->estado;
            
            $pedido->update([
                'estado' => 'rechazado',
                'observaciones_admin' => $request->motivo,
                'fecha_aprobacion' => now(),
            ]);

            \Log::info("Pedido {$pedido->id} rechazado por admin {$user->id}");

            // Enviar notificación FCM al cliente
            try {
                FcmNotificationService::sendPedidoStatusNotification(
                    $pedido,
                    $oldStatus,
                    'rechazado',
                    $request->motivo
                );
                \Log::info("Notificación FCM enviada al cliente {$pedido->cliente_id} por rechazo de pedido");
            } catch (\Exception $e) {
                \Log::error("Error enviando notificación FCM: " . $e->getMessage());
                // No fallar la operación principal por error en notificación
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido rechazado exitosamente',
                'data' => $pedido->fresh(['cliente', 'deposito'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

     public function aprobar(Request $request, $id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado.'], 404);
        }

        // Valida que el pedido esté en estado 'Pendiente'
        if ($pedido->estado !== 'Pendiente') {
            return response()->json(['error' => 'El pedido ya ha sido procesado.'], 400);
        }

        // Validar que el vehículo haya sido asignado (en el frontend)
        $request->validate([
            'vehiculo' => 'required'
        ]);

        $pedido->estado = 'Aprobado';
        $pedido->vehiculo_asignado = $request->input('vehiculo');
        $pedido->save();

        return response()->json(['message' => 'Pedido aprobado con éxito.']);
    }

    public function despachar(Request $request, $id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado.'], 404);
        }

        // Valida que el pedido esté en estado 'Aprobado'
        if ($pedido->estado !== 'Aprobado') {
            return response()->json(['error' => 'El pedido no está aprobado para despacho.'], 400);
        }

        $request->validate([
            'deposito_id' => 'required|exists:depositos,id',
        ]);

        $deposito = Deposito::find($request->input('deposito_id'));

        if ($deposito->disponible < $pedido->cantidad) {
            return response()->json(['error' => 'El deposito no tiene suficiente combustible.'], 400);
        }

        // 1. Actualiza el estado del pedido
        $pedido->estado = 'Despachado';
        $pedido->save();

        // 2. Reduce la cantidad en el deposito
        $deposito->disponible -= $pedido->cantidad;
        $deposito->save();

        // 3. Opcional: crea un nuevo registro de despacho, si tu modelo lo requiere
        // Despacho::create([ ... ]);

        // 4. Actualiza el cupo disponible del cliente (simulación)
        $cliente = Cliente::find($pedido->cliente_id);
        if ($cliente) {
            $cliente->disponible -= $pedido->cantidad;
            $cliente->save();
        }

        return response()->json(['message' => 'Despacho creado con éxito.']);
    }

    /**
     * Actualizar un pedido (para administradores)
     */
    public function actualizarPedido(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario es administrador (id_perfil = 2)
            if ($user->id_perfil != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado. Solo administradores pueden actualizar pedidos'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'sometimes|in:pendiente,aprobado,rechazado,en_proceso,completado,cancelado',
                'cantidad_aprobada' => 'sometimes|numeric|min:0.01',
                'observaciones_admin' => 'sometimes|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pedido = Pedido::with(['cliente', 'deposito'])->find($id);

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $updateData = $request->only(['estado', 'cantidad_aprobada', 'observaciones_admin']);

            // Si se está aprobando, agregar fecha de aprobación
            if (isset($updateData['estado']) && $updateData['estado'] === 'aprobado') {
                $updateData['fecha_aprobacion'] = now();
            }

            // Si se está completando, agregar fecha de completado
            if (isset($updateData['estado']) && $updateData['estado'] === 'completado') {
                $updateData['fecha_completado'] = now();
            }

            // Guardar el estado anterior para la notificación
            $oldStatus = $pedido->estado;
            
            $pedido->update($updateData);

            \Log::info("Pedido {$pedido->id} actualizado por admin {$user->id}");

            // Enviar notificación FCM si cambió el estatus
            if (isset($updateData['estado']) && $updateData['estado'] !== $oldStatus) {
                try {
                    FcmNotificationService::sendPedidoStatusNotification(
                        $pedido,
                        $oldStatus,
                        $updateData['estado'],
                        $updateData['observaciones_admin'] ?? null
                    );
                    \Log::info("Notificación FCM enviada al cliente {$pedido->cliente_id} por cambio de estatus a {$updateData['estado']}");
                } catch (\Exception $e) {
                    \Log::error("Error enviando notificación FCM: " . $e->getMessage());
                    // No fallar la operación principal por error en notificación
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente',
                'data' => $pedido->fresh(['cliente', 'deposito'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar pedido: ' . $e->getMessage()
            ], 500);
        }
    }
}
