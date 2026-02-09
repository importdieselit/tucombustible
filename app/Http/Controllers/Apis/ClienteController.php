<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        
        $clientes = Cliente::all();

        return response()->json([
            'success' => true,
            'data' => $clientes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'contacto' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:50|unique:clientes',
            'rif' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'disponible' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cliente = Cliente::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => $cliente
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $cliente
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'contacto' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:50|unique:clientes,email,' . $cliente->id,
            'rif' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'disponible' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cliente->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente',
                'data' => $cliente
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        try {
            $cliente->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the authenticated user's cliente information.
     */
    public function info(Request $request): JsonResponse
    {
        try {
            // Obtener el usuario autenticado
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario tenga un cliente asociado
            if (!$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 404);
            }

            // Obtener el cliente asociado al usuario usando la relación
            $cliente = $user->cliente;
            
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

        

            return response()->json([
                'success' => true,
                'data' => $cliente
            ]);

        } catch (\Exception $e) {
            \Log::error('Error obteniendo información del cliente', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the authenticated user's cliente data.
     */
    public function misDatos(Request $request): JsonResponse
    {
        try {
            // Obtener el usuario autenticado
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario tenga un cliente asociado
            if (!$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 404);
            }

            // Obtener el cliente asociado al usuario usando la relación
            $cliente = $user->cliente;
            
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Datos del cliente obtenidos exitosamente',
                'data' => $cliente
            ]);

        } catch (\Exception $e) {
            \Log::error('Error obteniendo datos del cliente', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the disponible amount for the authenticated user's cliente.
     */
    public function updateDisponible(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'disponible' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el usuario autenticado
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Obtener el cliente asociado al usuario usando la relación
            $cliente = $user->cliente;
            
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Actualizar el saldo disponible
            $cliente->disponible = $request->disponible;
            $cliente->save();

            return response()->json([
                'success' => true,
                'message' => 'Saldo disponible actualizado exitosamente',
                'data' => $cliente
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar saldo disponible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener clientes que tienen vehículos registrados
     */
    public function getClientesConVehiculos(): JsonResponse
    {   
        try {
            
            // Consulta directa con DB para debug
            $clientes = \DB::table('clientes as c')
                ->join('vehiculos as v', 'c.id', '=', 'v.id_cliente')
                ->select([
                    'c.id',
                    'c.nombre',
                    'c.contacto',
                    'c.telefono',
                    'c.email',
                    'c.rif',
                    'c.direccion',
                    'c.disponible',
                    'c.parent',
                    'c.created_at',
                    'c.updated_at'
                ])
                ->distinct()
                ->orderBy('c.nombre')
                ->get();


            return response()->json([
                'success' => true,
                'message' => 'Clientes con vehículos obtenidos exitosamente',
                'data' => $clientes
            ]);

        } catch (\Exception $e) {
            \Log::error('getClientesConVehiculos: Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes con vehículos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método de prueba para verificar que el endpoint funciona
     */
    public function testClientesConVehiculos(): JsonResponse
    {
        
        return response()->json([
            'success' => true,
            'message' => 'Método de prueba funcionando',
            'data' => [
                [
                    'id' => 1,
                    'nombre' => 'Prueba',
                    'contacto' => 'Test',
                    'telefono' => '123456789',
                    'email' => 'test@test.com',
                    'rif' => 'V-12345678-9',
                    'direccion' => 'Dirección de prueba',
                    'disponible' => 1000.00,
                    'parent' => null,
                    'created_at' => '2025-01-01 00:00:00',
                    'updated_at' => '2025-01-01 00:00:00'
                ]
            ]
        ]);
    }

    /**
     * Método de prueba simple sin usar modelos
     */
    public function testSimple(): JsonResponse
    {
        
        return response()->json([
            'success' => true,
            'message' => 'Método simple funcionando',
            'data' => [
                'test' => 'funcionando'
            ]
        ]);
    }

    /**
     * Get clientes para el admin (para filtros)
     */
    public function getClientesAdmin(): JsonResponse
    {
        try {
            $clientes = Cliente::select(['id', 'nombre', 'contacto', 'telefono', 'email'])
                ->orderBy('nombre', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $clientes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener las sucursales de un cliente padre
     */
    public function getSucursales(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Verificar que el usuario tenga un cliente asociado
            if (!$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 400);
            }

            // Obtener el cliente del usuario
            $cliente = Cliente::find($user->cliente_id);
            
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar que sea un cliente padre (parent = 0)
            if ($cliente->parent != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los clientes principales pueden ver sus sucursales'
                ], 403);
            }

            // Obtener todas las sucursales del cliente padre
            $sucursales = Cliente::where('parent', $cliente->id)
                ->select([
                    'id',
                    'nombre',
                    'contacto',
                    'dni',
                    'telefono',
                    'email',
                    'rif',
                    'direccion',
                    'disponible',
                    'cupo',
                    'ciiu',
                    'parent',
                    'sector',
                    'periodo',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('nombre', 'asc')
                ->get();

            // Agregar información adicional a cada sucursal
            $sucursales->transform(function ($sucursal) {
                $sucursal->porcentaje_disponible = $sucursal->cupo > 0 
                    ? round(($sucursal->disponible / $sucursal->cupo) * 100, 2) 
                    : 0;
                
                $sucursal->estado_disponible = $this->getEstadoDisponible($sucursal->porcentaje_disponible);
                
                return $sucursal;
            });

            return response()->json([
                'success' => true,
                'data' => $sucursales,
                'message' => 'Sucursales obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sucursales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el detalle de una sucursal específica
     */
    public function getSucursalDetail(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Verificar que el usuario tenga un cliente asociado
            if (!$user->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 400);
            }

            // Obtener el cliente del usuario
            $cliente = Cliente::find($user->cliente_id);
            
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar que sea un cliente padre (parent = 0)
            if ($cliente->parent != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los clientes principales pueden ver sus sucursales'
                ], 403);
            }

            // Buscar la sucursal específica
            $sucursal = Cliente::where('id', $id)
                ->where('parent', $cliente->id)
                ->select([
                    'id',
                    'nombre',
                    'contacto',
                    'dni',
                    'telefono',
                    'email',
                    'rif',
                    'direccion',
                    'disponible',
                    'cupo',
                    'ciiu',
                    'parent',
                    'sector',
                    'periodo',
                    'created_at',
                    'updated_at'
                ])
                ->first();

            if (!$sucursal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sucursal no encontrada o no pertenece a este cliente'
                ], 404);
            }

            // Agregar información adicional
            $sucursal->porcentaje_disponible = $sucursal->cupo > 0 
                ? round(($sucursal->disponible / $sucursal->cupo) * 100, 2) 
                : 0;
            
            $sucursal->estado_disponible = $this->getEstadoDisponible($sucursal->porcentaje_disponible);

            return response()->json([
                'success' => true,
                'data' => $sucursal,
                'message' => 'Detalle de sucursal obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle de sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determinar el estado del combustible basado en el porcentaje disponible
     */
    private function getEstadoDisponible($porcentaje): string
    {
        if ($porcentaje <= 10) {
            return 'bajo';
        } elseif ($porcentaje <= 30) {
            return 'medio';
        } else {
            return 'alto';
        }
    }

   
}
