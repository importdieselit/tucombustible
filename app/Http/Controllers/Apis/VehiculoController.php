<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VehiculoController extends Controller
{
    /**
     * Obtener todos los vehículos del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $vehiculos = DB::table('vehiculos as v')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->select([
                    'v.id',
                    'v.id_usuario',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.nombre as marca_nombre',
                    'v.modelo',
                    'v.placa',
                    'v.tipo',
                    'v.tipo_diagrama',
                    'v.serial_motor',
                    'v.serial_carroceria',
                    'v.transmision',
                    'v.HP',
                    'v.CC',
                    'v.altura',
                    'v.ancho',
                    'v.largo',
                    'v.consumo_promedio',
                    'v.created_at',
                    'v.updated_at'
                ])
                ->where('v.estatus', 1) // Solo vehículos activos
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Vehículos obtenidos exitosamente',
                'data' => $vehiculos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un vehículo específico
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $vehiculo = DB::table('vehiculos as v')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->select([
                    'v.id',
                    'v.id_usuario',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.nombre as marca_nombre',
                    'v.modelo',
                    'v.placa',
                    'v.tipo',
                    'v.tipo_diagrama',
                    'v.serial_motor',
                    'v.serial_carroceria',
                    'v.transmision',
                    'v.HP',
                    'v.CC',
                    'v.altura',
                    'v.ancho',
                    'v.largo',
                    'v.consumo_promedio',
                    'v.created_at',
                    'v.updated_at'
                ])
                ->where('v.id', $id)
                ->where('v.id_usuario', $user->id)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vehículo obtenido exitosamente',
                'data' => $vehiculo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo vehículo
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'flota' => 'nullable|string|max:100',
            'marca' => 'nullable|integer|exists:marcas,id',
            'modelo' => 'nullable|integer',
            'placa' => 'required|string|max:50|unique:vehiculos',
            'tipo' => 'nullable|string|max:10',
            'tipo_diagrama' => 'nullable|string|max:40',
            'serial_motor' => 'nullable|string|max:100',
            'serial_carroceria' => 'nullable|string|max:100',
            'transmision' => 'nullable|string|max:20',
            'HP' => 'nullable|integer',
            'CC' => 'nullable|integer',
            'altura' => 'nullable|numeric',
            'ancho' => 'nullable|numeric',
            'largo' => 'nullable|numeric',
            'consumo_promedio' => 'nullable|numeric',
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
            
            $vehiculoId = DB::table('vehiculos')->insertGetId([
                'id_usuario' => $user->id,
                'estatus' => 1, // Activo
                'flota' => $request->flota,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'placa' => $request->placa,
                'tipo' => $request->tipo,
                'tipo_diagrama' => $request->tipo_diagrama,
                'serial_motor' => $request->serial_motor,
                'serial_carroceria' => $request->serial_carroceria,
                'transmision' => $request->transmision,
                'HP' => $request->HP,
                'CC' => $request->CC,
                'altura' => $request->altura,
                'ancho' => $request->ancho,
                'largo' => $request->largo,
                'consumo_promedio' => $request->consumo_promedio,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $vehiculo = $this->getVehiculoById($vehiculoId);

            return response()->json([
                'success' => true,
                'message' => 'Vehículo creado exitosamente',
                'data' => $vehiculo
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear vehículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un vehículo
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'flota' => 'nullable|string|max:100',
            'marca' => 'nullable|integer|exists:marcas,id',
            'modelo' => 'nullable|integer',
            'placa' => 'required|string|max:50|unique:vehiculos,placa,' . $id,
            'tipo' => 'nullable|string|max:10',
            'tipo_diagrama' => 'nullable|string|max:40',
            'serial_motor' => 'nullable|string|max:100',
            'serial_carroceria' => 'nullable|string|max:100',
            'transmision' => 'nullable|string|max:20',
            'HP' => 'nullable|integer',
            'CC' => 'nullable|integer',
            'altura' => 'nullable|numeric',
            'ancho' => 'nullable|numeric',
            'largo' => 'nullable|numeric',
            'consumo_promedio' => 'nullable|numeric',
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
            
            $vehiculo = DB::table('vehiculos')
                ->where('id', $id)
                ->where('id_usuario', $user->id)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            DB::table('vehiculos')
                ->where('id', $id)
                ->update([
                    'flota' => $request->flota,
                    'marca' => $request->marca,
                    'modelo' => $request->modelo,
                    'placa' => $request->placa,
                    'tipo' => $request->tipo,
                    'tipo_diagrama' => $request->tipo_diagrama,
                    'serial_motor' => $request->serial_motor,
                    'serial_carroceria' => $request->serial_carroceria,
                    'transmision' => $request->transmision,
                    'HP' => $request->HP,
                    'CC' => $request->CC,
                    'altura' => $request->altura,
                    'ancho' => $request->ancho,
                    'largo' => $request->largo,
                    'consumo_promedio' => $request->consumo_promedio,
                    'updated_at' => now(),
                ]);

            $vehiculoActualizado = $this->getVehiculoById($id);

            return response()->json([
                'success' => true,
                'message' => 'Vehículo actualizado exitosamente',
                'data' => $vehiculoActualizado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar vehículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un vehículo
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $vehiculo = DB::table('vehiculos')
                ->where('id', $id)
                ->where('id_usuario', $user->id)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            DB::table('vehiculos')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehículo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar vehículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener marcas de vehículos
     */
    public function marcas(): JsonResponse
    {
        try {
            $marcas = DB::table('marcas')
                ->select(['id', 'nombre'])
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Marcas obtenidas exitosamente',
                'data' => $marcas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marcas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper para obtener vehículo por ID
     */
    private function getVehiculoById($id)
    {
        return DB::table('vehiculos as v')
            ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
            ->select([
                'v.id',
                'v.id_usuario',
                'v.estatus',
                'v.flota',
                'v.marca',
                'm.nombre as marca_nombre',
                'v.modelo',
                'v.placa',
                'v.tipo',
                'v.tipo_diagrama',
                'v.serial_motor',
                'v.serial_carroceria',
                'v.transmision',
                'v.HP',
                'v.CC',
                'v.altura',
                'v.ancho',
                'v.largo',
                'v.consumo_promedio',
                'v.created_at',
                'v.updated_at'
            ])
            ->where('v.id', $id)
            ->first();
    }
}
