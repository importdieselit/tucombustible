<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DespachoController extends Controller
{
    /**
     * Realizar despacho de combustible a vehículo
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehiculo_id' => 'required|integer|exists:vehiculos,id',
            'deposito_id' => 'required|integer|exists:depositos,id',
            'cantidad_litros' => 'required|numeric|min:0.1',
            'observaciones' => 'nullable|string|max:500',
            'tipo' => 'nullable|string|in:despacho_admin,despacho_normal',
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
            $vehiculo = DB::table('vehiculos as v')
                ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id')
                ->select([
                    'v.id',
                    'v.placa',
                    'v.id_cliente',
                    'c.nombre as cliente_nombre',
                    'c.disponible as cliente_disponible'
                ])
                ->where('v.id', $request->vehiculo_id)
                ->where('v.estatus', 1)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado o inactivo'
                ], 404);
            }

            // Obtener información del depósito
            $deposito = DB::table('depositos')
                ->select(['id', 'serial', 'nivel_actual_litros', 'capacidad_litros', 'producto'])
                ->where('id', $request->deposito_id)
                ->first();

            if (!$deposito) {
                return response()->json([
                    'success' => false,
                    'message' => 'Depósito no encontrado'
                ], 404);
            }

            // Verificar que hay suficiente combustible en el depósito
            if ($deposito->nivel_actual_litros < $request->cantidad_litros) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay suficiente combustible en el depósito. Disponible: ' . $deposito->nivel_actual_litros . ' L'
                ], 400);
            }

            // Verificar que el cliente tiene suficiente disponible
            if ($vehiculo->cliente_disponible < $request->cantidad_litros) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no tiene suficiente combustible disponible. Disponible: ' . $vehiculo->cliente_disponible . ' L'
                ], 400);
            }

            // Crear el registro de despacho
            $despachoId = DB::table('despachos')->insertGetId([
                'vehiculo_id' => $request->vehiculo_id,
                'deposito_id' => $request->deposito_id,
                'cantidad_litros' => $request->cantidad_litros,
                'observaciones' => $request->observaciones,
                'tipo' => $request->tipo ?? 'despacho_admin',
                'fecha_despacho' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Actualizar el nivel del depósito
            DB::table('depositos')
                ->where('id', $request->deposito_id)
                ->update([
                    'nivel_actual_litros' => $deposito->nivel_actual_litros - $request->cantidad_litros,
                    'updated_at' => now(),
                ]);

            // Actualizar el disponible del cliente
            DB::table('clientes')
                ->where('id', $vehiculo->id_cliente)
                ->update([
                    'disponible' => $vehiculo->cliente_disponible - $request->cantidad_litros,
                    'updated_at' => now(),
                ]);

            // Crear registro de movimiento de combustible
            DB::table('movimientos_combustible')->insert([
                'tipo_movimiento' => 'salida',
                'deposito_id' => $request->deposito_id,
                'cliente_id' => $vehiculo->id_cliente,
                'cantidad_litros' => $request->cantidad_litros,
                'observaciones' => $request->observaciones ?? 'Despacho a vehículo ' . $vehiculo->placa,
                'despacho_id' => $despachoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Obtener información completa del despacho realizado
            $despachoCompleto = DB::table('despachos as d')
                ->leftJoin('vehiculos as v', 'd.vehiculo_id', '=', 'v.id')
                ->leftJoin('depositos as dep', 'd.deposito_id', '=', 'dep.id')
                ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id')
                ->select([
                    'd.id as despacho_id',
                    'd.cantidad_litros',
                    'd.observaciones',
                    'd.fecha_despacho',
                    'v.placa',
                    'v.marca',
                    'v.modelo',
                    'c.nombre as cliente_nombre',
                    'dep.serial as deposito_serial',
                    'dep.producto'
                ])
                ->where('d.id', $despachoId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Despacho realizado exitosamente',
                'data' => $despachoCompleto
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar despacho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de despachos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DB::table('despachos as d')
                ->leftJoin('vehiculos as v', 'd.vehiculo_id', '=', 'v.id')
                ->leftJoin('depositos as dep', 'd.deposito_id', '=', 'dep.id')
                ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id')
                ->select([
                    'd.id as despacho_id',
                    'd.cantidad_litros',
                    'd.observaciones',
                    'd.fecha_despacho',
                    'd.tipo',
                    'v.placa',
                    'v.marca',
                    'v.modelo',
                    'c.nombre as cliente_nombre',
                    'dep.serial as deposito_serial',
                    'dep.producto'
                ])
                ->orderBy('d.fecha_despacho', 'desc');

            // Aplicar filtros si se proporcionan
            if ($request->has('fecha_inicio')) {
                $query->whereDate('d.fecha_despacho', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->whereDate('d.fecha_despacho', '<=', $request->fecha_fin);
            }

            if ($request->has('cliente_id')) {
                $query->where('v.id_cliente', $request->cliente_id);
            }

            if ($request->has('vehiculo_id')) {
                $query->where('d.vehiculo_id', $request->vehiculo_id);
            }

            $despachos = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Despachos obtenidos exitosamente',
                'data' => $despachos->items(),
                'pagination' => [
                    'current_page' => $despachos->currentPage(),
                    'last_page' => $despachos->lastPage(),
                    'per_page' => $despachos->perPage(),
                    'total' => $despachos->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener despachos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un despacho específico
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $despacho = DB::table('despachos as d')
                ->leftJoin('vehiculos as v', 'd.vehiculo_id', '=', 'v.id')
                ->leftJoin('depositos as dep', 'd.deposito_id', '=', 'dep.id')
                ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id')
                ->select([
                    'd.id as despacho_id',
                    'd.cantidad_litros',
                    'd.observaciones',
                    'd.fecha_despacho',
                    'd.tipo',
                    'v.placa',
                    'v.marca',
                    'v.modelo',
                    'c.nombre as cliente_nombre',
                    'dep.serial as deposito_serial',
                    'dep.producto'
                ])
                ->where('d.id', $id)
                ->first();

            if (!$despacho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Despacho no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Despacho obtenido exitosamente',
                'data' => $despacho
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener despacho',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}