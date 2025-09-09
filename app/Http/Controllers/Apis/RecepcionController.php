<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\MovimientoCombustible;
use App\Models\Pedido;
use App\Models\Deposito;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecepcionController extends Controller
{
    /**
     * Registrar recepción de combustible
     */
    public function registrarRecepcion(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'pedido_id' => 'required|exists:pedidos,id',
                'cantidad_recibida' => 'required|numeric|min:0.01',
                'observaciones' => 'nullable|string|max:500',
                'calificacion' => 'nullable|integer|min:1|max:5',
                'comentario_calificacion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el pedido
            $pedido = Pedido::with('cliente')->find($request->pedido_id);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // Verificar que el pedido pertenece al cliente del usuario o a una de sus sucursales
            $clienteUsuario = Cliente::where('id', $user->cliente_id)->first();
            
            if (!$clienteUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente del usuario no encontrado'
                ], 404);
            }

            $puedeMarcarRecepcion = false;
            
            // Verificar si el pedido pertenece directamente al usuario
            if ($pedido->cliente_id === $user->cliente_id) {
                $puedeMarcarRecepcion = true;
            }
            // Si es cliente padre, verificar si el pedido pertenece a una de sus sucursales
            elseif ($clienteUsuario->parent == 0) {
                $sucursales = Cliente::where('parent', $user->cliente_id)->pluck('id')->toArray();
                if (in_array($pedido->cliente_id, $sucursales)) {
                    $puedeMarcarRecepcion = true;
                }
            }

            if (!$puedeMarcarRecepcion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para marcar recepción de este pedido'
                ], 403);
            }

            // Verificar que el pedido esté en proceso (solo se puede marcar recepción en este estado)
            if ($pedido->estado !== 'en_proceso') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede marcar recepción cuando el pedido está en proceso'
                ], 422);
            }

            // Obtener datos del cliente
            $cliente = $pedido->cliente;
            
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar que el cliente tenga suficiente disponible
            $disponible = $cliente->disponible ?? 0;
            
            if ($disponible < $request->cantidad_recibida) {
                return response()->json([
                    'success' => false,
                    'message' => "No tiene suficiente disponible. Disponible: {$disponible} litros, Solicitado: {$request->cantidad_recibida} litros"
                ], 422);
            }

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Lógica para sucursales: descontar tanto de la sucursal como del cliente padre
                if ($cliente->parent > 0) {
                    // Es una sucursal - descontar de la sucursal y del cliente padre
                    \Log::info("Procesando recepción para sucursal ID: {$cliente->id}, Parent: {$cliente->parent}");
                    
                    // 1. Descontar de la sucursal
                    $cliente->update([
                        'disponible' => $disponible - $request->cantidad_recibida
                    ]);
                    
                    // 2. Descontar del cliente padre
                    $clientePadre = \App\Models\Cliente::find($cliente->parent);
                    if ($clientePadre) {
                        $disponiblePadre = $clientePadre->disponible ?? 0;
                        $clientePadre->update([
                            'disponible' => $disponiblePadre - $request->cantidad_recibida
                        ]);
                        
                        \Log::info("Descontado de sucursal {$cliente->id}: {$request->cantidad_recibida} litros");
                        \Log::info("Descontado de cliente padre {$clientePadre->id}: {$request->cantidad_recibida} litros");
                        \Log::info("Nuevo disponible sucursal: " . ($disponible - $request->cantidad_recibida));
                        \Log::info("Nuevo disponible padre: " . ($disponiblePadre - $request->cantidad_recibida));
                    } else {
                        \Log::warning("No se encontró el cliente padre con ID: {$cliente->parent}");
                    }
                } else {
                    // Es un cliente principal - solo descontar de él
                    \Log::info("Procesando recepción para cliente principal ID: {$cliente->id}");
                    
                    $cliente->update([
                        'disponible' => $disponible - $request->cantidad_recibida
                    ]);
                    
                    \Log::info("Descontado de cliente principal {$cliente->id}: {$request->cantidad_recibida} litros");
                    \Log::info("Nuevo disponible: " . ($disponible - $request->cantidad_recibida));
                }

                // Actualizar el estado del pedido a 'completado' y calificación si se proporciona
                $updateData = [
                    'estado' => 'completado',
                    'fecha_completado' => now(),
                ];
                
                // Si se proporciona calificación, agregarla
                if ($request->has('calificacion') && $request->calificacion !== null) {
                    $updateData['calificacion'] = $request->calificacion;
                    $updateData['comentario_calificacion'] = $request->comentario_calificacion;
                }
                
                $pedido->update($updateData);

                DB::commit();

                $resultado = [
                    'pedido_id' => $pedido->id,
                    'cantidad_recibida' => $request->cantidad_recibida,
                    'observaciones' => $request->observaciones,
                    'fecha' => now(),
                    'nuevo_disponible_cliente' => $disponible - $request->cantidad_recibida,
                    'calificacion' => $request->calificacion ?? null,
                    'comentario_calificacion' => $request->comentario_calificacion ?? null,
                    'es_sucursal' => $cliente->parent > 0,
                    'cliente_padre_id' => $cliente->parent > 0 ? $cliente->parent : null,
                ];

                \Log::info("Recepción registrada por usuario {$user->id} - Cliente: {$user->cliente_id} - Pedido: {$pedido->id} - Disponible descontado: {$request->cantidad_recibida}");

                return response()->json([
                    'success' => true,
                    'message' => 'Recepción registrada exitosamente',
                    'data' => $resultado
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar recepción: ' . $e->getMessage()
            ], 500);
        }
    }
}
