<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DepositoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $depositos = Deposito::all();

        return response()->json([
            'success' => true,
            'data' => $depositos
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'serial' => 'required|string|max:50|unique:depositos',
            'capacidad_litros' => 'required|integer|min:1',
            'nivel_actual_litros' => 'required|integer|min:0',
            'nivel_alerta_litros' => 'required|integer|min:0',
            'producto' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $deposito = Deposito::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Depósito creado exitosamente',
                'data' => $deposito
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear depósito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Deposito $deposito): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $deposito
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deposito $deposito): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'serial' => 'required|string|max:50|unique:depositos,serial,' . $deposito->id,
            'capacidad_litros' => 'required|integer|min:1',
            'nivel_actual_litros' => 'required|integer|min:0',
            'nivel_alerta_litros' => 'required|integer|min:0',
            'producto' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $deposito->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Depósito actualizado exitosamente',
                'data' => $deposito
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar depósito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deposito $deposito): JsonResponse
    {
        try {
            $deposito->delete();

            return response()->json([
                'success' => true,
                'message' => 'Depósito eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar depósito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depositos by cliente.
     */
    public function getByCliente($clienteId): JsonResponse
    {
        try {
            $depositos = Deposito::whereHas('movimientosCombustible', function($query) use ($clienteId) {
                $query->where('cliente_id', $clienteId);
            })->get();

            return response()->json([
                'success' => true,
                'data' => $depositos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener depósitos del cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depositos with low stock (en alerta).
     */
    public function getEnAlerta(): JsonResponse
    {
        try {
            $depositos = Deposito::whereRaw('nivel_actual_litros <= nivel_alerta_litros')->get();

            return response()->json([
                'success' => true,
                'data' => $depositos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener depósitos en alerta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depositos statistics.
     */
    public function getEstadisticas(): JsonResponse
    {
        try {
            $totalDepositos = Deposito::count();
            $depositosEnAlerta = Deposito::whereRaw('nivel_actual_litros <= nivel_alerta_litros')->count();
            $depositosVacios = Deposito::where('nivel_actual_litros', 0)->count();
            $totalCapacidad = Deposito::sum('capacidad_litros');
            $totalActual = Deposito::sum('nivel_actual_litros');
            $porcentajePromedio = $totalCapacidad > 0 ? ($totalActual / $totalCapacidad) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_depositos' => $totalDepositos,
                    'en_alerta' => $depositosEnAlerta,
                    'vacios' => $depositosVacios,
                    'total_capacidad_litros' => $totalCapacidad,
                    'total_actual_litros' => $totalActual,
                    'porcentaje_promedio' => round($porcentajePromedio, 2),
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
     * Get depositos for the authenticated user.
     */
        public function getMisDepositos(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Debug: Verificar información del usuario
            $debugInfo = [
                'user_id' => $user->id,
                'id_cliente' => $user->id_cliente,
                'user_name' => $user->name
            ];
            
            // Si el usuario tiene un cliente asociado, obtener sus depósitos
            if ($user->id_cliente) {
                // Debug: Verificar movimientos del cliente
                $movimientosCount = \App\Models\MovimientoCombustible::where('cliente_id', $user->id_cliente)->count();
                $debugInfo['movimientos_count'] = $movimientosCount;
                
                // Obtener depósitos únicos que tienen movimientos asociados al cliente
                $depositos = Deposito::whereHas('movimientosCombustible', function($query) use ($user) {
                    $query->where('cliente_id', $user->id_cliente);
                })->get();
                
                $debugInfo['depositos_count'] = $depositos->count();
            } else {
                // Si no tiene cliente asociado, devolver array vacío
                $depositos = collect([]);
                $debugInfo['depositos_count'] = 0;
            }

            return response()->json([
                'success' => true,
                'data' => $depositos,
                'message' => $depositos->count() > 0 
                    ? 'Depósitos encontrados' 
                    : 'No tienes depósitos asignados',
                'debug' => $debugInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tus depósitos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depositos statistics for the authenticated user.
     */
    public function getMisEstadisticas(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user->id_cliente) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_depositos' => 0,
                        'en_alerta' => 0,
                        'vacios' => 0,
                        'total_capacidad_litros' => 0,
                        'total_actual_litros' => 0,
                        'porcentaje_promedio' => 0,
                    ],
                    'message' => 'No tienes depósitos asignados'
                ]);
            }

            $depositos = Deposito::whereHas('movimientosCombustible', function($query) use ($user) {
                $query->where('cliente_id', $user->id_cliente);
            });
            
            $totalDepositos = $depositos->count();
            $depositosEnAlerta = $depositos->whereRaw('nivel_actual_litros <= nivel_alerta_litros')->count();
            $depositosVacios = $depositos->where('nivel_actual_litros', 0)->count();
            $totalCapacidad = $depositos->sum('capacidad_litros');
            $totalActual = $depositos->sum('nivel_actual_litros');
            $porcentajePromedio = $totalCapacidad > 0 ? ($totalActual / $totalCapacidad) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_depositos' => $totalDepositos,
                    'en_alerta' => $depositosEnAlerta,
                    'vacios' => $depositosVacios,
                    'total_capacidad_litros' => $totalCapacidad,
                    'total_actual_litros' => $totalActual,
                    'porcentaje_promedio' => round($porcentajePromedio, 2),
                ],
                'message' => 'Estadísticas de tus depósitos'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
