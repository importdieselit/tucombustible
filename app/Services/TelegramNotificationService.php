<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;


/**
 * Clase de Servicio para manejar las notificaciones a través de la API de Telegram.
 * Encapsula la lógica de comunicación con la API.
 */
class TelegramNotificationService
{
    protected $botToken;
    protected $chatId;
    protected $apiUrlBase = 'https://api.telegram.org/bot';

    public function __construct()
    {
        // Obtener credenciales desde el archivo .env (PRÁCTICA SUGERIDA)
        // Requerirá que configures TELEGRAM_BOT_TOKEN y TELEGRAM_CHAT_ID en tu .env
        $this->botToken = env('TELEGRAM_BOT_TOKEN', '8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po');
        $this->chatId = env('TELEGRAM_CHAT_ID', '-1002935486238');
    }

    /**
     * Envía un mensaje de texto simple a un chat de Telegram.
     * * @param string $message El texto del mensaje.
     * @param string|null $overrideChatId ID del chat de destino (si se necesita un chat diferente al predeterminado).
     * @return bool Retorna true si el envío fue exitoso, false en caso contrario.
     */
    public function sendMessage(string $message, string $overrideChatId = null): bool
    {
        $targetChatId = $overrideChatId ?? $this->chatId;

        // if ($this->botToken === '8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po' || $targetChatId === '-1002935486238') {
        //      Log::error('Error de Configuración de Telegram: Token o Chat ID no configurados en .env.');
        //      return false;
        // }

        $apiUrl = "{$this->apiUrlBase}{$this->botToken}/sendMessage";
          $safeMessage = $this->escapeMarkdownV2($message);
        
        try {
            $response = Http::post($apiUrl, [
                'chat_id' => $targetChatId,
                'text' => $safeMessage,
                'parse_mode' => 'MarkdownV2', // Permite formato enriquecido
            ]);

            if ($response->successful() && $response->json('ok') === true) {
                Log::info("Mensaje de texto enviado a Telegram con éxito. Chat ID: {$targetChatId}");
                return true;
            } else {
                Log::error('Error al enviar mensaje de texto a Telegram:', [
                    'response_body' => $response->body(),
                    'status' => $response->status()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Excepción al enviar texto a Telegram:', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    // Aquí puedes añadir más métodos, como sendPhoto, usando el mismo principio
    /**
     * Envía una foto a un chat de Telegram.
     * public function sendPhoto(string $filePath, string $caption = null, string $overrideChatId = null): bool { ... }
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

       /* Escapa todos los caracteres especiales usados en MarkdownV2 de Telegram.
     * Necesario para prevenir el error 'Bad Request: can't parse entities'.
     * Fuente: https://core.telegram.org/bots/api#markdownv2-style
     *
     * @param string $text El texto original con formato (o sin formato)
     * @return string El texto con los caracteres especiales escapados.
     */
    private function escapeMarkdownV2(string $text): string
    {
        // Lista de caracteres especiales que deben ser escapados en MarkdownV2
        // Los caracteres ya escapados (con un '\' delante) no se tocan.
        $reserved = [
            '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', 
            '-', '=', '|', '{', '}', '.', '!'
        ];

        // Usamos una función anónima con preg_replace_callback para manejar las excepciones
        $safeText = preg_replace_callback('/([_*\[\]()~`>#+\-=|{}.!])/', function ($matches) {
            // El primer elemento de $matches[1] es el carácter reservado encontrado.
            return '\\' . $matches[1];
        }, $text);

        return $safeText;
    }
}
