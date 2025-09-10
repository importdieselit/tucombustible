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

   
}
