<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
use App\Models\User;
use App\Models\MovimientoCombustible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MecanicoController extends Controller
{
    /**
     * Obtener estadísticas del mecánico
     */
    public function getEstadisticas(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario tenga id_cliente
            if (!$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asignado'
                ], 403);
            }

            // Obtener fecha actual
            $hoy = now()->format('Y-m-d');

            // Estadísticas del día filtradas por cliente
            $estadisticas = [
                'egresos_hoy' => MovimientoCombustible::where('tipo_movimiento', 'salida')
                    ->where('cliente_id', $user->id_cliente)
                    ->whereDate('created_at', $hoy)
                    ->count(),
                'ingresos_hoy' => MovimientoCombustible::where('tipo_movimiento', 'entrada')
                    ->where('cliente_id', $user->id_cliente)
                    ->whereDate('created_at', $hoy)
                    ->count(),
                'check_ins_hoy' => 0, // TODO: Implementar cuando se creen las tablas de check in/out
                'check_outs_hoy' => 0, // TODO: Implementar cuando se creen las tablas de check in/out
                'total_depositos' => Deposito::count(), // Mostrar todos los depósitos por ahora
                'depositos_bajos' => Deposito::where('nivel_actual_litros', '<=', DB::raw('nivel_alerta_litros'))
                    ->count(),
                'total_vehiculos' => DB::table('vehiculos')->where('estatus', 1)->count(),
                'vehiculos_disponibles' => DB::table('vehiculos')->where('estatus', 1)->count(),
            ];

            \Log::info("Estadísticas del mecánico obtenidas para usuario {$user->id} - Cliente: {$user->id_cliente}");

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
     * Obtener depósitos disponibles
     */
    public function getDepositos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario tenga id_cliente
            if (!$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asignado'
                ], 403);
            }

            // Obtener depósitos filtrados por cliente
            $depositos = Deposito::select([
                'depositos.id',
                'depositos.serial',
                'depositos.capacidad_litros',
                'depositos.nivel_actual_litros',
                'depositos.nivel_alerta_litros',
                'depositos.producto',
                'depositos.created_at',
                'depositos.updated_at'
            ])
            ->join('movimientos_combustible', 'depositos.id', '=', 'movimientos_combustible.deposito_id')
            ->where('movimientos_combustible.cliente_id', $user->id_cliente)
            ->distinct()
            ->get();

            \Log::info("Depósitos obtenidos para mecánico: " . $depositos->count() . " - Cliente: {$user->id_cliente}");

            return response()->json([
                'success' => true,
                'data' => $depositos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener depósitos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener vehículos disponibles
     */
    public function getVehiculos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Obtener vehículos de la tabla real con joins a marcas y modelos
            $vehiculos = DB::table('vehiculos as v')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->leftJoin('modelos as mo', 'v.modelo', '=', 'mo.id')
                ->select([
                    'v.id',
                    'v.id_usuario',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.marca as marca_nombre',
                    'mo.modelo as modelo',
                    'v.placa',
                    'v.tipo',
                    'v.tipo_diagrama',
                    'v.serial_motor',
                    'v.serial_carroceria',
                    'v.transmision',
                    DB::raw('NULL as HP'), // Campo temporal
                    DB::raw('NULL as CC'), // Campo temporal
                    DB::raw('NULL as altura'), // Campo temporal
                    DB::raw('NULL as ancho'), // Campo temporal
                    DB::raw('NULL as largo'), // Campo temporal
                    DB::raw('NULL as consumo_promedio'), // Campo temporal
                    'v.created_at',
                    'v.updated_at'
                ])
                ->where('v.estatus', 1) // Solo vehículos activos
                ->get();

            \Log::info("Vehículos obtenidos para mecánico: " . $vehiculos->count() . " - Usuario: {$user->id}");

            return response()->json([
                'success' => true,
                'data' => $vehiculos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener pedidos aprobados para recarga
     */
    public function getPedidos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario tenga id_cliente
            if (!$user->id_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asignado'
                ], 403);
            }

            // Obtener pedidos aprobados del cliente
            $pedidos = DB::table('pedidos')
                ->where('cliente_id', $user->id_cliente)
                ->where('estado', 'aprobado')
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info("Pedidos obtenidos para mecánico {$user->id} - Cliente: {$user->id_cliente} - Total: {$pedidos->count()}");

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
     * Obtener proveedores
     */
    public function getProveedores(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Obtener todos los proveedores
            $proveedores = DB::table('proveedores')
                ->select(['id', 'nombre', 'rif', 'telefono', 'email'])
                ->orderBy('nombre')
                ->get();

            \Log::info("Proveedores obtenidos para mecánico - Usuario: {$user->id}");

            return response()->json([
                'success' => true,
                'data' => $proveedores
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar egreso/despacho de combustible
     */
    public function realizarEgresoDespacho(Request $request): JsonResponse
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
                'deposito_id' => 'required|exists:depositos,id',
                'cantidad' => 'required|numeric|min:0.01',
                'vehiculo_id' => 'required|string',
                'observaciones' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el depósito pertenece al cliente del usuario
            $deposito = Deposito::whereHas('movimientosCombustible', function($query) use ($user) {
                $query->where('cliente_id', $user->id_cliente);
            })->find($request->deposito_id);

            if (!$deposito) {
                return response()->json([
                    'success' => false,
                    'message' => 'Depósito no encontrado o no pertenece a su cliente'
                ], 404);
            }

            if ($deposito->nivel_actual_litros < $request->cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay suficiente combustible en el depósito'
                ], 422);
            }

            // Registrar el movimiento en la tabla movimientos_combustible
            $movimiento = MovimientoCombustible::create([
                'tipo_movimiento' => 'salida',
                'deposito_id' => $request->deposito_id,
                'cliente_id' => $user->id_cliente,
                'cantidad_litros' => $request->cantidad,
                'observaciones' => "Despacho a vehículo: {$request->vehiculo_id}. " . ($request->observaciones ?? ''),
            ]);

            // Actualizar el nivel del depósito
            $deposito->update([
                'nivel_actual_litros' => $deposito->nivel_actual_litros - $request->cantidad
            ]);

            $resultado = [
                'id' => $movimiento->id,
                'deposito_id' => $request->deposito_id,
                'cantidad' => $request->cantidad,
                'vehiculo_id' => $request->vehiculo_id,
                'observaciones' => $request->observaciones,
                'fecha' => $movimiento->created_at,
                'mecanico_id' => $user->id,
                'movimiento_id' => $movimiento->id,
            ];

            \Log::info("Egreso/despacho realizado por mecánico {$user->id} - Cliente: {$user->id_cliente}");

            return response()->json([
                'success' => true,
                'message' => 'Egreso/despacho realizado exitosamente',
                'data' => $resultado
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar egreso/despacho: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar ingreso/recarga de combustible
     */
    public function realizarIngresoRecarga(Request $request): JsonResponse
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
                'deposito_id' => 'required|exists:depositos,id',
                'cantidad' => 'required|numeric|min:0.01',
                'proveedor_id' => 'required|exists:proveedores,id',
                'observaciones' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el depósito pertenece al cliente del usuario
            $deposito = Deposito::whereHas('movimientosCombustible', function($query) use ($user) {
                $query->where('cliente_id', $user->id_cliente);
            })->find($request->deposito_id);

            if (!$deposito) {
                return response()->json([
                    'success' => false,
                    'message' => 'Depósito no encontrado o no pertenece a su cliente'
                ], 404);
            }

            $nuevoNivel = $deposito->nivel_actual_litros + $request->cantidad;
            
            if ($nuevoNivel > $deposito->capacidad_litros) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad excede la capacidad del depósito'
                ], 422);
            }

            // Obtener información del proveedor
            $proveedor = DB::table('proveedores')->where('id', $request->proveedor_id)->first();
            
            // Registrar el movimiento en la tabla movimientos_combustible
            $movimiento = MovimientoCombustible::create([
                'tipo_movimiento' => 'entrada',
                'deposito_id' => $request->deposito_id,
                'cliente_id' => $user->id_cliente,
                'cantidad_litros' => $request->cantidad,
                'observaciones' => "Recarga desde proveedor: {$proveedor->nombre}. " . ($request->observaciones ?? ''),
            ]);

            // Actualizar el nivel del depósito
            $deposito->update([
                'nivel_actual_litros' => $nuevoNivel
            ]);

            // Actualizar el estado del pedido a completado
            DB::table('pedidos')
                ->where('id', $request->pedido_id)
                ->update([
                    'estado' => 'completado',
                    'fecha_completado' => now(),
                    'updated_at' => now(),
                ]);

            $resultado = [
                'id' => $movimiento->id,
                'deposito_id' => $request->deposito_id,
                'cantidad' => $request->cantidad,
                'proveedor_id' => $request->proveedor_id,
                'proveedor_nombre' => $proveedor->nombre,
                'observaciones' => $request->observaciones,
                'fecha' => $movimiento->created_at,
                'mecanico_id' => $user->id,
                'movimiento_id' => $movimiento->id,
            ];

            \Log::info("Ingreso/recarga realizado por mecánico {$user->id} - Cliente: {$user->id_cliente}");

            return response()->json([
                'success' => true,
                'message' => 'Ingreso/recarga realizado exitosamente',
                'data' => $resultado
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar ingreso/recarga: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar check in/out de vehículo
     */
    public function realizarCheckInOut(Request $request): JsonResponse
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
                'vehiculo_id' => 'required|string',
                'tipo' => 'required|in:check_in,check_out',
                'observaciones' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // TODO: Implementar la lógica de check in/out
            // Por ahora solo retornamos un resultado de ejemplo
            $resultado = [
                'id' => uniqid(),
                'vehiculo_id' => $request->vehiculo_id,
                'tipo' => $request->tipo,
                'observaciones' => $request->observaciones,
                'fecha' => now(),
                'mecanico_id' => $user->id,
            ];

            \Log::info("Check {$request->tipo} realizado por mecánico {$user->id} - Cliente: {$user->id_cliente}");

            return response()->json([
                'success' => true,
                'message' => "Check {$request->tipo} realizado exitosamente",
                'data' => $resultado
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar check in/out: ' . $e->getMessage()
            ], 500);
        }
    }
}
