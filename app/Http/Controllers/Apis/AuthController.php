<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Persona;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'nombre' => 'required|string|max:255',
            'dni' => 'nullable|string|max:255|unique:personas',
            'telefono' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'perfil_id' => 'nullable|exists:perfiles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Crear la persona
            $persona = Persona::create([
                'nombre' => $request->nombre,
                'dni' => $request->dni,
                'telefono' => $request->telefono,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
            ]);

            // Crear el usuario
            $user = User::create([
                'id_perfil' => $request->perfil_id,
                'id_persona' => $persona->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
            ]);

            // Cargar las relaciones
            $user->load(['persona', 'perfil', 'cliente']);

            // Generar token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'user' => $user,
                    'persona' => $user->persona,
                    'perfil' => $user->perfil,
                    'cliente' => $user->cliente,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $emailOrName = $request->email;
        $password = $request->password;

        // Intentar autenticación por email primero
        $user = null;
        if (filter_var($emailOrName, FILTER_VALIDATE_EMAIL)) {
            // Es un email válido, intentar autenticación por email
            if (Auth::attempt(['email' => $emailOrName, 'password' => $password])) {
                $user = User::with(['persona', 'perfil', 'cliente'])->where('email', $emailOrName)->first();
            }
        } else {
            // No es un email válido, intentar autenticación por name
            if (Auth::attempt(['name' => $emailOrName, 'password' => $password])) {
                $user = User::with(['persona', 'perfil', 'cliente'])->where('name', $emailOrName)->first();
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        // Debug: Verificar que la relación se carga correctamente
        \Log::info('User cliente_id: ' . $user->cliente_id);
        \Log::info('User cliente relation: ' . ($user->cliente ? 'loaded' : 'null'));

        // Generar token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'user' => $user,
                    'persona' => $user->persona,
                    'perfil' => $user->perfil,
                    'cliente' => $user->cliente,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['persona', 'perfil', 'cliente']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'persona' => $user->persona,
                'perfil' => $user->perfil,
                'cliente' => $user->cliente,
            ]
        ]);
    }

    /**
     * Get all perfiles for registration.
     */
    public function getPerfiles(): JsonResponse
    {
        $perfiles = Perfil::all();

        return response()->json([
            'success' => true,
            'data' => $perfiles
        ]);
    }

    /**
     * Debug endpoint to check user data.
     */
    public function debugUser(Request $request): JsonResponse
    {
        $user = User::with(['persona', 'perfil', 'cliente'])->where('email', 'cliente44@test.com')->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'user_raw' => $user->getRawOriginal(),
                'cliente_id' => $user->cliente_id,
                'cliente_relation' => $user->cliente,
                'perfil' => $user->perfil,
                'perfil_id' => $user->id_perfil,
                'nombre_perfil' => $user->perfil->nombre ?? 'null',
            ]
        ]);
    }
} 