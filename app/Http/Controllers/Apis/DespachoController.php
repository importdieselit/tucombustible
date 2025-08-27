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
     * Obtener todos los despachos del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $despachos = DB::table('repostaje_vehiculos as rv')
                ->leftJoin('vehiculos as v', 'rv.id_vehiculo', '=', 'v.id')
                ->leftJoin('tanques as t', 'rv.id_tanque', '=', 't.id')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->select([
                    'rv.id',
                    'rv.id_vehiculo',
                    'rv.id_tanque',
                    'rv.qty',
                    'rv.qtya',
                    'rv.rest',
                    'rv.fecha',
                    'rv.obs',
                    'rv.id_us',
                    'rv.pic',
                    'rv.type',
                    'rv.ref',
                    'rv.placa_ext',
                    'rv.nombre_ext',
                    'rv.origin',
                    'rv.id_admin',
                    'rv.ticket',
                    'v.placa as vehiculo_placa',
                    'v.flota as vehiculo_flota',
                    'm.nombre as vehiculo_marca',
                    't.serial as tanque_serial',
                    't.producto as tanque_producto'
                ])
                ->where('rv.id_us', $user->id)
                ->orderBy('rv.fecha', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Despachos obtenidos exitosamente',
                'data' => $despachos
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
            $user = $request->user();
            
            $despacho = DB::table('repostaje_vehiculos as rv')
                ->leftJoin('vehiculos as v', 'rv.id_vehiculo', '=', 'v.id')
                ->leftJoin('tanques as t', 'rv.id_tanque', '=', 't.id')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->select([
                    'rv.id',
                    'rv.id_vehiculo',
                    'rv.id_tanque',
                    'rv.qty',
                    'rv.qtya',
                    'rv.rest',
                    'rv.fecha',
                    'rv.obs',
                    'rv.id_us',
                    'rv.pic',
                    'rv.type',
                    'rv.ref',
                    'rv.placa_ext',
                    'rv.nombre_ext',
                    'rv.origin',
                    'rv.id_admin',
                    'rv.ticket',
                    'v.placa as vehiculo_placa',
                    'v.flota as vehiculo_flota',
                    'm.nombre as vehiculo_marca',
                    't.serial as tanque_serial',
                    't.producto as tanque_producto'
                ])
                ->where('rv.id', $id)
                ->where('rv.id_us', $user->id)
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

    /**
     * Crear un nuevo despacho
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_vehiculo' => 'required|integer|exists:vehiculos,id',
            'id_tanque' => 'required|integer|exists:tanques,id',
            'qty' => 'required|numeric|min:0',
            'qtya' => 'nullable|numeric|min:0',
            'rest' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'obs' => 'nullable|string',
            'pic' => 'nullable|string',
            'type' => 'nullable|integer',
            'ref' => 'nullable|string|max:100',
            'placa_ext' => 'nullable|string|max:50',
            'nombre_ext' => 'nullable|string|max:50',
            'origin' => 'nullable|string|max:255',
            'ticket' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            // Verificar que el vehículo pertenece al usuario
            $vehiculo = DB::table('vehiculos')
                ->where('id', $request->id_vehiculo)
                ->where('id_usuario', $user->id)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado o no autorizado'
                ], 404);
            }

            // Verificar que el tanque existe y pertenece al usuario
            $tanque = DB::table('tanques')
                ->where('id', $request->id_tanque)
                ->where('id_us', $user->id)
                ->first();

            if (!$tanque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanque no encontrado o no autorizado'
                ], 404);
            }

            // Obtener la cantidad actual del tanque desde repost_tanques
            $ultimoRegistro = DB::table('repost_tanques')
                ->where('id_tanque', $request->id_tanque)
                ->orderBy('id', 'desc')
                ->first();

            $cantidadActual = $ultimoRegistro ? $ultimoRegistro->qty : 0;

            // Verificar que hay suficiente combustible en el tanque
            if ($cantidadActual < $request->qty) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay suficiente combustible en el tanque. Disponible: ' . $cantidadActual . ' litros'
                ], 422);
            }

            $despachoId = DB::table('repostaje_vehiculos')->insertGetId([
                'id_vehiculo' => $request->id_vehiculo,
                'id_tanque' => $request->id_tanque,
                'qty' => $request->qty,
                'qtya' => $request->qtya ?? 0,
                'rest' => $request->rest,
                'fecha' => $request->fecha,
                'obs' => $request->obs,
                'id_us' => $user->id,
                'pic' => $request->pic,
                'type' => $request->type ?? 1,
                'ref' => $request->ref,
                'placa_ext' => $request->placa_ext,
                'nombre_ext' => $request->nombre_ext,
                'origin' => $request->origin,
                'id_admin' => null,
                'ticket' => $request->ticket,
            ]);

            // Calcular nueva cantidad después del despacho
            $nuevaCantidad = $cantidadActual - $request->qty;

            // Registrar en repost_tanques para control de inventario
            // lectura_out = cantidad despachada, qty = cantidad que queda en el tanque
            DB::table('repost_tanques')->insert([
                'id_tanque' => $request->id_tanque,
                'id_us' => $user->id,
                'lectura_in' => $cantidadActual,
                'lectura_out' => $request->qty, // Cantidad despachada
                'qty' => $nuevaCantidad, // Cantidad que queda en el tanque
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $despacho = $this->getDespachoById($despachoId);

            return response()->json([
                'success' => true,
                'message' => 'Despacho creado exitosamente',
                'data' => $despacho
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear despacho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un despacho
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_vehiculo' => 'required|integer|exists:vehiculos,id',
            'id_tanque' => 'required|integer|exists:tanques,id',
            'qty' => 'required|numeric|min:0',
            'qtya' => 'nullable|numeric|min:0',
            'rest' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'obs' => 'nullable|string',
            'pic' => 'nullable|string',
            'type' => 'nullable|integer',
            'ref' => 'nullable|string|max:100',
            'placa_ext' => 'nullable|string|max:50',
            'nombre_ext' => 'nullable|string|max:50',
            'origin' => 'nullable|string|max:255',
            'ticket' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $despacho = DB::table('repostaje_vehiculos')
                ->where('id', $id)
                ->where('id_us', $user->id)
                ->first();

            if (!$despacho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Despacho no encontrado'
                ], 404);
            }

            // Verificar que el vehículo pertenece al usuario
            $vehiculo = DB::table('vehiculos')
                ->where('id', $request->id_vehiculo)
                ->where('id_usuario', $user->id)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado o no autorizado'
                ], 404);
            }

            // Revertir cantidad anterior en el tanque
            $tanqueAnterior = DB::table('tanques')
                ->where('id', $despacho->id_tanque)
                ->first();

            if ($tanqueAnterior) {
                $cantidadRevertida = $tanqueAnterior->cantidad_actual + $despacho->qty;
                DB::table('tanques')
                    ->where('id', $despacho->id_tanque)
                    ->update(['cantidad_actual' => $cantidadRevertida]);
            }

            // Actualizar despacho
            DB::table('repostaje_vehiculos')
                ->where('id', $id)
                ->update([
                    'id_vehiculo' => $request->id_vehiculo,
                    'id_tanque' => $request->id_tanque,
                    'qty' => $request->qty,
                    'qtya' => $request->qtya ?? 0,
                    'rest' => $request->rest,
                    'fecha' => $request->fecha,
                    'obs' => $request->obs,
                    'pic' => $request->pic,
                    'type' => $request->type ?? 1,
                    'ref' => $request->ref,
                    'placa_ext' => $request->placa_ext,
                    'nombre_ext' => $request->nombre_ext,
                    'origin' => $request->origin,
                    'ticket' => $request->ticket,
                ]);

            // Actualizar cantidad en el nuevo tanque
            $tanqueNuevo = DB::table('tanques')
                ->where('id', $request->id_tanque)
                ->first();

            if ($tanqueNuevo) {
                $nuevaCantidad = $tanqueNuevo->cantidad_actual - $request->qty;
                DB::table('tanques')
                    ->where('id', $request->id_tanque)
                    ->update(['cantidad_actual' => $nuevaCantidad]);
            }

            $despachoActualizado = $this->getDespachoById($id);

            return response()->json([
                'success' => true,
                'message' => 'Despacho actualizado exitosamente',
                'data' => $despachoActualizado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar despacho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un despacho
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $despacho = DB::table('repostaje_vehiculos')
                ->where('id', $id)
                ->where('id_us', $user->id)
                ->first();

            if (!$despacho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Despacho no encontrado'
                ], 404);
            }

            // Revertir cantidad en el tanque
            $tanque = DB::table('tanques')
                ->where('id', $despacho->id_tanque)
                ->first();

            if ($tanque) {
                $cantidadRevertida = $tanque->cantidad_actual + $despacho->qty;
                DB::table('tanques')
                    ->where('id', $despacho->id_tanque)
                    ->update(['cantidad_actual' => $cantidadRevertida]);
            }

            DB::table('repostaje_vehiculos')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Despacho eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar despacho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de despachos
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Total de despachos
            $totalDespachos = DB::table('repostaje_vehiculos')
                ->where('id_us', $user->id)
                ->count();

            // Total de combustible despachado
            $totalCombustible = DB::table('repostaje_vehiculos')
                ->where('id_us', $user->id)
                ->sum('qty');

            // Despachos del mes actual
            $despachosMes = DB::table('repostaje_vehiculos')
                ->where('id_us', $user->id)
                ->whereMonth('fecha', now()->month)
                ->whereYear('fecha', now()->year)
                ->count();

            // Combustible del mes actual
            $combustibleMes = DB::table('repostaje_vehiculos')
                ->where('id_us', $user->id)
                ->whereMonth('fecha', now()->month)
                ->whereYear('fecha', now()->year)
                ->sum('qty');

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas obtenidas exitosamente',
                'data' => [
                    'total_despachos' => $totalDespachos,
                    'total_combustible' => $totalCombustible,
                    'despachos_mes' => $despachosMes,
                    'combustible_mes' => $combustibleMes,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper para obtener despacho por ID
     */
    private function getDespachoById($id)
    {
        return DB::table('repostaje_vehiculos as rv')
            ->leftJoin('vehiculos as v', 'rv.id_vehiculo', '=', 'v.id')
            ->leftJoin('tanques as t', 'rv.id_tanque', '=', 't.id')
            ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
            ->select([
                'rv.id',
                'rv.id_vehiculo',
                'rv.id_tanque',
                'rv.qty',
                'rv.qtya',
                'rv.rest',
                'rv.fecha',
                'rv.obs',
                'rv.id_us',
                'rv.pic',
                'rv.type',
                'rv.ref',
                'rv.placa_ext',
                'rv.nombre_ext',
                'rv.origin',
                'rv.id_admin',
                'rv.ticket',
                'v.placa as vehiculo_placa',
                'v.flota as vehiculo_flota',
                'm.nombre as vehiculo_marca',
                't.serial as tanque_serial',
                't.producto as tanque_producto'
            ])
            ->where('rv.id', $id)
            ->first();
    }
}
