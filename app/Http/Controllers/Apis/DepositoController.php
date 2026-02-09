<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Producto;

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
     * Solo permite editar el nombre (serial) y la cantidad disponible actual.
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Debug: Log de datos recibidos

        try {
            // Buscar el depósito por ID
            $deposito = Deposito::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'serial' => 'sometimes|string|max:50|unique:depositos,serial,' . $deposito->id,
                'nivel_actual_litros' => 'sometimes|integer|min:0|max:' . $deposito->capacidad_litros,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Solo actualizar los campos permitidos
            $updateData = [];
            
            if ($request->has('serial')) {
                $updateData['serial'] = $request->serial;
            }
            
            if ($request->has('nivel_actual_litros')) {
                $updateData['nivel_actual_litros'] = $request->nivel_actual_litros;
            }


            // Verificar que hay datos para actualizar
            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos para actualizar'
                ], 400);
            }

            // Actualizar el depósito
            $resultado = $deposito->update($updateData);
            

            // Verificar que se actualizó correctamente
            $depositoActualizado = Deposito::find($id);


            return response()->json([
                'success' => true,
                'message' => 'Depósito actualizado exitosamente',
                'data' => $depositoActualizado
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Depósito no encontrado'
            ], 404);
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
            
            // Obtener todos los depósitos disponibles
            $depositos = Deposito::all();
            
            $debugInfo['depositos_count'] = $depositos->count();
            $debugInfo['message'] = 'Mostrando todos los depósitos disponibles';

            return response()->json([
                'success' => true,
                'data' => $depositos,
                'message' => 'Todos los depósitos disponibles',
                'debug' => $debugInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener depósitos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depositos statistics for all available depositos.
     */
    public function getMisEstadisticas(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Obtener todos los depósitos
            $depositos = Deposito::all();
            
            $totalDepositos = $depositos->count();
            $depositosEnAlerta = $depositos->filter(function($deposito) {
                return $deposito->nivel_actual_litros <= $deposito->nivel_alerta_litros;
            })->count();
            $depositosVacios = $depositos->where('nivel_actual_litros', 0)->count();
            $totalCapacidad = $depositos->sum('capacidad_litros');
            $totalActual = $depositos->sum('nivel_actual_litros');
            $porcentajePromedio = $totalCapacidad > 0 ? ($totalActual / $totalCapacidad) * 100 : 0;

            // Estadísticas adicionales para el dashboard
            $estadisticas = [
                'totalDepositos' => $totalDepositos,
                'totalCapacidad' => $totalCapacidad,
                'totalDisponible' => $totalActual,
                'despachosHoy' => 0, // Por ahora en 0, se puede implementar después
                'recargasHoy' => 0,  // Por ahora en 0, se puede implementar después
                'vehiculosActivos' => 0, // Por ahora en 0, se puede implementar después
                'totalClientes' => 0, // Por ahora en 0, se puede implementar después
                'despachosMes' => 0,  // Por ahora en 0, se puede implementar después
                'recargasMes' => 0,   // Por ahora en 0, se puede implementar después
                'en_alerta' => $depositosEnAlerta,
                'vacios' => $depositosVacios,
                'porcentaje_promedio' => round($porcentajePromedio, 2),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de todos los depósitos'
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
      * Get all depositos for admin (no restrictions).
      */
     public function getAllDepositos(Request $request): JsonResponse
     {
         try {
             $user = $request->user();
             
             // Debug: Verificar información del usuario
             $debugInfo = [
                 'user_id' => $user->id,
                 'perfil_id' => $user->perfil_id ?? 'N/A',
                 'user_name' => $user->name
             ];
             
             // Obtener todos los depósitos sin restricciones
             $depositos = Deposito::all();
             
             $debugInfo['depositos_count'] = $depositos->count();
             $debugInfo['message'] = 'Mostrando todos los depósitos para administrador';
             
             // Debug: Verificar estructura de los depósitos
             if ($depositos->count() > 0) {
                 $debugInfo['sample_deposito'] = $depositos->first()->toArray();
             }

             return response()->json([
                 'success' => true,
                 'data' => $depositos,
                 'message' => 'Todos los depósitos disponibles para administrador',
                 'debug' => $debugInfo
             ]);

         } catch (\Exception $e) {
             return response()->json([
                 'success' => false,
                 'message' => 'Error al obtener depósitos',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 }
