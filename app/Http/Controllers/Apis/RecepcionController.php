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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el pedido
            $pedido = Pedido::find($request->pedido_id);
            
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

            // Verificar que el depósito existe y obtenerlo
            $deposito = Deposito::find($pedido->deposito_id);

            if (!$deposito) {
                return response()->json([
                    'success' => false,
                    'message' => 'Depósito no encontrado'
                ], 404);
            }

            // Verificar que el pedido pertenece al cliente del usuario (ya verificado arriba)
            // El depósito se obtiene del pedido que ya fue verificado que pertenece al cliente

            // Verificar que la cantidad recibida no exceda la capacidad del depósito
            $nuevoNivel = $deposito->nivel_actual_litros + $request->cantidad_recibida;
            
            if ($nuevoNivel > $deposito->capacidad_litros) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad recibida excede la capacidad del depósito'
                ], 422);
            }

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Registrar el movimiento en la tabla movimientos_combustible
                $movimiento = MovimientoCombustible::create([
                    'tipo_movimiento' => 'entrada',
                    'deposito_id' => $pedido->deposito_id,
                    'cliente_id' => $user->cliente_id,
                    'cantidad_litros' => $request->cantidad_recibida,
                    'observaciones' => "Recepción de pedido #{$pedido->id}. " . ($request->observaciones ?? ''),
                ]);

                // Actualizar el nivel del depósito
                $deposito->update([
                    'nivel_actual_litros' => $nuevoNivel
                ]);

                // Actualizar el estado del pedido a 'completado' (igual que en mecánico)
                DB::table('pedidos')
                    ->where('id', $request->pedido_id)
                    ->update([
                        'estado' => 'completado',
                        'fecha_completado' => now(),
                        'updated_at' => now(),
                    ]);

                DB::commit();

                $resultado = [
                    'id' => $movimiento->id,
                    'pedido_id' => $pedido->id,
                    'deposito_id' => $pedido->deposito_id,
                    'cantidad_recibida' => $request->cantidad_recibida,
                    'observaciones' => $request->observaciones,
                    'fecha' => $movimiento->created_at,
                    'nuevo_nivel_deposito' => $nuevoNivel,
                ];

                \Log::info("Recepción registrada por usuario {$user->id} - Cliente: {$user->cliente_id} - Pedido: {$pedido->id}");

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
