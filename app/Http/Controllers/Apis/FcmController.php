<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class FcmController extends Controller
{
    /**
     * Actualiza el token FCM del usuario autenticado
     */
    public function updateToken(Request $request): JsonResponse
    {
        try {
            // Validar la solicitud
            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el usuario autenticado
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Actualizar el token FCM
            $user->update([
                'fcm_token' => $request->fcm_token
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token FCM actualizado exitosamente',
                'data' => [
                    'user_id' => $user->id,
                    'fcm_token_updated' => true
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error actualizando token FCM: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtiene el token FCM del usuario autenticado
     */
    public function getToken(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'fcm_token' => $user->fcm_token,
                    'has_token' => !empty($user->fcm_token)
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error obteniendo token FCM: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Elimina el token FCM del usuario autenticado (logout)
     */
    public function removeToken(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Limpiar el token FCM
            $user->update([
                'fcm_token' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token FCM eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error eliminando token FCM: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
