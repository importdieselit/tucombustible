<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminDespachoController extends Controller
{
    /**
     * Realizar despacho de combustible desde el admin
     */
    public function realizarDespacho(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_vehiculo' => 'required|integer|exists:vehiculos,id',
            'cantidad_litros' => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener información del vehículo y cliente
            $vehiculo = DB::table('vehiculos')
                ->join('clientes', 'vehiculos.id_cliente', '=', 'clientes.id')
                ->select(
                    'vehiculos.id as vehiculo_id',
                    'vehiculos.placa',
                    'clientes.id as cliente_id',
                    'clientes.nombre as cliente_nombre',
                    'clientes.disponible'
                )
                ->where('vehiculos.id', $request->id_vehiculo)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            // Verificar que el cliente tenga suficiente combustible disponible
            if ($vehiculo->disponible < $request->cantidad_litros) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no tiene suficiente combustible disponible. Disponible: ' . $vehiculo->disponible . ' L'
                ], 400);
            }

            // Crear el movimiento de combustible
            $movimientoId = DB::table('movimientos_combustible')->insertGetId([
                'tipo_movimiento' => 'salida',
                'deposito_id' => 3, // Depósito fijo
                'cliente_id' => $vehiculo->cliente_id,
                'vehiculo_id' => $vehiculo->vehiculo_id,
                'cantidad_litros' => $request->cantidad_litros,
                'observaciones' => $request->observaciones ?? 'Despacho realizado por administrador',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Descontar la cantidad del disponible del cliente
            DB::table('clientes')
                ->where('id', $vehiculo->cliente_id)
                ->decrement('disponible', $request->cantidad_litros);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Despacho realizado exitosamente',
                'data' => [
                    'movimiento_id' => $movimientoId,
                    'vehiculo' => $vehiculo->placa,
                    'cliente' => $vehiculo->cliente_nombre,
                    'cantidad_despachada' => $request->cantidad_litros,
                    'disponible_restante' => $vehiculo->disponible - $request->cantidad_litros,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Error en realizarDespacho: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar el despacho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de despachos realizados
     */
    public function getHistorialDespachos(Request $request): JsonResponse
    {
        try {
            $despachos = DB::table('movimientos_combustible as mc')
                ->join('vehiculos as v', 'mc.vehiculo_id', '=', 'v.id')
                ->join('clientes as c', 'mc.cliente_id', '=', 'c.id')
                ->join('depositos as d', 'mc.deposito_id', '=', 'd.id')
                ->select([
                    'mc.id',
                    'mc.cantidad_litros',
                    'mc.observaciones',
                    'mc.created_at',
                    'v.placa',
                    'c.nombre as cliente_nombre',
                    'd.serial as deposito_serial'
                ])
                ->where('mc.tipo_movimiento', 'salida')
                ->orderBy('mc.created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $despachos
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getHistorialDespachos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de despachos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
