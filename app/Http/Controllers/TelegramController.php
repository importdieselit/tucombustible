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
    
    protected $telegramService;
    
    protected $botpressUrl = 'https://api.botpress.cloud/v1/bots/bf3ba980-bc5e-40fb-8029-88f9ec975e39/events'; 
    protected $botpressToken = '8278356133:AAFbPIiY77YEdFbRoO8JSpF83UKaSM2X-dM'; 

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
        // 1. Verificar si hay un mensaje de texto
        $update = $request->all();
        Log::info($update);
        if (isset($update['message']['text'])) {
            $text = $update['message']['text'];
            $chat_id = $update['message']['chat']['id'];
            $user_id = $update['message']['from']['id'] ?? null;

            // 2. Intentar procesar con lógica local (Prioridad 1)
            $localResponse = $this->processLocalPatterns($text);

            if ($localResponse) {
                // Si la respuesta local existe (el patrón coincidió), enviarla directamente.
                $this->telegramService->sendMessage($chat_id, $localResponse, 'Markdown');
            } else {
                // 3. Fallback: Si no coincide con ningún patrón local, delegar a Botpress (Prioridad 2)
                $botpressResponse = $this->sendToBotpress($text,$chat_id, $user_id);

                if ($botpressResponse) {
                    // Procesar y enviar la respuesta de Botpress
                    $this->telegramService->sendMessage($chat_id, $botpressResponse, 'HTML');
                } else {
                    // Mensaje de fallback final (si Botpress también falla o no responde)
                    $this->telegramService->sendMessage($chat_id, "Lo siento, no pude procesar tu solicitud ni a través de mis patrones ni con Botpress. Por favor, revisa el formato.");
                }
            }

        } else if (isset($update['message']['photo'])) {
            // Manejar mensajes con fotos si es necesario
            $this->telegramService->sendMessage($update['message']['chat']['id'], "Gracias por la foto, pero solo proceso mensajes de texto.", 'Markdown');
        }

        return response('OK', 200);
    }



     private function processLocalPatterns(string $text): ?string
    {
        $text = trim($text);

        // 1. Patrón de ayuda o bienvenida
        if (strtolower($text) === '/start' || strtolower($text) === 'holaBot') {
            return "¡Hola! Soy el Bot de Reporte de Despachos. Mi formato de reporte es:\n"
                 . "`abastecio unidad de [NOMBRE CLIENTE] con el surtidor tanque [SERIAL TANQUE] [CANTIDAD] litros`\n"
                 . "Ejemplo: `abastecio unidad de Transportes C.A. con el surtidor tanque TQ001 500 litros`";
        }
        
        // 2. Patrón de Despacho (Expresión Regular más robusta para capturar los datos)
        $pattern = '/^\/?abasteci[oó] unidad de (.+?) con el surtidor del? tanque (.+?) (\d+) litros/iu';

        if (preg_match($pattern, $text, $matches)) {
            // Lógica de Despacho
            list(, $cliente_txt, $tanque_txt, $cantidad_txt) = $matches;

            // Normalizar cantidad a formato decimal (reemplazar coma por punto)
            $cantidad = (float)str_replace(',', '.', $cantidad_txt);
            
            // Buscar Cliente (usando LIKE por si hay variaciones)
            $cliente = Cliente::where('nombre', 'LIKE', "%{$cliente_txt}%")->first();
            $tanque = Deposito::where('serial', $tanque_txt)->first();

            $otro_cliente = $cliente ? null : $cliente_txt;

            if (!$tanque) {
                return "❌ Error: No se encontró un depósito con el serial: **{$tanque_txt}**.";
            }

            // Lógica de validación de stock
            if ($tanque->stock < $cantidad) {
                // Usar $tanque->stock en lugar de nivel_alerta_litros para el stock real
                return "⚠️ Aviso: El tanque **{$tanque->serial}** solo tiene {$tanque->stock} litros disponibles. No se pudo realizar el despacho de {$cantidad}L.";
            }

            // Realizar la resta del stock (DESCOMENTAR EN PRODUCCIÓN)
            // $tanque->stock -= $cantidad;
            // $tanque->save();
            $nombre = $cliente->nombre ?? $otro_cliente;

            // 3. Respuesta de Éxito
            return "✅ **Despacho Registrado**:\n"
                 . "Cliente: **{$nombre}**\n"
                 . "Tanque: **{$tanque->serial}**\n"
                 . "Cantidad Despachada: **{$cantidad}** litros.";
        }
        
        $aforo_header_pattern = '/^(?:Aforo inicial inventario área de almacenamiento Impordiesel:\s*\n)?/iu';
        $aforo_detail_pattern = '/(Tanque\s*\d+)\s+DSL\s*\n\s*([\d,]+)\s+cm\s*=\s*([\d,]+)\s+lts\./iu';

        // Primero, verificamos si el mensaje tiene la estructura general de Aforo
        if (preg_match($aforo_header_pattern, $text) && preg_match($aforo_detail_pattern, $text)) {
            Log::info('actualizacion de aforo');
            
            $success_messages = [];
            $error_messages = [];

            // Extraemos todos los detalles de los tanques
            if (preg_match_all($aforo_detail_pattern, $text, $matches, PREG_SET_ORDER)) {
                
                foreach ($matches as $match) {
                    $serial_txt = trim($match[1]); // e.g., 'Tanque 1'
                    $cm_txt = trim($match[2]);     // e.g., '224,5'
                    $litros_txt = trim($match[3]); // e.g., '36683,08'

                    // Normalizar cantidad a formato decimal (reemplazar coma por punto para PHP)
                    $litros = (float)str_replace(',', '.', $litros_txt);
                    $cm = (float)str_replace(',', '.', $cm_txt);
                    
                    // Buscar Tanque en DB
                    $tanque = Deposito::where('serial', $serial_txt)->first();

                    if ($tanque) {
                        // Lógica de Actualización: Stock y Nivel en CM
                        // DESCOMENTAR EN PRODUCCIÓN:
                        $tanque->nivel_actual_litros = $litros; 
                        //$tanque->nivel_cm = $cm;
                        $tanque->save(); 
                        
                        $success_messages[] = "Tanque **{$serial_txt}** actualizado a **{$litros_txt}** Lts ({$cm_txt} cm).";

                    } else {
                        $error_messages[] = "Tanque **{$serial_txt}**: No se encontró un depósito con ese serial.";
                    }
                }

                if (!empty($success_messages)) {
                    // Construir la respuesta final de éxito
                    $response = "✅ **Aforo Inicial Registrado y Stock Actualizado**:\n"
                        . implode("\n", $success_messages);
                    
                    if (!empty($error_messages)) {
                        $response .= "\n\n⚠️ Errores encontrados:\n" . implode("\n", $error_messages);
                    }
                    
                    // Se puede añadir un mensaje para los lts disponibles para la venta si se desea capturarlo
                    if (preg_match('/Disponibles para la venta = ([\d,]+) lts/iu', $text, $disponibles_match)) {
                    $response .= "\n\n(Total disponible en el reporte: {$disponibles_match[1]} lts)";
                    }

                    return $response;
                }
            }
        }
        


        
        // Si no coincide con ningún patrón conocido, retorna null
        return null;
    }


    

     private function sendToBotpress(string $message,$chatId, $userId): ?string
    {
        Log::info("Delegando a Botpress: " . $message);

        
        
        $payload = [
                'type' => 'text', 
                'text' => $message,
                'source' => 'api',
                'channel' => 'telegram',
                'direction' => 'incoming',
                'payload' => [
                    'text' => $message,
                    // Aquí se adjunta la data que tu nodo de código debe leer.
                    'laravel_metadata' => [ 
                        'source_app' => 'Laravel-Telegram-Proxy',
                        // Puedes adjuntar cualquier otra data que necesites:
                    ]
                ],
                'target' => (string) $chatId, // El usuario de Telegram
                'target_type' => 'user',
                'target_channel' => 'telegram' 
            ];
    
        try {

            Log::info("enviado a botpress:  ".json_encode($payload));
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botpressToken,
                'Content-Type' => 'application/json',
            ])->post($this->botpressUrl, $payload);
            
            Log::info("Respuesta de Botpress (Status {$response->status()}): " . $response->body());
    
            if ($response->successful()) {
                $body = $response->json();
                
                // Botpress a menudo devuelve una lista de respuestas. Buscamos la primera de tipo 'text'.
                $reply = collect($body['responses'] ?? [])->firstWhere('type', 'text');
    
                return $reply['text'] ?? "Recibí una respuesta de Botpress, pero no contenía texto legible.";

            } else {
                Log::error("Error al enviar a Botpress. Estado: " . $response->status() . " Body: " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Excepción al comunicarse con Botpress: " . $e->getMessage());
            return null;
        }
    }

    public function handleWebhook_old(Request $request)
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
        
        $botpressUrl = 'https://api.botpress.cloud/v1/bots/bf3ba980-bc5e-40fb-8029-88f9ec975e39/events'; 
        $botpressToken = '8278356133:AAFbPIiY77YEdFbRoO8JSpF83UKaSM2X-dM'; 

        try {
            // ----------------------------------------------------------------------
            // ESTRUCTURA DE EVENTO REQUERIDA POR LA API DE BOTPRESS
            // ----------------------------------------------------------------------
            $payload = [
                'type' => 'text', 
                'text' => $text,
                'source' => 'api',
                'channel' => 'telegram',
                'direction' => 'incoming',
                'payload' => [
                    'text' => $text,
                    // Aquí se adjunta la data que tu nodo de código debe leer.
                    'laravel_metadata' => [ 
                        'source_app' => 'Laravel-Telegram-Proxy',
                        // Puedes adjuntar cualquier otra data que necesites:
                    ]
                ],
                'target' => (string) $chatId, // El usuario de Telegram
                'target_type' => 'user',
                'target_channel' => 'telegram' 
            ];
            
            Log::info('enviando a botpress:  '.json_encode($payload));

            $botpressResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $botpressToken,
                'Content-Type' => 'application/json',
            ])->post($botpressUrl, $payload)->json();
            
            Log::info('recibiendo: '.$botpressResponse);
            
            // ----------------------------------------------------
            // PASO 2: Procesar la respuesta de Botpress
            // ----------------------------------------------------
            
            $finalResponseText = "Lo siento, la IA no devolvió un mensaje claro.";
            $actionData = null; 

            // NOTA: La estructura de la respuesta de Botpress varía. 
            // Debes ajustarla a la estructura real que te devuelva.
            if (isset($botpressResponse['responses'][0]['text'])) {
                $finalResponseText = $botpressResponse['responses'][0]['text'];
            }

            // Si Botpress incluyó una "Acción" o "Metadata"
            if (isset($botpressResponse['action_data'])) {
                $actionData = $botpressResponse['action_data'];
                
                // Lógica para ejecutar la acción de DB basada en Botpress
                if ($actionData['intent'] === 'INTENT_DESPACHO') {
                    $finalResponseText = $this->processMessage($actionData);
                }
            }
            
            // ----------------------------------------------------
            // PASO 3: Enviar la respuesta final a Telegram
            // ----------------------------------------------------
            Log::info('enviado a botpress'.$finalResponseText);
            // Usamos el servicio inyectado para la respuesta
            $this->processMessage($finalResponseText); 

        } catch (\Exception $e) {
            Log::error('Error de comunicación con Botpress o procesamiento de acción:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'text' => $text
            ]);
            
            $errorMessage = "⚠️ Ocurrió un error al conectar con el sistema de IA.";
            $this->telegramService->sendMessage($errorMessage);
            return response()->json(['status' => 'error'], 500);
        }

        // try {
        //     // 1. Detectar el patrón y ejecutar la acción
        //     $response = $this->processMessage($text);
        //     Log::info($response);

        //     // 2. Enviar respuesta de vuelta al usuario
        //     // Usamos el servicio inyectado ($this->telegramService) para enviar el mensaje,
        //     // ya que el método sendMessage del servicio probablemente acepta (chatId, texto),
        //     // mientras que el método sendMessage de este controlador NO lo hace (espera un Request).
        //     $this->telegramService->sendMessage($response); 
            
        // } catch (\Exception $e) {
        //     // Manejar errores de DB o excepciones en processMessage y registrar
        //     Log::error('Error en el procesamiento del Webhook de Telegram:', [
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString(),
        //         'text' => $text
        //     ]);
            
        //     // Enviar un mensaje de error al usuario por Telegram
        //     $errorMessage = "⚠️ Lo siento, ocurrió un error interno al procesar tu solicitud: {$e->getMessage()}";
        //     $this->telegramService->sendMessage($errorMessage);

        //     return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        // }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Lógica principal para detectar el patrón y actualizar la DB.
     * Ejemplo: "abastecio unidad de Cliente X con el surtidor tanque T1 100 litros"
     */
    protected function processMessage(string $text): string
    {
        // Usar REGEX (Expresiones Regulares) para buscar el patrón específico
        $pattern = '/^\/?abasteci[oó] unidad de (.+?) con el surtidor del? tanque (.+?) (\d+) litros/iu';
        
        if (preg_match($pattern, $text, $matches)) {
            $cliente_txt = trim($matches[1]);
            $tanque_txt = trim($matches[2]);
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
            
            if ($tanque->nivel_alerta_litros < $cantidad) {
                return "⚠️ Aviso: El tanque **{$tanque->serial}** solo tiene {$tanque->nivel_alerta_litros} litros disponibles. No se pudo realizar el despacho de {$cantidad}L.";
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

    function parseInventarioAforoInicial(string $text): array
{
    // Array para almacenar todos los datos extraídos
    $data = [
        'tanques' => [],
        'resguardo_litros' => 0.0,
        'disponible_venta_litros' => 0.0,
        'exito' => false,
    ];

    // Función auxiliar para limpiar la cadena de números (reemplaza coma por punto y convierte a float)
    $cleanValue = function (string $value): float {
        return (float)str_replace(',', '.', str_replace('.', '', $value)); // Maneja el formato de miles y decimales
    };
    
    // --- 1. EXTRACCIÓN DE DATOS DE TANQUES ---
    // Patrón: Tanque N DSL [CM] cm = [LITROS] lts.
    // Grupos de captura: 1=Número de tanque, 2=CM, 3=Litros
    $patternTanques = '/Tanque\s+(\d+)\s+DSL\s*([\d\.,]+)\s*cm\s*=\s*([\d\.,]+)\s*lts\./i';

    if (preg_match_all($patternTanques, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $data['tanques'][] = [
                'tanque_id' => (int)$match[1],
                'cm' => $cleanValue($match[2]),
                'litros' => $cleanValue($match[3]),
            ];
        }
    }

    // --- 2. EXTRACCIÓN DE VOLUMEN EN RESGUARDO ---
    // Patrón: En resguardo del Ministerio de agricultura y tierras [LITROS] lts.
    // Grupo de captura: 1=Litros
    $patternResguardo = '/En\s+resguardo.*?tierras\s*([\d\.,]+)\s*lts\./si';
    
    if (preg_match($patternResguardo, $text, $matchResguardo)) {
        $data['resguardo_litros'] = $cleanValue($matchResguardo[1]);
    }

    // --- 3. EXTRACCIÓN DE VOLUMEN DISPONIBLE PARA LA VENTA ---
    // Patrón: Disponibles para la venta = [LITROS] lts.
    // Grupo de captura: 1=Litros
    $patternDisponible = '/Disponibles\s+para\s+la\s+venta\s*=\s*([\d\.,]+)\s*lts\./si';

    if (preg_match($patternDisponible, $text, $matchDisponible)) {
        $data['disponible_venta_litros'] = $cleanValue($matchDisponible[1]);
    }
    
    // Si se extrajo algo, consideramos que fue un éxito
    if (!empty($data['tanques']) || $data['resguardo_litros'] > 0 || $data['disponible_venta_litros'] > 0) {
        $data['exito'] = true;
    }

    return $data;
}

    /**
     * Webhook para el Bot de Logística (8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po)
     */
 public function handleLogisticaWebhook(Request $request)
    {
        Log::info('Webhook de Logística recibido:', $request->all());
        try {
            $update = $request->all();
            $logisticaToken = '8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po';
        if (isset($update['message'])) {
            // El chat_id es donde responderemos (puede ser el grupo o el privado)
            $chatId = $update['message']['chat']['id']; 
            $from = $update['message']['from'];
            $userId = $from['id']; 
            $userName = ($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '');
            $text = $update['message']['text'] ?? '';

            Log::info("Bot Logística - Procesando mensaje", [
                'origen_chat_id' => $chatId,
                'remitente_user_id' => $userId,
                'texto' => $text
            ]);

            // Lógica de Vinculación
            if (str_contains(strtolower($text), '/vincular')) {
                $identificador = trim(str_ireplace('/vincular', '', $text));
                
                if (empty($identificador)) {
                    $this->sendSimpleMessage($chatId, "⚠️ *Error de formato*\nUsa: `/vincular nombre_usuario`", $logisticaToken);
                    return response('OK', 200);
                }

                // Búsqueda en DB
                $user = \App\Models\User::where('name', 'LIKE', "%{$identificador}%")->first();
                
                if ($user) {
                    try {
                        $user->update(['telegram_id' => $userId]);
                        
                        $msg = "✅ *Vinculación Exitosa*\n\n"
                             . "👤 *Usuario:* {$user->name}\n"
                             . "🆔 *Telegram ID:* `{$userId}`\n"
                             . "💬 *Origen:* " . ($chatId < 0 ? "Grupo" : "Privado");
                        
                        $this->sendSimpleMessage($chatId, $msg, $logisticaToken);
                        
                    } catch (\Exception $dbEx) {
                        Log::error("Error al actualizar telegram_id: " . $dbEx->getMessage());
                        $this->sendSimpleMessage($chatId, "❌ *Error interno*: No se pudo guardar la vinculación en la base de datos.", $logisticaToken);
                    }
                } else {
                    $this->sendSimpleMessage($chatId, "🔍 *No encontrado*\nNo existe un usuario con el nombre: *{$identificador}*", $logisticaToken);
                }
            }
        }

        return response('OK', 200);

        } catch (\Exception $e) {
            Log::error("Error Crítico en Webhook Logística:", [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('OK', 200); 
        }
    }

    /**
     * Método auxiliar para enviar mensajes usando un token específico
     */
    private function sendSimpleMessage($chatId, $text, $token)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);
    }
}
