<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\MovimientoCombustible;
use App\Models\Deposito;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MovimientoCombustibleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $movimientos = MovimientoCombustible::with(['deposito', 'proveedor', 'cliente'])->get();

        return response()->json([
            'success' => true,
            'data' => $movimientos
        ]);
    }

    /**
     * Get movements by deposito ID
     */
    public function getByDeposito($depositoId): JsonResponse
    {
        try {
            $movimientos = MovimientoCombustible::with(['deposito', 'proveedor', 'cliente'])
                ->where('deposito_id', $depositoId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $movimientos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos del depósito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo_movimiento' => 'required|in:entrada,salida',
            'deposito_id' => 'required|exists:depositos,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cantidad_litros' => 'required|integer|min:1',
            'observaciones' => 'nullable|string',
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

            // Verificar que hay suficiente combustible para salidas
            if ($request->tipo_movimiento === 'salida') {
                $deposito = Deposito::find($request->deposito_id);
                if ($deposito->nivel_actual_litros < $request->cantidad_litros) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay suficiente combustible en el depósito'
                    ], 400);
                }
            }

            $movimiento = MovimientoCombustible::create($request->all());

            // Actualizar el nivel del depósito
            $deposito = Deposito::find($request->deposito_id);
            if ($request->tipo_movimiento === 'entrada') {
                $deposito->nivel_actual_litros += $request->cantidad_litros;
            } else {
                $deposito->nivel_actual_litros -= $request->cantidad_litros;
            }
            $deposito->save();

            DB::commit();

            $movimiento->load(['deposito', 'proveedor', 'cliente']);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de combustible registrado exitosamente',
                'data' => $movimiento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar movimiento de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MovimientoCombustible $movimiento): JsonResponse
    {
        $movimiento->load(['deposito', 'proveedor', 'cliente']);

        return response()->json([
            'success' => true,
            'data' => $movimiento
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MovimientoCombustible $movimiento): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo_movimiento' => 'required|in:entrada,salida',
            'deposito_id' => 'required|exists:depositos,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cantidad_litros' => 'required|integer|min:1',
            'observaciones' => 'nullable|string',
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

            // Revertir el movimiento anterior
            $depositoAnterior = Deposito::find($movimiento->deposito_id);
            if ($movimiento->tipo_movimiento === 'entrada') {
                $depositoAnterior->nivel_actual_litros -= $movimiento->cantidad_litros;
            } else {
                $depositoAnterior->nivel_actual_litros += $movimiento->cantidad_litros;
            }
            $depositoAnterior->save();

            // Verificar que hay suficiente combustible para el nuevo movimiento
            if ($request->tipo_movimiento === 'salida') {
                $depositoNuevo = Deposito::find($request->deposito_id);
                if ($depositoNuevo->nivel_actual_litros < $request->cantidad_litros) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay suficiente combustible en el depósito'
                    ], 400);
                }
            }

            $movimiento->update($request->all());

            // Aplicar el nuevo movimiento
            $depositoNuevo = Deposito::find($request->deposito_id);
            if ($request->tipo_movimiento === 'entrada') {
                $depositoNuevo->nivel_actual_litros += $request->cantidad_litros;
            } else {
                $depositoNuevo->nivel_actual_litros -= $request->cantidad_litros;
            }
            $depositoNuevo->save();

            DB::commit();

            $movimiento->load(['deposito', 'proveedor', 'cliente']);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de combustible actualizado exitosamente',
                'data' => $movimiento
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar movimiento de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MovimientoCombustible $movimiento): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Revertir el movimiento
            $deposito = Deposito::find($movimiento->deposito_id);
            if ($movimiento->tipo_movimiento === 'entrada') {
                $deposito->nivel_actual_litros -= $movimiento->cantidad_litros;
            } else {
                $deposito->nivel_actual_litros += $movimiento->cantidad_litros;
            }
            $deposito->save();

            $movimiento->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de combustible eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar movimiento de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get client's movement history
     */
    public function getMiHistorial(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $clienteId = $user->cliente_id;

            if (!$clienteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $query = DB::table('movimientos_combustible as mc')
                ->select([
                    'mc.id',
                    'mc.tipo_movimiento',
                    'mc.deposito_id',
                    'mc.proveedor_id',
                    'mc.cliente_id',
                    'mc.cantidad_litros',
                    'mc.observaciones',
                    'mc.created_at',
                    'mc.updated_at',
                    'mc.vehiculo_id',
                    'mc.cisterna_id',
                    'v.placa as vehiculo_placa',
                    'v.marca as vehiculo_marca',
                    'v.modelo as vehiculo_modelo',
                    'c.nombre as cliente_nombre',
                    'd.serial as deposito_serial',
                    'd.producto as deposito_producto'
                ])
                ->leftJoin('vehiculos as v', 'mc.vehiculo_id', '=', 'v.id')
                ->leftJoin('depositos as d', 'mc.deposito_id', '=', 'd.id')
                ->leftJoin('clientes as c', 'mc.cliente_id', '=', 'c.id')
                ->where('mc.cliente_id', $clienteId)
                ->where('mc.tipo_movimiento', 'salida') // Solo despachos (salidas)
                ->orderBy('mc.created_at', 'desc');

            // Filtros
            if ($request->has('vehiculo_id') && $request->vehiculo_id) {
                $query->where('mc.vehiculo_id', $request->vehiculo_id);
            }

            if ($request->has('fecha_inicio') && $request->fecha_inicio) {
                $query->whereDate('mc.created_at', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin') && $request->fecha_fin) {
                $query->whereDate('mc.created_at', '<=', $request->fecha_fin);
            }

            // Paginación
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);
            
            $movimientos = $query->limit($limit)->offset($offset)->get();

            return response()->json([
                'success' => true,
                'data' => $movimientos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de movimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for client's movement history
     */
    public function getEstadisticasMiHistorial(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $clienteId = $user->cliente_id;

            if (!$clienteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $baseQuery = DB::table('movimientos_combustible')
                ->where('cliente_id', $clienteId)
                ->where('tipo_movimiento', 'salida'); // Solo despachos (salidas)

            // Filtros de fecha
            if ($request->has('fecha_inicio') && $request->fecha_inicio) {
                $baseQuery->whereDate('created_at', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin') && $request->fecha_fin) {
                $baseQuery->whereDate('created_at', '<=', $request->fecha_fin);
            }

            $estadisticas = [
                'total_despachos' => (clone $baseQuery)->count(),
                'total_litros' => (clone $baseQuery)->sum('cantidad_litros') ?? 0,
                'promedio_por_despacho' => (clone $baseQuery)->avg('cantidad_litros') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information for a specific movement
     */
    public function getDetalle($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $clienteId = $user->cliente_id;

            if (!$clienteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $movimiento = DB::table('movimientos_combustible as mc')
                ->select([
                    'mc.id',
                    'mc.tipo_movimiento',
                    'mc.deposito_id',
                    'mc.proveedor_id',
                    'mc.cliente_id',
                    'mc.cantidad_litros',
                    'mc.observaciones',
                    'mc.created_at',
                    'mc.updated_at',
                    'mc.vehiculo_id',
                    'mc.cisterna_id',
                    'v.placa as vehiculo_placa',
                    'v.marca as vehiculo_marca',
                    'v.modelo as vehiculo_modelo',
                    'c.nombre as cliente_nombre',
                    'd.serial as deposito_serial',
                    'd.producto as deposito_producto'
                ])
                ->leftJoin('vehiculos as v', 'mc.vehiculo_id', '=', 'v.id')
                ->leftJoin('depositos as d', 'mc.deposito_id', '=', 'd.id')
                ->leftJoin('clientes as c', 'mc.cliente_id', '=', 'c.id')
                ->where('mc.id', $id)
                ->where('mc.cliente_id', $clienteId)
                ->where('mc.tipo_movimiento', 'salida') // Solo despachos (salidas)
                ->first();

            if (!$movimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movimiento no encontrado o no tienes permisos para verlo'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $movimiento
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle del movimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get historial de despachos para administradores
     */
    public function getHistorialAdmin(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);
            $clienteId = $request->get('cliente_id');
            $vehiculoId = $request->get('vehiculo_id');
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');

            $query = DB::table('movimientos_combustible as mc')
                ->select([
                    'mc.id',
                    'mc.tipo_movimiento',
                    'mc.deposito_id',
                    'mc.proveedor_id',
                    'mc.cliente_id',
                    'mc.cantidad_litros',
                    'mc.observaciones',
                    'mc.created_at',
                    'mc.updated_at',
                    'mc.vehiculo_id',
                    'mc.cisterna_id',
                    'v.placa as vehiculo_placa',
                    'v.marca as vehiculo_marca',
                    'v.modelo as vehiculo_modelo',
                    'c.nombre as cliente_nombre',
                    'd.serial as deposito_serial',
                    'd.producto as deposito_producto'
                ])
                ->leftJoin('vehiculos as v', 'mc.vehiculo_id', '=', 'v.id')
                ->leftJoin('depositos as d', 'mc.deposito_id', '=', 'd.id')
                ->leftJoin('clientes as c', 'mc.cliente_id', '=', 'c.id')
                ->where('mc.tipo_movimiento', 'salida') // Solo despachos (salidas)
                ->orderBy('mc.created_at', 'desc');

            // Aplicar filtros
            if ($clienteId) {
                $query->where('mc.cliente_id', $clienteId);
            }

            if ($vehiculoId) {
                $query->where('mc.vehiculo_id', $vehiculoId);
            }

            if ($fechaInicio) {
                $query->whereDate('mc.created_at', '>=', $fechaInicio);
            }

            if ($fechaFin) {
                $query->whereDate('mc.created_at', '<=', $fechaFin);
            }

            $movimientos = $query->limit($limit)->offset($offset)->get();

            return response()->json([
                'success' => true,
                'data' => $movimientos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de despachos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get estadísticas del historial para administradores
     */
    public function getEstadisticasHistorialAdmin(Request $request): JsonResponse
    {
        try {
            $clienteId = $request->get('cliente_id');
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');

            $baseQuery = DB::table('movimientos_combustible')
                ->where('tipo_movimiento', 'salida'); // Solo despachos (salidas)

            // Aplicar filtros
            if ($clienteId) {
                $baseQuery->where('cliente_id', $clienteId);
            }

            if ($fechaInicio) {
                $baseQuery->whereDate('created_at', '>=', $fechaInicio);
            }

            if ($fechaFin) {
                $baseQuery->whereDate('created_at', '<=', $fechaFin);
            }

            $estadisticas = [
                'total_despachos' => (clone $baseQuery)->count(),
                'total_litros' => (clone $baseQuery)->sum('cantidad_litros') ?? 0,
                'promedio_por_despacho' => (clone $baseQuery)->avg('cantidad_litros') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detalle de un movimiento para administradores
     */
    public function getDetalleAdmin($id): JsonResponse
    {
        try {
            $movimiento = DB::table('movimientos_combustible as mc')
                ->select([
                    'mc.id',
                    'mc.tipo_movimiento',
                    'mc.deposito_id',
                    'mc.proveedor_id',
                    'mc.cliente_id',
                    'mc.cantidad_litros',
                    'mc.observaciones',
                    'mc.created_at',
                    'mc.updated_at',
                    'mc.vehiculo_id',
                    'mc.cisterna_id',
                    'v.placa as vehiculo_placa',
                    'v.marca as vehiculo_marca',
                    'v.modelo as vehiculo_modelo',
                    'c.nombre as cliente_nombre',
                    'd.serial as deposito_serial',
                    'd.producto as deposito_producto'
                ])
                ->leftJoin('vehiculos as v', 'mc.vehiculo_id', '=', 'v.id')
                ->leftJoin('depositos as d', 'mc.deposito_id', '=', 'd.id')
                ->leftJoin('clientes as c', 'mc.cliente_id', '=', 'c.id')
                ->where('mc.id', $id)
                ->where('mc.tipo_movimiento', 'salida') // Solo despachos (salidas)
                ->first();

            if (!$movimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movimiento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $movimiento
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle del movimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
