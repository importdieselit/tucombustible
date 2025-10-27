<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use App\Services\TelegramNotificationService;

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
 // Inyección de dependencias: Laravel automáticamente provee una instancia del servicio
    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }
    
    // --- MÉTODO PARA ENVIAR SOLO TEXTO (HTTP Wrapper) ---

    /**
     * Envía un mensaje de texto simple a Telegram (Endpoint HTTP).
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        // 1. Validación de la solicitud para el texto
        $request->validate([
            'message' => 'required|string|min:1|max:4096', 
        ]);

        $text = $request->input('message');

        // 2. Delegar la tarea al servicio
        $success = $this->telegramService->sendMessage($text);

        // 3. Devolver respuesta
        if ($success) {
            return response()->json(['message' => 'Mensaje enviado a Telegram con éxito.'], 200);
        } else {
            // El servicio ya registró el error, solo devolvemos una respuesta genérica
            return response()->json([
                'message' => 'Error al enviar el mensaje de texto a Telegram. Revise los logs del servidor para más detalles.',
            ], 500);
        }
    }

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

public function handleWebhook(Request $request)
    {
        // La primera línea de log SÍ se debe ejecutar ahora que el 419 está resuelto.
        Log::info('Webhook de Telegram recibido:', $request->all());

        $data = $request->all();
        $message = $data['message'] ?? null;

        // Si no hay mensaje o texto, ignorar
        if (!$message || !isset($message['text'])) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $chatId = $message['chat']['id'];
        $text = $message['text'];

        try {
            // 1. Detectar el patrón y ejecutar la acción
            $response = $this->processMessage($text);
            Log::info($response);

            // 2. Enviar respuesta de vuelta al usuario
            // Usamos el servicio inyectado ($this->telegramService) para enviar el mensaje,
            // ya que el método sendMessage del servicio probablemente acepta (chatId, texto),
            // mientras que el método sendMessage de este controlador NO lo hace (espera un Request).
            $this->telegramService->sendMessage($response); 
            
        } catch (\Exception $e) {
            // Manejar errores de DB o excepciones en processMessage y registrar
            Log::error('Error en el procesamiento del Webhook de Telegram:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'text' => $text
            ]);
            
            // Enviar un mensaje de error al usuario por Telegram
            $errorMessage = "⚠️ Lo siento, ocurrió un error interno al procesar tu solicitud: {$e->getMessage()}";
            $this->telegramService->sendMessage($errorMessage);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Lógica principal para detectar el patrón y actualizar la DB.
     * Ejemplo: "abastecio unidad de Cliente X con el surtidor tanque T1 100 litros"
     */
    protected function processMessage(string $text): string
    {
        // Usar REGEX (Expresiones Regulares) para buscar el patrón específico
        $pattern = '/abastecio unidad de (.+?) con el del surtidor tanque (.+?) (\d+) litros/i';
        
        if (preg_match($pattern, $text, $matches)) {
            $cliente_txt = trim($matches[1]);
            $tanque_txt = trim($matches['2']);
            $cantidad = (int)$matches[3];

            // 1. Búsqueda de cliente y tanque
            $cliente = Cliente::where('nombre','like','%'.$cliente_txt.'%')->first(); // Usar first()
            $tanque = Deposito::where('serial','like',$tanque_txt)->first(); // Usar first()
            
            if (!$cliente) {
                $otro_cliente=$cliente_txt;
            }

            if (!$tanque) {
                return "❌ Error: No se encontró un depósito con el serial: **{$tanque_txt}**.";
            }

            // 2. Lógica de Actualización de la DB
            
            if ($tanque->stock < $cantidad) {
                return "⚠️ Aviso: El tanque **{$tanque->serial}** solo tiene {$tanque->stock} litros disponibles. No se pudo realizar el despacho de {$cantidad}L.";
            }

            // Realizar la resta del stock (Descomenta esto en producción)
            // $tanque->stock -= $cantidad;
            // $tanque->save();
            $nombre=$cliente->nombre?? $otro_cliente;
            // 3. Respuesta de Éxito
            return "✅ **Despacho Registrado**:\n" 
                 . "Cliente: **{$nombre}**\n"
                 . "Tanque: **{$tanque->serial}**\n"
                 . "Cantidad Despachada: **{$cantidad}** litros.";

        } else if (strtolower($text) === '/start' || strtolower($text) === 'hola') {
            return "¡Hola! Soy el Bot de Reporte de Despachos. Mi formato de reporte es:\n"
                 . "`abastecio unidad de [NOMBRE CLIENTE] con el surtidor tanque [SERIAL TANQUE] [CANTIDAD] litros`";
        }
        
        return "Lo siento, no entendí el formato. Por favor, revisa mi mensaje de `/start` para ver el formato correcto.";
    }
}
