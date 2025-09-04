<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pedido;
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
                Log::warning("No se encontró token FCM para el cliente {$pedido->cliente_id}");
                return false;
            }

            // Preparar datos de la notificación
            $title = self::getStatusChangeTitle($oldStatus, $newStatus);
            $body = self::getStatusChangeBody($pedido, $oldStatus, $newStatus);
            $data = self::prepareNotificationData($pedido, $oldStatus, $newStatus, $observaciones);

            // Enviar notificación
            return self::sendFcmNotification($clienteUser->fcm_token, $title, $body, $data);

        } catch (\Exception $e) {
            Log::error("Error enviando notificación de cambio de estatus: " . $e->getMessage());
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

    /**
     * Enviar notificación FCM
     */
    private static function sendFcmNotification(string $fcmToken, string $title, string $body, array $data): bool
    {
        try {
            $projectId = config('services.fcm.project_id');
            
            if (!$projectId) {
                Log::error('FCM Project ID no configurado');
                return false;
            }

            $credentialsFilePath = storage_path("tucombustible-76660-firebase-adminsdk-fbsvc-186df7ef1c.json");
            
            if (!file_exists($credentialsFilePath)) {
                Log::error('Archivo de credenciales FCM no encontrado: ' . $credentialsFilePath);
                return false;
            }

            // Crear cliente Google
            $client = new \Google\Client();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            
            $token = $client->getAccessToken();
            
            if (!isset($token['access_token'])) {
                Log::error('No se pudo obtener token de acceso para FCM');
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

            Log::info("Enviando payload FCM: " . json_encode($payload));

            // Enviar notificación
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                Log::info("Notificación FCM enviada exitosamente a token: " . substr($fcmToken, 0, 20) . "...");
                return true;
            } else {
                Log::error("Error enviando notificación FCM: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Excepción enviando notificación FCM: " . $e->getMessage());
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
                Log::warning("No se encontraron usuarios administradores para enviar notificación");
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
                        Log::info("Notificación enviada al administrador: {$admin->email}");
                    }
                } else {
                    Log::warning("Administrador {$admin->email} no tiene token FCM");
                }
            }

            Log::info("Notificaciones enviadas: {$successCount}/{$totalAdmins} administradores");
            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error("Error enviando notificación de nuevo pedido al administrador: " . $e->getMessage());
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
                Log::warning("No se encontró token FCM para el cliente {$pedido->cliente_id}");
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
            Log::error("Error enviando notificación de nuevo pedido: " . $e->getMessage());
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
                Log::warning("No se encontró token FCM para el usuario de sucursal {$sucursalUser->id}");
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
            Log::error("Error enviando notificación a sucursal: " . $e->getMessage());
            return false;
        }
    }
}
