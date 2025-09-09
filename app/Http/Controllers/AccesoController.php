<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Modulo;
use App\Models\Acceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccesoController extends Controller
{
    /**
     * Muestra la vista principal con la lista de usuarios.
     */
    public function index()
    {
        // Solo permitir el acceso a super usuarios (id_perfil = 1)
        if (Auth::user()->id_perfil != 1) {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        $users = User::with('perfil')->get();
        return view('permisos.index', compact('users'));
    }

    /**
     * API para obtener los permisos y módulos de un usuario.
     * Esto se usará para popular el modal.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPermissionsForUser(User $user)
    {
        $allModules = Modulo::orderBy('orden')->get();
        $userPermissions = Acceso::where('id_usuario', $user->id)->get()->keyBy('id_modulo');

        $data = [
            'user' => $user->only(['id', 'name']),
            'modules' => $allModules,
            'permissions' => $userPermissions,
        ];

        return response()->json($data);
    }

    /**
     * API para actualizar los permisos de un usuario.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePermissions(Request $request, User $user)
    {
        // Solo permitir la actualización por super usuarios
        if (Auth::user()->id_perfil != 1) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado'], 403);
        }

        $permissions = $request->input('permissions', []);

        DB::beginTransaction();
        try {
            // Eliminar todos los permisos existentes del usuario
            Acceso::where('id_usuario', $user->id)->delete();

            // Insertar los nuevos permisos
            foreach ($permissions as $permission) {
                // Verificar que el módulo exista y el permiso sea válido
                if (isset($permission['id_modulo']) && isset($permission['read'])) {
                    Acceso::create([
                        'id_usuario' => $user->id,
                        'id_modulo' => $permission['id_modulo'],
                        'read' => $permission['read'],
                        'update' => $permission['update'] ?? 0,
                        'create' => $permission['create'] ?? 0,
                        'delete' => $permission['delete'] ?? 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Permisos actualizados exitosamente.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al actualizar los permisos: ' . $e->getMessage()], 500);
        }
    }
}
