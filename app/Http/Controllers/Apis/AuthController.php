<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Persona;
use App\Models\Perfil;
use App\Models\Cliente;
use App\Services\FcmNotificationService;
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

    public function getUsuariosParaNotificar(): JsonResponse
    {
        // IDs específicos de usuarios que pueden recibir notificaciones de preregistro
        // Para editar: simplemente cambiar estos IDs por los IDs de los usuarios deseados
        // Ejemplo: si quieres agregar más usuarios, solo añade sus IDs al array
        $usuariosParaNotificar = [
            1,2,11,18, // Oscar (Super Admin) - ID del usuario Oscar
        ];

        // Obtener usuarios por IDs específicos
        $usuarios = User::whereIn('id', $usuariosParaNotificar)
        ->where('status', 1) // Solo usuarios activos
        ->select('id', 'name', 'email')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    }

    /**
     * Preregister a new client.
     */
    public function preregister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Datos del usuario
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            
            // Datos de la persona
            'nombre' => 'required|string|max:255',
            'dni' => 'nullable|string|max:255|unique:personas',
            'telefono' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            
            // Datos del cliente
            'cliente_nombre' => 'required|string|max:255',
            'cliente_rif' => 'required|string|max:255|unique:clientes,rif',
            'cliente_contacto' => 'nullable|string|max:255',
            'cliente_direccion' => 'nullable|string|max:255',
            'cliente_telefono' => 'nullable|string|max:255',
            'cliente_email' => 'nullable|string|email|max:255',
            'cliente_sector' => 'nullable|string|max:255',
            'cliente_periodo' => 'nullable|string|max:255',
            
            // IDs de usuarios a notificar (opcional, si no se envía se usan los configurados)
            'notificar_usuarios' => 'nullable|array',
            'notificar_usuarios.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            \DB::beginTransaction();

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

            // Crear el cliente
            $cliente = Cliente::create([
                'nombre' => $request->cliente_nombre,
                'rif' => $request->cliente_rif,
                'contacto' => $request->cliente_contacto,
                'direccion' => $request->cliente_direccion,
                'telefono' => $request->cliente_telefono,
                'email' => $request->cliente_email,
                'sector' => $request->cliente_sector,
                'periodo' => $request->cliente_periodo,
                'disponible' => 0,
                'cupo' => 0,
            ]);

            // Crear el usuario con status = 0 (preregistrado)
            $user = User::create([
                'id_perfil' => 3, // Perfil de cliente
                'id_persona' => $persona->id,
                'cliente_id' => $cliente->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 0, // Status 0 = preregistrado
                'remember_token' => Str::random(60),
            ]);

            // Determinar usuarios a notificar
            $usuariosParaNotificar = $request->notificar_usuarios;
            if (empty($usuariosParaNotificar)) {
                // Si no se especifican usuarios, usar los configurados automáticamente
                $usuariosParaNotificar = [1, 2]; // Oscar y Guillermo por defecto
            }
            
            // Enviar notificaciones a los usuarios especificados
            $this->enviarNotificacionPreregistro($user, $cliente, $usuariosParaNotificar);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preregistro enviado exitosamente. Su solicitud será revisada por nuestros administradores.',
                'data' => [
                    'user' => $user->load(['persona', 'perfil', 'cliente']),
                    'cliente' => $cliente,
                    'status' => 'pending_approval'
                ]
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error ('Error al procesar preregistro: ' . $e->getMessage());  
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el preregistro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar notificación de preregistro a usuarios específicos
     */
    private function enviarNotificacionPreregistro(User $user, Cliente $cliente, array $usuarioIds): void
    {
        try {
            // Obtener usuarios a notificar
            $usuarios = User::whereIn('id', $usuarioIds)
                          ->whereNotNull('fcm_token')
                          ->get();

            if ($usuarios->isEmpty()) {
                \Log::warning('No se encontraron usuarios con tokens FCM para notificar preregistro');
                return;
            }

            $title = '🆕 Nuevo Preregistro de Cliente';
            $body = "El cliente {$cliente->nombre} ({$cliente->rif}) ha iniciado un preregistro al sistema.";
            
            $data = [
                'type' => 'nuevo_preregistro',
                'user_id' => $user->id,
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre,
                'cliente_rif' => $cliente->rif,
                'usuario_nombre' => $user->name,
                'usuario_email' => $user->email,
                'fecha' => now()->toISOString(),
            ];

            $successCount = 0;

            // Enviar notificación a cada usuario usando el servicio FCM existente
            foreach ($usuarios as $usuario) {
                if ($usuario->fcm_token) {
                    // Usar el método privado del servicio FCM para enviar notificación individual
                    $success = $this->enviarNotificacionFCMIndividual(
                        $usuario->fcm_token, 
                        $title, 
                        $body, 
                        $data
                    );
                    
                    if ($success) {
                        $successCount++;
                        \Log::info("Notificación de preregistro enviada al usuario: {$usuario->email}");
                    }
                } else {
                    \Log::warning("Usuario {$usuario->email} no tiene token FCM");
                }
            }

            \Log::info("Notificaciones de preregistro enviadas: {$successCount}/" . $usuarios->count() . " usuarios", [
                'cliente_id' => $cliente->id,
                'usuario_ids' => $usuarioIds
            ]);

        } catch (\Exception $e) {
            \Log::error('Error enviando notificación de preregistro: ' . $e->getMessage());
        }
    }

    /**
     * Enviar notificación FCM individual usando el servicio existente
     */
    private function enviarNotificacionFCMIndividual(string $fcmToken, string $title, string $body, array $data): bool
    {
        try {
            $projectId = config('services.fcm.project_id');
            
            if (!$projectId) {
                \Log::error('FCM Project ID no configurado');
                return false;
            }

            $credentialsFilePath = storage_path("tucombustible-76660-firebase-adminsdk-fbsvc-186df7ef1c.json");
            
            if (!file_exists($credentialsFilePath)) {
                \Log::error('Archivo de credenciales FCM no encontrado: ' . $credentialsFilePath);
                return false;
            }

            // Crear cliente Google
            $client = new \Google\Client();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            
            $token = $client->getAccessToken();
            
            if (!isset($token['access_token'])) {
                \Log::error('No se pudo obtener token de acceso para FCM');
                return false;
            }

            $accessToken = $token['access_token'];

            // Convertir todos los valores de datos a strings (requerido por FCM)
            $dataStrings = [];
            foreach ($data as $key => $value) {
                $dataStrings[$key] = (string) $value;
            }

            // Preparar payload de la notificación (formato correcto para FCM v1)
            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $dataStrings,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            // Enviar notificación
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                \Log::info("Notificación FCM enviada exitosamente a token: " . substr($fcmToken, 0, 20) . "...");
                return true;
            } else {
                \Log::error("Error enviando notificación FCM: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
            \Log::error("Excepción enviando notificación FCM: " . $e->getMessage());
            return false;
        }
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

    /**
     * Obtener lista de usuarios preregistrados (status = 0)
     * Solo para Super Admin
     */
    public function getUsuariosPreregistrados(Request $request)
    {
        try {
            // Verificar que el usuario autenticado sea Super Admin
            $user = $request->user();
            if (!$user || $user->id_perfil != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo Super Admin puede acceder a esta información.'
                ], 403);
            }

            // Obtener usuarios preregistrados con sus relaciones
            $usuariosPreregistrados = User::where('status', 0)
                ->where('id_perfil', 3) // Solo clientes preregistrados
                ->with(['persona', 'cliente', 'perfil'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $usuariosPreregistrados,
                'total' => $usuariosPreregistrados->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios preregistrados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalle de un usuario preregistrado específico
     * Solo para Super Admin
     */
    public function getUsuarioPreregistradoDetalle(Request $request, $id)
    {
        try {
            // Verificar que el usuario autenticado sea Super Admin
            $user = $request->user();
            if (!$user || $user->id_perfil != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo Super Admin puede acceder a esta información.'
                ], 403);
            }

            // Buscar usuario preregistrado específico
            $usuarioPreregistrado = User::where('id', $id)
                ->where('status', 0)
                ->where('id_perfil', 3)
                ->with(['persona', 'cliente', 'perfil'])
                ->first();

            if (!$usuarioPreregistrado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario preregistrado no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $usuarioPreregistrado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle del usuario preregistrado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar usuario preregistrado (cambiar status de 0 a 1)
     * Solo para Super Admin
     */
    public function aprobarUsuarioPreregistrado(Request $request, $id)
    {
        try {
            // Verificar que el usuario autenticado sea Super Admin
            $user = $request->user();
            if (!$user || $user->id_perfil != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo Super Admin puede aprobar usuarios.'
                ], 403);
            }

            // Buscar usuario preregistrado
            $usuarioPreregistrado = User::where('id', $id)
                ->where('status', 0)
                ->where('id_perfil', 3)
                ->first();

            if (!$usuarioPreregistrado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario preregistrado no encontrado'
                ], 404);
            }

            // Aprobar usuario (cambiar status a 1)
            $usuarioPreregistrado->update(['status' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario aprobado exitosamente',
                'data' => $usuarioPreregistrado->fresh(['persona', 'cliente', 'perfil'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar usuario preregistrado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar usuario preregistrado (eliminar o marcar como rechazado)
     * Solo para Super Admin
     */
    public function rechazarUsuarioPreregistrado(Request $request, $id)
    {
        try {
            // Verificar que el usuario autenticado sea Super Admin
            $user = $request->user();
            if (!$user || $user->id_perfil != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo Super Admin puede rechazar usuarios.'
                ], 403);
            }

            // Buscar usuario preregistrado
            $usuarioPreregistrado = User::where('id', $id)
                ->where('status', 0)
                ->where('id_perfil', 3)
                ->first();

            if (!$usuarioPreregistrado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario preregistrado no encontrado'
                ], 404);
            }

            // Eliminar usuario preregistrado y sus relaciones
            \DB::beginTransaction();
            
            // Eliminar cliente asociado
            if ($usuarioPreregistrado->cliente_id) {
                \App\Models\Cliente::where('id', $usuarioPreregistrado->cliente_id)->delete();
            }
            
            // Eliminar persona asociada
            if ($usuarioPreregistrado->id_persona) {
                \App\Models\Persona::where('id', $usuarioPreregistrado->id_persona)->delete();
            }
            
            // Eliminar usuario
            $usuarioPreregistrado->delete();
            
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario preregistrado rechazado y eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar usuario preregistrado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 