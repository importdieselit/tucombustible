<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Tanque;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TanqueController extends Controller
{
    /**
     * Obtener todos los tanques del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $tanques = Tanque::porUsuario($user->id)
                            ->activos()
                            ->with('usuario:id,name,email')
                            ->orderBy('created_at', 'desc')
                            ->get();

            // Agregar indicadores a cada tanque
            $tanquesConIndicadores = $tanques->map(function ($tanque) {
                return $tanque->getDatosCompletosAttribute();
            });

            return response()->json([
                'success' => true,
                'message' => 'Tanques obtenidos exitosamente',
                'data' => $tanquesConIndicadores,
                'total' => $tanquesConIndicadores->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tanques: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un tanque específico por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $tanque = Tanque::porUsuario($user->id)
                           ->with('usuario:id,name,email')
                           ->find($id);

            if (!$tanque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanque no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tanque obtenido exitosamente',
                'data' => $tanque->getDatosCompletosAttribute(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tanque: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo tanque
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'serial' => 'required|string|max:255|unique:tanques,serial',
                'capacidad' => 'required|numeric|min:0',
                'producto' => 'required|string|max:255',
                'ubicacion' => 'nullable|string|max:255',
            ], [
                'serial.required' => 'El número de serie es obligatorio',
                'serial.unique' => 'El número de serie ya existe',
                'capacidad.required' => 'La capacidad es obligatoria',
                'capacidad.numeric' => 'La capacidad debe ser un número',
                'capacidad.min' => 'La capacidad debe ser mayor a 0',
                'producto.required' => 'El producto es obligatorio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tanque = Tanque::create([
                'id_us' => $user->id,
                'serial' => $request->serial,
                'capacidad' => $request->capacidad,
                'producto' => $request->producto,
                'ubicacion' => $request->ubicacion,
            ]);

            $tanque->load('usuario:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Tanque creado exitosamente',
                'data' => $tanque,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tanque: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un tanque existente
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $tanque = Tanque::porUsuario($user->id)->find($id);

            if (!$tanque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanque no encontrado',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'serial' => 'sometimes|required|string|max:255|unique:tanques,serial,' . $id,
                'capacidad' => 'sometimes|required|numeric|min:0',
                'producto' => 'sometimes|required|string|max:255',
                'ubicacion' => 'nullable|string|max:255',
            ], [
                'serial.required' => 'El número de serie es obligatorio',
                'serial.unique' => 'El número de serie ya existe',
                'capacidad.required' => 'La capacidad es obligatoria',
                'capacidad.numeric' => 'La capacidad debe ser un número',
                'capacidad.min' => 'La capacidad debe ser mayor a 0',
                'producto.required' => 'El producto es obligatorio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tanque->update($request->only(['serial', 'capacidad', 'producto', 'ubicacion']));
            $tanque->load('usuario:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Tanque actualizado exitosamente',
                'data' => $tanque,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tanque: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un tanque
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $tanque = Tanque::porUsuario($user->id)->find($id);

            if (!$tanque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanque no encontrado',
                ], 404);
            }

            $tanque->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tanque eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tanque: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener tanques por producto
     */
    public function porProducto(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'producto' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tanques = Tanque::porUsuario($user->id)
                            ->porProducto($request->producto)
                            ->activos()
                            ->with('usuario:id,name,email')
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Tanques obtenidos exitosamente',
                'data' => $tanques,
                'total' => $tanques->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tanques: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener productos únicos del usuario
     */
    public function productos(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $productos = Tanque::porUsuario($user->id)
                              ->activos()
                              ->distinct()
                              ->pluck('producto')
                              ->filter()
                              ->values();

            return response()->json([
                'success' => true,
                'message' => 'Productos obtenidos exitosamente',
                'data' => $productos,
                'total' => $productos->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los productos: ' . $e->getMessage(),
            ], 500);
        }
    }
} 