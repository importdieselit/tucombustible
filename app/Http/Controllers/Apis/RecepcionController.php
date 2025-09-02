<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\MovimientoCombustible;
use App\Models\Pedido;
use App\Models\Deposito;
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

            // Verificar que el pedido pertenece al cliente del usuario
            if ($pedido->cliente_id !== $user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido no pertenece a su cliente'
                ], 403);
            }

            // Verificar que el pedido esté aprobado o en proceso
            if (!in_array($pedido->estado, ['aprobado', 'en_proceso'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido debe estar aprobado o en proceso para registrar la recepción'
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
                // Descontar del disponible del cliente
                $cliente->update([
                    'disponible' => $disponible - $request->cantidad_recibida
                ]);

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
