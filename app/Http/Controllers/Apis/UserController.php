<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Persona;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Obtener todos los usuarios
     */
    public function index()
    {
        try {
            $usuarios = User::with(['persona', 'perfil'])
                ->select('id', 'name', 'email', 'id_perfil', 'created_at', 'updated_at')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'email' => $user->email,
                        'nombre' => $user->persona ? $user->persona->nombre : 'Sin nombre',
                        'apellido' => $user->persona ? $user->persona->apellido : 'Sin apellido',
                        'full_name' => $user->persona ? 
                            trim($user->persona->nombre . ' ' . $user->persona->apellido) : 
                            $user->name,
                        'role' => $user->perfil ? $user->perfil->nombre : 'Sin rol',
                        'perfil_id' => $user->id_perfil,
                        'activo' => true, // Por defecto activo ya que no tenemos este campo
                        'last_login' => null, // No tenemos este campo
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => 'Usuarios obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show($id)
    {
        try {
            $user = User::with(['persona', 'perfil'])->find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'nombre' => $user->persona ? $user->persona->nombre : 'Sin nombre',
                'apellido' => $user->persona ? $user->persona->apellido : 'Sin apellido',
                'full_name' => $user->persona ? 
                    trim($user->persona->nombre . ' ' . $user->persona->apellido) : 
                    $user->name,
                'role' => $user->perfil ? $user->perfil->nombre : 'Sin rol',
                'perfil_id' => $user->id_perfil,
                'activo' => true, // Por defecto activo ya que no tenemos este campo
                'last_login' => null, // No tenemos este campo
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $userData,
                'message' => 'Usuario obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'perfil_id' => 'required|exists:perfiles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Crear persona
            $persona = Persona::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
            ]);

            // Crear usuario
            $user = User::create([
                'name' => $request->nombre . ' ' . $request->apellido,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'id_perfil' => $request->perfil_id,
                'id_persona' => $persona->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nombre' => $persona->nombre,
                    'apellido' => $persona->apellido,
                    'full_name' => trim($persona->nombre . ' ' . $persona->apellido),
                    'role' => $user->perfil ? $user->perfil->nombre : 'Sin rol',
                    'perfil_id' => $user->id_perfil,
                    'activo' => true, // Por defecto activo
                ],
                'message' => 'Usuario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::with(['persona', 'perfil'])->find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|min:6',
                'nombre' => 'sometimes|string|max:255',
                'apellido' => 'sometimes|string|max:255',
                'perfil_id' => 'sometimes|exists:perfiles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar persona si se proporciona
            if ($request->has('nombre') || $request->has('apellido')) {
                $persona = $user->persona;
                if ($persona) {
                    $persona->update([
                        'nombre' => $request->nombre ?? $persona->nombre,
                        'apellido' => $request->apellido ?? $persona->apellido,
                    ]);
                }
            }

            // Actualizar usuario
            $updateData = [];
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('password')) $updateData['password'] = Hash::make($request->password);
            if ($request->has('perfil_id')) $updateData['id_perfil'] = $request->perfil_id;
            if ($request->has('nombre') || $request->has('apellido')) {
                $nombre = $request->get('nombre', $user->persona->nombre);
                $apellido = $request->get('apellido', $user->persona->apellido);
                $updateData['name'] = $nombre . ' ' . $apellido;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            DB::commit();

            // Recargar usuario con relaciones
            $user->load(['persona', 'perfil']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nombre' => $user->persona ? $user->persona->nombre : 'Sin nombre',
                    'apellido' => $user->persona ? $user->persona->apellido : 'Sin apellido',
                    'full_name' => $user->persona ? 
                        trim($user->persona->nombre . ' ' . $user->persona->apellido) : 
                        $user->name,
                    'role' => $user->perfil ? $user->perfil->nombre : 'Sin rol',
                    'perfil_id' => $user->id_perfil,
                    'activo' => true, // Por defecto activo
                ],
                'message' => 'Usuario actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un usuario
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Verificar que no sea el usuario actual
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario'
                ], 422);
            }

            DB::beginTransaction();

            // Eliminar persona asociada
            if ($user->persona) {
                $user->persona->delete();
            }

            // Eliminar usuario
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de un usuario (activar/desactivar)
     * Nota: Como no tenemos campo 'activo' en la base de datos,
     * este método simula el cambio de estado
     */
    public function toggleStatus($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Verificar que no sea el usuario actual
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes cambiar el estado de tu propio usuario'
                ], 422);
            }

            // Como no tenemos campo 'activo', simulamos el cambio
            // En una implementación real, necesitarías añadir este campo a la base de datos
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'activo' => true, // Simulado
                ],
                'message' => 'Estado del usuario actualizado exitosamente (simulado)'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener perfiles disponibles
     */
    public function getPerfiles()
    {
        try {
            $perfiles = Perfil::select('id', 'nombre', 'descripcion')
                ->where('activo', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $perfiles,
                'message' => 'Perfiles obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfiles: ' . $e->getMessage()
            ], 500);
        }
    }
}
