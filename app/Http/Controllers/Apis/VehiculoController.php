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
                    'v.id_cliente',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.marca as marca_nombre',
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
                    'v.consumo',
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
                    'v.id_cliente',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.marca as marca_nombre',
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
                    'v.consumo',
                    'v.created_at',
                    'v.updated_at'
                ])
                ->where('v.id', $id)
                ->where('v.id_cliente', $user->id_cliente)
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
                'id_cliente' => $user->id_cliente,
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
                ->where('id_cliente', $user->id_cliente)
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
                ->where('id_cliente', $user->id_cliente)
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
                ->select(['id', 'marca'])
                ->orderBy('marca', 'asc')
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
                'v.id_cliente',
                'v.estatus',
                'v.flota',
                'v.marca',
                'm.marca as marca_nombre',
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

    /**
     * Obtener vehículos por cliente (para admin/super admin)
     */
    public function getByCliente(Request $request, $idCliente): JsonResponse
    {
        try {
            $vehiculos = DB::table('vehiculos as v')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id')
                ->select([
                    'v.id',
                    'v.id_cliente',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.marca as marca_nombre',
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
                    'v.consumo',
                    'v.created_at',
                    'v.updated_at',
                    'c.nombre as cliente_nombre'
                ])
                ->where('v.id_cliente', $idCliente)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Vehículos del cliente obtenidos exitosamente',
                'data' => $vehiculos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículos del cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar vehículo por placa (para admin/super admin)
     */
    public function getByPlaca(Request $request, $placa): JsonResponse
    {
        try {
            // Buscar vehículo sin filtrar por estatus
            $vehiculo = DB::table('vehiculos')
                ->select([
                    'id',
                    'id_cliente',
                    'estatus',
                    'flota',
                    'marca',
                    'modelo',
                    'placa',
                    'tipo',
                    'tipo_diagrama',
                    'serial_motor',
                    'serial_carroceria',
                    'transmision',
                    'HP',
                    'CC',
                    'altura',
                    'ancho',
                    'largo',
                    'consumo',
                    'created_at',
                    'updated_at'
                ])
                ->where('placa', $placa)
                ->first();

            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado con la placa: ' . $placa
                ], 404);
            }

            // Agregar información de marca y cliente por separado
            $marca = null;
            if ($vehiculo->marca) {
                $marca = DB::table('marcas')->where('id', $vehiculo->marca)->first();
            }

            $cliente = null;
            if ($vehiculo->id_cliente) {
                $cliente = DB::table('clientes')->where('id', $vehiculo->id_cliente)->first();
            }

            // Agregar campos adicionales
            $vehiculo->marca_nombre = $marca ? $marca->marca : null;
            $vehiculo->cliente_nombre = $cliente ? $cliente->nombre : null;

            return response()->json([
                'success' => true,
                'message' => 'Vehículo encontrado exitosamente',
                'data' => $vehiculo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar vehículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los vehículos (para admin/super admin)
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $vehiculos = DB::table('vehiculos as v')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id')
                ->select([
                    'v.id',
                    'v.id_cliente',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.marca as marca_nombre',
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
                    'v.consumo',
                    'v.created_at',
                    'v.updated_at',
                    'c.nombre as cliente_nombre'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Todos los vehículos obtenidos exitosamente',
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
     * Obtener vehículos del cliente autenticado
     */
    public function getMisVehiculos(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $clienteId = $user->cliente_id;

            if (!$clienteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 403);
            }

            $vehiculos = DB::table('vehiculos as v')
                ->leftJoin('marcas as m', 'v.marca', '=', 'm.id')
                ->select([
                    'v.id',
                    'v.id_cliente',
                    'v.estatus',
                    'v.flota',
                    'v.marca',
                    'm.marca as marca_nombre',
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
                    'v.consumo',
                    'v.created_at',
                    'v.updated_at'
                ])
                ->where('v.id_cliente', $clienteId)
                ->where('v.estatus', 1) // Solo vehículos activos
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Vehículos del cliente obtenidos exitosamente',
                'data' => $vehiculos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículos del cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vehículos para el admin (para filtros) - TODOS los vehículos del sistema
     */
    public function getVehiculosAdmin(): JsonResponse
    {
        try {
            // Primero obtener todos los vehículos sin join para asegurar que funcione
            $vehiculos = DB::table('vehiculos')
                ->select([
                    'id',
                    'placa',
                    'marca',
                    'modelo',
                    'id_cliente',
                    'marca as marca_nombre' // Usar el campo marca directamente como marca_nombre
                ])
                ->orderBy('placa', 'asc')
                ->get();

            // Luego obtener los nombres de clientes por separado
            $clientes = DB::table('clientes')
                ->select(['id', 'nombre'])
                ->get()
                ->keyBy('id');

            // Combinar los datos
            $vehiculosConClientes = $vehiculos->map(function ($vehiculo) use ($clientes) {
                $vehiculo->cliente_nombre = $vehiculo->id_cliente ? 
                    ($clientes[$vehiculo->id_cliente]->nombre ?? 'Cliente no encontrado') : 
                    'Sin cliente asignado';
                return $vehiculo;
            });

            return response()->json([
                'success' => true,
                'data' => $vehiculosConClientes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
