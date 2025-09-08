<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Persona;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Update persona data.
     */
    public function updatePersona(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'persona_id' => 'required|integer|min:0',
            'name' => 'nullable|string|max:255',
            'nombre' => 'nullable|string|max:255',
            'dni' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Si persona_id es 0, crear una nueva persona
            if ($request->persona_id == 0) {
                $persona = Persona::create([
                    'nombre' => $request->nombre ?? $user->name,
                    'dni' => $request->dni,
                    'telefono' => $request->telefono,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                ]);
                
                // Actualizar el usuario con el nuevo id_persona
                $user->update(['id_persona' => $persona->id]);
            } else {
                $persona = Persona::findOrFail($request->persona_id);

                // Verificar que la persona pertenece al usuario autenticado
                if ($user->id_persona !== $persona->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para editar esta persona'
                    ], 403);
                }
            }

            // Actualizar solo los campos que se proporcionaron
            $updateData = [];
            if ($request->has('nombre')) $updateData['nombre'] = $request->nombre;
            if ($request->has('dni')) $updateData['dni'] = $request->dni;
            if ($request->has('telefono')) $updateData['telefono'] = $request->telefono;
            if ($request->has('address')) $updateData['address'] = $request->address;
            if ($request->has('city')) $updateData['city'] = $request->city;
            if ($request->has('state')) $updateData['state'] = $request->state;
            if ($request->has('country')) $updateData['country'] = $request->country;

            $persona->update($updateData);

            // Actualizar el campo 'name' del usuario si se proporciona
            if ($request->has('name') && $request->name !== null) {
                $user->update(['name' => $request->name]);
            }

            // Recargar el usuario con todas las relaciones
            $user = User::with(['persona', 'perfil', 'cliente'])->find($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Datos de persona actualizados exitosamente',
                'data' => [
                    'user' => $user,
                    'persona' => $user->persona,
                    'perfil' => $user->perfil,
                    'cliente' => $user->cliente,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar datos de persona',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Los datos de cliente son solo lectura, no se actualizan

    /**
     * Get available clients for the authenticated user.
     * Returns the user's own client and all child clients (sucursales).
     */
    public function getAvailableClients(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $availableClients = collect();

            // Si el usuario tiene un cliente asociado
            if ($user->cliente_id) {
                \Log::info("Usuario ID: {$user->id}, Cliente ID: {$user->cliente_id}");
                
                // Obtener el cliente principal del usuario
                $mainClient = Cliente::find($user->cliente_id);
                if ($mainClient) {
                    \Log::info("Cliente encontrado: ID={$mainClient->id}, Nombre={$mainClient->nombre}, Parent={$mainClient->parent}");
                    
                    $availableClients->push([
                        'id' => $mainClient->id,
                        'nombre' => $mainClient->nombre,
                        'contacto' => $mainClient->contacto,
                        'telefono' => $mainClient->telefono,
                        'email' => $mainClient->email,
                        'rif' => $mainClient->rif,
                        'direccion' => $mainClient->direccion,
                        'parent' => $mainClient->parent,
                        'tipo' => $mainClient->parent == 0 ? 'principal' : 'sucursal',
                        'disponible' => $mainClient->disponible
                    ]);

                    // Si es cliente principal (parent = 0), obtener todas las sucursales
                    if ($mainClient->parent == 0) {
                        $sucursales = Cliente::where('parent', $mainClient->id)->get();
                        foreach ($sucursales as $sucursal) {
                            $availableClients->push([
                                'id' => $sucursal->id,
                                'nombre' => $sucursal->nombre,
                                'contacto' => $sucursal->contacto,
                                'telefono' => $sucursal->telefono,
                                'email' => $sucursal->email,
                                'rif' => $sucursal->rif,
                                'direccion' => $sucursal->direccion,
                                'parent' => $sucursal->parent,
                                'tipo' => 'sucursal',
                                'disponible' => $sucursal->disponible
                            ]);
                        }
                    }
                }
            }

            \Log::info("Total clientes disponibles: " . $availableClients->count());
            
            return response()->json([
                'success' => true,
                'data' => $availableClients->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes disponibles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            // Verificar que la contraseña actual es correcta
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 400);
            }

            // Actualizar la contraseña
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar contraseña',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user data.
     */
    public function getCurrentUser(): JsonResponse
    {
        try {
            $user = Auth::user()->load(['persona', 'perfil', 'cliente']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'persona' => $user->persona,
                    'perfil' => $user->perfil,
                    'cliente' => $user->cliente,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile data for editing.
     */
    public function getProfileData(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Cargar todas las relaciones
            $user = User::with(['persona', 'perfil', 'cliente'])->find($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Datos del perfil obtenidos exitosamente',
                'data' => [
                    'user' => $user,
                    'persona' => $user->persona,
                    'perfil' => $user->perfil,
                    'cliente' => $user->cliente,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
