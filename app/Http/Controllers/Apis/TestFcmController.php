<?php


namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;

class TestFcmController extends Controller
{

    public function sendFcmNotification(Request $request)
    {

        $fcmToken = $request->tokenfcm;
        $title = "Prueba TuCombustible";
        $body = "¡Esta es una notificación de prueba desde TuCombustible!";
        $projectId = config("services.fcm.project_id");
        
    
        $dataN = [
            "fecha" => $request->data["fecha"] ?? now()->toDateString(),
            "mensaje" => "Notificación de prueba",
            "app" => "TuCombustible"
        ];
        
        // Load Google credentials - ajustar ruta para TuCombustible
        $credentialsFilePath = storage_path("tucombustible-76660-firebase-adminsdk-fbsvc-186df7ef1c.json");
        
        if (!file_exists($credentialsFilePath)) {
            return response()->json([
                "success" => false,
                "message" => "Archivo de credenciales no encontrado: " . $credentialsFilePath
            ], 404);
        }
        
        try {
            $client = new GoogleClient();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope("https://www.googleapis.com/auth/firebase.messaging");
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();

            if (!isset($token["access_token"])) {
                return response()->json([
                    "success" => false,
                    "message" => "Failed to get access token"
                ], 500);
            }

            $accessToken = $token["access_token"];

            $headers = [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json",
            ];

            $data = [
                "message" => [
                    "token" => $fcmToken,
                    "notification" => [
                        "title" => $title,
                        "body" => $body,
                    ],
                    "data"  => $dataN
                ]
            ];
            
            $payload = json_encode($data);
            
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/$projectId/messages:send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return response()->json([
                    "success" => false,
                    "message" => "Curl Error: " . $error
                ], 500);
            } else {
                return response()->json([
                    "success" => true,
                    "message" => "Notification sent successfully",
                    "response" => json_decode($response, true),
                    "sent_to" => $fcmToken,
                    "project_id" => $projectId
                ], 200);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Error: " . $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine()
            ], 500);
        }
    }
}