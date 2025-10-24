<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Token de tu Bot de Telegram.
     * PRÁCTICA SUGERIDA: Usar env('TELEGRAM_BOT_TOKEN')
     */
    protected $botToken = '8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po'; 

    /**
     * ID del chat o grupo de destino. (Debe ser un ID numérico, probablemente negativo para grupos)
     * PRÁCTICA SUGERIDA: Usar env('TELEGRAM_CHAT_ID')
     */
    protected $chatId = '-1002935486238'; 

    /**
     * Recibe la imagen capturada desde el frontend y la envía a un grupo de Telegram.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPhoto(Request $request)
    {
        // 1. Validación de la solicitud
        $request->validate([
            'chart_image' => 'required|image|mimes:png,jpg,jpeg|max:8192', // Máximo 8MB permitido por Telegram
            'caption' => 'nullable|string|max:1024' // Descripción opcional
        ]);

        // // Verificación de credenciales (para evitar envíos fallidos si no se han configurado)
        // if ($this->botToken === '8278356133:AAFbPIiY77YEdFbRoO8JSpF83UKaSM2X-dM' || $this->chatId === '+YONFb8H0Fxg5ODNh') {
        //      // Es preferible usar un log o lanzar una excepción real en un entorno de producción.
        //      return response()->json([
        //         'message' => 'Error: Configuración de Telegram pendiente. Por favor, actualice el token y el chat ID en TelegramController.php.', 
        //     ], 500);
        // }

        try {
            // Obtener el archivo y la descripción
            $photoFile = $request->file('chart_image');
            $caption = $request->input('caption', 'Reporte Automático (Sin descripción)');
            
            $url = "https://api.telegram.org/bot{$this->botToken}/sendPhoto";

            // 2. Hacer la solicitud multipart a la API de Telegram usando el helper Http
            $response = Http::timeout(30)->attach(
                'photo', // El nombre del campo que la API de Telegram espera para el archivo
                file_get_contents($photoFile->getRealPath()), // Contenido binario del archivo
                $photoFile->getClientOriginalName() // Nombre original del archivo
            )->post($url, [
                'chat_id' => $this->chatId,
                'caption' => $caption,
                'parse_mode' => 'Markdown' // Permite el formato Markdown en la descripción
            ]);

            // 3. Verificar si la respuesta de Telegram es exitosa
            if ($response->successful() && $response->json('ok')) {
                Log::info("Reporte enviado a Telegram con éxito. Chat ID: {$this->chatId}");
                return response()->json(['message' => 'Reporte enviado a Telegram con éxito.'], 200);
            } else {
                // Registrar el error detallado de la API de Telegram para debugging
                Log::error('Error al enviar reporte a Telegram:', [
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                return response()->json([
                    'message' => 'Error al enviar el reporte a Telegram.', 
                    'telegram_error' => $response->json('description', 'Error desconocido del API')
                ], 500);
            }

        } catch (\Exception $e) {
            // Manejo de excepciones generales (ej. error de I/O, problema de red, etc.)
            Log::error('Excepción al manejar el envío a Telegram:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error interno del servidor al procesar la solicitud.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
