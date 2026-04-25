<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pedido;
use App\Models\Cliente;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmNotificationService
{
    /**
     * Enviar notificación de cambio de estatus de pedido
     */
    public static function sendPedidoStatusNotification(Pedido $pedido, string $oldStatus, string $newStatus, ?string $observaciones = null): bool
    {
        try {
            // Buscar el usuario cliente asociado al pedido
            $clienteUser = User::where('cliente_id', $pedido->cliente_id)->first();
            
            if (!$clienteUser || !$clienteUser->fcm_token) {
               // Log::warning("No se encontró token FCM para el cliente {$pedido->cliente_id}");
                return false;
            }

            // Preparar datos de la notificación
            $title = self::getStatusChangeTitle($oldStatus, $newStatus);
            $body = self::getStatusChangeBody($pedido, $oldStatus, $newStatus);
            $data = self::prepareNotificationData($pedido, $oldStatus, $newStatus, $observaciones);

            // Enviar notificación
            return self::sendFcmNotification($clienteUser->fcm_token, $title, $body, $data);

        } catch (\Exception $e) {
            //Log::error("Error enviando notificación de cambio de estatus: " . $e->getMessage());
            return false;
        }
    }

    public static function sendCustomNotification(Pedido $pedido, Cliente $cliente, string $title, string $text): bool
    {
        try {
            // Buscar el usuario cliente asociado al pedido
            //$clienteU = User::where('cliente_id', $cliente->cliente_id)->first();
            
            if (!$cliente || !$cliente->fcm_token) {
              //  Log::warning("No se encontró token FCM para el cliente {$cliente->cliente_id}");
                return false;
            }

            // Preparar datos de la notificación
            $title = $title;
            $body = $text;
            $data = self::prepareAlertData($pedido, $cliente);

            // Enviar notificación
            return self::sendFcmNotification($cliente->fcm_token, $title, $body, $data);

        } catch (\Exception $e) {
           // Log::error("Error enviando notificación de cambio de estatus: " . $e->getMessage());
            return false;
        }
    }


     public static function sendNotification(User $user, string $title, string $text): bool
    {
        try {
            // Buscar el usuario cliente asociado al pedido
            //$clienteU = User::where('cliente_id', $cliente->cliente_id)->first();
            
            if (!$user || !$user->fcm_token) {
             //   Log::warning("No se encontró token FCM para el Usuario {$user->id}");
                return false;
            }

            // Preparar datos de la notificación
            $title = $title;
            $body = $text;
            $data =  $data = [
                'type' => 'nuevo_notificacion',
                'user_id' => $user->id,
            ]; 
          
            // Enviar notificación
            return self::sendFcmNotification($user->fcm_token, $title, $body, $data);

        } catch (\Exception $e) {
            //Log::error("Error enviando notificación de cambio de estatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación de asignación de pedido al conductor
     */
    public static function sendPedidoAsignadoConductorNotification(Pedido $pedido, int $chofer_id): bool
    {
        try {
            // Buscar el chofer y su relación con persona y usuario
            $chofer = \DB::table('choferes')
                ->join('personas', 'choferes.persona_id', '=', 'personas.id')
                ->join('users', 'users.id_persona', '=', 'personas.id')
                ->where('choferes.id', $chofer_id)
                ->where('users.id_perfil', 4) // Perfil conductor
                ->select('users.id', 'users.fcm_token', 'personas.nombre')
                ->first();
            
            if (!$chofer || !$chofer->fcm_token) {
              //  Log::warning("No se encontró token FCM para el conductor {$chofer_id}");
                return false;
            }

            // Preparar datos de la notificación
            $cliente = $pedido->cliente;
            $clienteNombre = $cliente ? $cliente->nombre : 'Cliente';
            
            $title = '🚚 Nuevo Pedido Asignado';
            $body = "Se te ha asignado el pedido #{$pedido->id} - {$clienteNombre}. Cantidad: " . 
                    ($pedido->cantidad_aprobada ?? $pedido->cantidad_solicitada) . " L";
            
            $data = [
                'type' => 'pedido_asignado',
                'pedido_id' => (string)$pedido->id,
                'cliente_id' => (string)$pedido->cliente_id,
                'cliente_nombre' => $clienteNombre,
                'cantidad' => (string)($pedido->cantidad_aprobada ?? $pedido->cantidad_solicitada),
                'estado' => $pedido->estado,
                'action' => 'navigate_to_pedido_detail',
                'screen' => 'conductor_pedido_detalle',
            ];

            // Enviar notificación
            $result = self::sendFcmNotification($chofer->fcm_token, $title, $body, $data);
            
            
            return $result;

        } catch (\Exception $e) {
            //Log::error("Error enviando notificación al conductor: " . $e->getMessage());
            return false;
        }
    }

    

    /**
     * Obtener título de la notificación según el cambio de estatus
     */
    private static function getStatusChangeTitle(string $oldStatus, string $newStatus): string
    {
        switch ($newStatus) {
            case 'aprobado':
                return '🎉 ¡Pedido Aprobado!';
            case 'rechazado':
                return '❌ Pedido Rechazado';
            case 'en_proceso':
                return '🚚 Pedido en Proceso';
            case 'completado':
                return '✅ Pedido Completado';
            case 'cancelado':
                return '🚫 Pedido Cancelado';
            default:
                return '📋 Estado del Pedido Actualizado';
        }
    }

    /**
     * Obtener cuerpo de la notificación según el cambio de estatus
     */
    private static function getStatusChangeBody(Pedido $pedido, string $oldStatus, string $newStatus): string
    {
        $pedidoId = $pedido->id;
        $cantidad = $pedido->cantidad_aprobada ?? $pedido->cantidad_solicitada;
        
        switch ($newStatus) {
            case 'aprobado':
                return "Tu pedido #{$pedidoId} ha sido aprobado por {$cantidad} litros.";
            case 'rechazado':
                return "Tu pedido #{$pedidoId} ha sido rechazado. Revisa los detalles.";
            case 'en_proceso':
                return "Tu pedido #{$pedidoId} está siendo procesado. Prepárate para recibirlo.";
            case 'completado':
                return "Tu pedido #{$pedidoId} ha sido completado. ¡Gracias por tu paciencia!";
            case 'cancelado':
                return "Tu pedido #{$pedidoId} ha sido cancelado.";
            default:
                return "El estado de tu pedido #{$pedidoId} ha cambiado a " . ucfirst($newStatus);
        }
    }

    /**
     * Preparar datos adicionales para la notificación (SIMPLIFICADO)
     */
    private static function prepareNotificationData(Pedido $pedido, string $oldStatus, string $newStatus, ?string $observaciones = null): array
    {
        $data = [
            'cantidad_solicitada' => $pedido->cantidad_solicitada,
            'estado_nuevo' => $newStatus,
        ];

        // Solo agregar cantidad aprobada si el pedido fue aprobado
        if ($newStatus === 'aprobado' && $pedido->cantidad_aprobada) {
            $data['cantidad_aprobada'] = $pedido->cantidad_aprobada;
        }

        // Solo agregar observaciones si existen
        if ($observaciones) {
            $data['observaciones'] = $observaciones;
        }

        return $data;
    }


    private static function prepareAlertData(Cliente $Cliente, Pedido $pedido ): array
    {
        $data = [
            'cantidad_solicitada' => $pedido->cantidad_solicitada,
            'disponible' => $Cliente->disponible-$pedido->cantidad_solicitada,
            'cupo' => $Cliente->cupo,
        ];

        
        
        return $data;
    }

    /**
     * Enviar notificación FCM
     */
    private static function sendFcmNotification(string $fcmToken, string $title, string $body, array $data): bool
    {
        try {
            $projectId = config('services.fcm.project_id');
            
            if (!$projectId) {
              //  Log::error('FCM Project ID no configurado');
                return false;
            }

            $credentialsFilePath = storage_path("tucombustible-76660-firebase-adminsdk-fbsvc-186df7ef1c.json");
            
            if (!file_exists($credentialsFilePath)) {
                //Log::error('Archivo de credenciales FCM no encontrado: ' . $credentialsFilePath);
                return false;
            }

            // Crear cliente Google
            $client = new \Google\Client();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            
            $token = $client->getAccessToken();
            
            if (!isset($token['access_token'])) {
                //Log::error('No se pudo obtener token de acceso para FCM');
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                return true;
            } else {
          //      Log::error("Error enviando notificación FCM: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
        //    Log::error("Excepción enviando notificación FCM: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación de nuevo pedido creado AL ADMINISTRADOR
     */
    public static function sendNewPedidoNotificationToAdmin(Pedido $pedido): bool
    {
        try {
            // Buscar usuarios administradores (superadmin id=1 y administrador id=2)
            $admins = User::whereIn('id_perfil', [1, 2])->get();
            
            if ($admins->isEmpty()) {
            //    Log::warning("No se encontraron usuarios administradores para enviar notificación");
                return false;
            }

            $title = '🆕 Nuevo Pedido Creado';
            $body = "El cliente {$pedido->cliente->nombre} ha creado un nuevo pedido #{$pedido->id} por {$pedido->cantidad_solicitada} litros.";
            
            $data = [
                'pedido_id' => $pedido->id,
                'cliente_nombre' => $pedido->cliente->nombre,
                'cantidad_solicitada' => $pedido->cantidad_solicitada,
                'fecha_solicitud' => $pedido->fecha_solicitud->format('Y-m-d H:i'),
                'tipo_notificacion' => 'nuevo_pedido_admin',
            ];

            $successCount = 0;
            $totalAdmins = $admins->count();

            // Enviar notificación a todos los administradores
            foreach ($admins as $admin) {
                if ($admin->fcm_token) {
                    if (self::sendFcmNotification($admin->fcm_token, $title, $body, $data)) {
                        $successCount++;
                    }
                } else {
               //     Log::warning("Administrador {$admin->email} no tiene token FCM");
                }
            }

            return $successCount > 0;

        } catch (\Exception $e) {
            //Log::error("Error enviando notificación de nuevo pedido al administrador: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación de nuevo pedido creado (para el cliente - mantenido por compatibilidad)
     */
    public static function sendNewPedidoNotification(Pedido $pedido): bool
    {
        try {
            // Buscar el usuario cliente asociado al pedido
            $clienteUser = User::where('cliente_id', $pedido->cliente_id)->first();
            
            if (!$clienteUser || !$clienteUser->fcm_token) {
              //  Log::warning("No se encontró token FCM para el cliente {$pedido->cliente_id}");
                return false;
            }

            $title = '🆕 Nuevo Pedido Creado';
            $body = "Has creado un nuevo pedido #{$pedido->id} por {$pedido->cantidad_solicitada} litros.";
            
            $data = [
                'cantidad_solicitada' => $pedido->cantidad_solicitada,
                'fecha_solicitud' => $pedido->fecha_solicitud->format('Y-m-d H:i'),
            ];

            return self::sendFcmNotification($clienteUser->fcm_token, $title, $body, $data);

        } catch (\Exception $e) {
            //Log::error("Error enviando notificación de nuevo pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación a sucursal cuando se crea un pedido para ella
     */
    public static function sendPedidoNotificationToSucursal(Pedido $pedido, User $sucursalUser): bool
    {
        try {
            if (!$sucursalUser->fcm_token) {
              //  Log::warning("No se encontró token FCM para el usuario de sucursal {$sucursalUser->id}");
                return false;
            }

            // Obtener información del cliente
            $cliente = $pedido->cliente;
            $clienteNombre = $cliente ? $cliente->nombre : 'Cliente';

            $title = '📦 Nuevo Pedido para tu Sucursal';
            $body = "Se ha creado un pedido #{$pedido->id} para {$clienteNombre} por {$pedido->cantidad_solicitada} litros.";
            
            $data = [
                'pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'cliente_nombre' => $clienteNombre,
                'cantidad_solicitada' => $pedido->cantidad_solicitada,
                'fecha_solicitud' => $pedido->fecha_solicitud->format('Y-m-d H:i'),
                'estado' => $pedido->estado,
                'tipo_notificacion' => 'pedido_sucursal',
            ];

            return self::sendFcmNotification($sucursalUser->fcm_token, $title, $body, $data);

        } catch (\Exception $e) {
            //Log::error("Error enviando notificación a sucursal: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación de bajo disponible al cliente padre
     * Se envía cuando el disponible queda por debajo del 10% del cupo
     */
    public static function sendBajoDisponibleNotification(Cliente $clientePadre, float $nuevoDisponible, float $cupo, ?string $sucursalNombre = null): bool
    {
        try {
            // Buscar el usuario asociado al cliente padre
            $clienteUser = User::where('cliente_id', $clientePadre->id)->first();
            
            if (!$clienteUser || !$clienteUser->fcm_token) {
              //  Log::warning("No se encontró token FCM para el cliente padre {$clientePadre->id}");
                return false;
            }

            // Calcular el porcentaje actual
            $porcentajeActual = ($nuevoDisponible / $cupo) * 100;
            $porcentajeFormateado = number_format($porcentajeActual, 1);

            // Preparar título y mensaje
            $title = '⚠️ Bajo Disponible';
            
            if ($sucursalNombre) {
                $body = "Tu sucursal '{$sucursalNombre}' ha recibido combustible. Tu disponible actual es de {$nuevoDisponible} litros ({$porcentajeFormateado}% de tu cupo). Se recomienda tomar previsiones.";
            } else {
                $body = "Tu disponible actual es de {$nuevoDisponible} litros ({$porcentajeFormateado}% de tu cupo). Se recomienda tomar previsiones.";
            }
            
            $data = [
                'cliente_id' => $clientePadre->id,
                'disponible_actual' => $nuevoDisponible,
                'cupo_total' => $cupo,
                'porcentaje_disponible' => $porcentajeActual,
                'tipo_notificacion' => 'bajo_disponible',
                'sucursal_nombre' => $sucursalNombre ?? '',
            ];

            return self::sendFcmNotification($clienteUser->fcm_token, $title, $body, $data);

        } catch (\Exception $e) {
            //Log::error("Error enviando notificación de bajo disponible: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación de bajo disponible a super admins
     * Se envía cuando cualquier cliente tiene disponible por debajo del 10% del cupo
     */
    public static function sendBajoDisponibleNotificationToSuperAdmins(Cliente $cliente, float $nuevoDisponible, float $cupo): bool
    {
        try {
            // Buscar usuarios super admins (id_perfil = 1)
            $superAdmins = User::where('id_perfil', 1)->get();
            
            if ($superAdmins->isEmpty()) {
              //  Log::warning("No se encontraron usuarios super admin para enviar notificación de bajo disponible");
                return false;
            }

            // Calcular el porcentaje actual
            $porcentajeActual = ($nuevoDisponible / $cupo) * 100;
            $porcentajeFormateado = number_format($porcentajeActual, 1);

            // Preparar título y mensaje para super admins
            $title = '🚨 Alerta: Cliente con Bajo Disponible';
            $body = "El cliente '{$cliente->nombre}' tiene disponible bajo: {$nuevoDisponible} litros ({$porcentajeFormateado}% de su cupo de {$cupo} litros).";
            
            $data = [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre,
                'disponible_actual' => $nuevoDisponible,
                'cupo_total' => $cupo,
                'porcentaje_disponible' => $porcentajeActual,
                'tipo_notificacion' => 'bajo_disponible_admin',
            ];

            $successCount = 0;
            $totalSuperAdmins = $superAdmins->count();

            // Enviar notificación a todos los super admins
            foreach ($superAdmins as $superAdmin) {
                if ($superAdmin->fcm_token) {
                    if (self::sendFcmNotification($superAdmin->fcm_token, $title, $body, $data)) {
                        $successCount++;
                    }
                } else {
                //    Log::warning("Super admin {$superAdmin->email} no tiene token FCM");
                }
            }

            return $successCount > 0;

        } catch (\Exception $e) {
           // Log::error("Error enviando notificación de bajo disponible a super admins: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación consolidada de bajo disponible a super admins
     * Se envía una sola notificación con todos los clientes con bajo disponible
     */
    public static function sendBajoDisponibleConsolidatedNotificationToSuperAdmins(array $clientesConBajoDisponible): bool
    {
        try {
            // Buscar usuarios super admins (id_perfil = 1)
            $superAdmins = User::where('id_perfil', 1)->get();
            
            if ($superAdmins->isEmpty()) {
             //   Log::warning("No se encontraron usuarios super admin para enviar notificación consolidada de bajo disponible");
                return false;
            }

            $totalClientes = count($clientesConBajoDisponible);
            
            if ($totalClientes === 0) {
                return true;
            }

            // Preparar título y mensaje consolidado
            $title = '🚨 Alerta';
            
            if ($totalClientes === 1) {
                $cliente = $clientesConBajoDisponible[0];
                $porcentaje = number_format(($cliente['disponible'] / $cliente['cupo']) * 100, 1);
                $body = "Múltiples Clientes con Bajo Disponible\n1 cliente con bajo disponible: {$cliente['nombre']} ({$porcentaje}%)";
            } else {
                $body = "Múltiples Clientes con Bajo Disponible\n{$totalClientes} clientes tienen disponible bajo. Toca para ver detalles.";
            }
            
            // Preparar datos consolidados
            $data = [
                'total_clientes' => $totalClientes,
                'clientes' => json_encode($clientesConBajoDisponible),
                'tipo_notificacion' => 'bajo_disponible_consolidado',
                'fecha_revision' => now()->toDateTimeString(),
            ];

            $successCount = 0;
            $totalSuperAdmins = $superAdmins->count();

            // Enviar notificación consolidada a todos los super admins
            foreach ($superAdmins as $superAdmin) {
                if ($superAdmin->fcm_token) {
                    if (self::sendFcmNotification($superAdmin->fcm_token, $title, $body, $data)) {
                        $successCount++;
                    }
                } else {
             //       Log::warning("Super admin {$superAdmin->email} no tiene token FCM");
                }
            }

            return $successCount > 0;

        } catch (\Exception $e) {
           // Log::error("Error enviando notificación consolidada de bajo disponible a super admins: " . $e->getMessage());
            return false;
        }
    }

    public static function enviarNotification(string $title, string $body, array $data): void
    {
        try {
            // Obtener usuarios a notificar
            $idsCliente = User::where('cliente_id', 348)
                                    ->pluck('id')
                                    ->toArray();

                    // 2. Definir los IDs base que siempre deben ser incluidos
                    $idBase = [1, 3, 9, 10, 109];

                    // 3. FUSIONAR y ELIMINAR DUPLICADOS para crear la lista final de IDs
                    // Usamos array_unique() para asegurar que un ID no se consulte dos veces si está en ambas listas.
                    $usuarioIds = array_unique(array_merge($idsCliente, $idBase));

                    // 4. Consulta final: Obtener los objetos User con los IDs combinados y un token FCM
                    $usuarios = User::whereIn('id', $usuarioIds)
                                    ->whereNotNull('fcm_token')
                                    ->get();
            if ($usuarios->isEmpty()) {
               // Log::warning('No se encontraron usuarios con tokens FCM para notificar');
                return;
            }

            
            // $data = [
            //     'type' => 'nuevo_preregistro',
            //     'user_id' => $user->id,
            //     'cliente_id' => $cliente->id,
            //     'cliente_nombre' => $cliente->nombre,
            //     'cliente_rif' => $cliente->rif,
            //     'usuario_nombre' => $user->name,
            //     'usuario_email' => $user->email,
            //     'fecha' => now()->toISOString(),
            // ];

            $successCount = 0;

            // Enviar notificación a cada usuario usando el servicio FCM existente
            foreach ($usuarios as $usuario) {
                if ($usuario->fcm_token) {
                    // Usar el método privado del servicio FCM para enviar notificación individual
                    
                    if (self::sendFcmNotification($usuario->fcm_token, $title, $body, $data)) {
                        $successCount++;
                    }
                } else {
                 //   Log::warning("Usuario {$usuario->email} no tiene token FCM");
                }
            }

            
        } catch (\Exception $e) {
//            Log::error('Error enviando notificación: ' . $e->getMessage());
        }
    }
}
