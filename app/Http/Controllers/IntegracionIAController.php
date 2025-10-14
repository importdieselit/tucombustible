<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Importa tus modelos de negocio (Ejemplo)
use App\Models\Cliente;
use App\Models\User;
use App\Models\Persona;
use App\Models\Solicitud;
use App\Models\Conductor;
use App\Models\Pedido;
use App\Models\Deposito;
use App\Models\Aforo;

class IntegracionIAController extends Controller
{
    /**
     * Endpoint base para recibir webhooks de Botpress.
     * Siempre devuelve una respuesta JSON.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
   public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        // 1. Verificar si el payload es un lote (array de acciones)
        if (is_array($payload) && !empty($payload) && isset($payload[0]['action'])) {
            $results = [];
            foreach ($payload as $item) {
                $action = $item['action'] ?? null;
                if ($action) {
                    // Ejecuta cada acción en el lote
                    $results[] = $this->executeAction($action, $item);
                }
            }
            // Devuelve un arreglo JSON con los resultados de todas las acciones
            return response()->json($results);

        } else {
            $action = $request->input('action');
            $data = $request->all(); // Pasa todo el request
            
            // Ejecutar la acción única
            $result = $this->executeAction($action, $data);

            // Si es una acción única, no se envuelve en un array
            return response()->json($result);
        }
    }

    /**
     * Método auxiliar para ejecutar una acción individual dentro del lote.
     * @param string $action Nombre de la acción a ejecutar.
     * @param array $data Datos del payload para esta acción.
     * @return array Resultado de la ejecución (para ser incluido en el JSON final).
     */
    protected function executeAction($action, $data)
    {
        // Crea un nuevo objeto Request con los datos de la acción actual.
        // Esto permite que los métodos existentes ($this->metodo($request)) funcionen.
        $request = new Request($data);
        
        // Asume que la ID de usuario o administrador se puede encontrar en 'userId' o dentro de 'params'
        $userId = $data['userId'] ?? $data['params']['admin_id'] ?? 'N/A';
        Log::info("Ejecutando Acción: {$action}, Usuario: {$userId}");

        if (!$action) {
            return ['success' => false, 'message' => 'Acción no especificada en el item del lote.'];
        }

        // Mapeo de acciones a métodos de clase
        // Se usa ->getData(true) para extraer el contenido del JsonResponse como un array PHP
        return match ($action) {
            'identificarPorTelegram'    => $this->identificarPorTelegram($request)->getData(true), 
            'consultarCupo'             => $this->consultarCupo($request)->getData(true),
            'crearSolicitud'            => $this->crearSolicitud($request)->getData(true),
            'confirmarRecepcion'        => $this->confirmarRecepcion($request)->getData(true),
            'reportarFalla'             => $this->reportarFalla($request)->getData(true),
            'aprobarSolicitud'          => $this->aprobarSolicitud($request)->getData(true),
            'identificarCliente'        => $this->identificarClientePorTelefono($request)->getData(true),
            'ajustarNivelTanque'        => $this->ajustarNivelTanque($request)->getData(true), 
            'aprobarEliminacionUsuario' => $this->aprobarEliminacionUsuario($request)->getData(true), 
            
            default                     => ['success' => false, 'message' => 'Acción no soportada: ' . $action],
        };
    }

    public function identificarPorTelegram(Request $request)
    {
        $telegramId = $request->input('telegramId');
        $contacto = $request->input('contacto'); 

        // Inicializar la respuesta base
        $response = [
            'success' => false,
            'action' => $request->action,
            'data' => [
                'clienteEncontrado' => false,
                'userEncontrado' => false,
                'perfil' => null,
                'clienteId' => null,
                'nombreCliente' => null,
                'cupo' => null,
                'disponible' => null,
            ],
            'response' => 'Error de identificación.'
        ];

        // ==========================================================
        // ETAPA 1: BÚSQUEDA CANÓNICA POR USER (Por telegram_id)
        // ==========================================================
        Log::info('valida telegramid: '.$telegramId);
        Log::info('valida telegramid: '.$request);

        $user = User::where('telegram_id', $telegramId)
                    ->first();

        if ($user) {
            // El usuario ya existe, tiene un perfil definido (3 o !=3). Se devuelve la información.
            return $this->handleUserFound($user, $response);
        }

        $cliente = Cliente::where('telegram_id', $telegramId)
                    ->first();

        if ($cliente) {
            $user=User::where('cliente_id',$cliente->id)->first();
            // El usuario ya existe, tiene un perfil definido (3 o !=3). Se devuelve la información.
            return $this->handleUserFound($user, $response);
        }

        // ==========================================================
        // ETAPA 2: BÚSQUEDA POR CONTACTO Y VALIDACIÓN DE CLIENTE
        // ==========================================================
        if ($contacto) {
            $contactoNormalizado = strtolower(trim($contacto));

            // A) Buscar la Persona
            $persona = Persona::where('telefono', $contactoNormalizado)
                              ->orWhere('correo', $contactoNormalizado) 
                              ->first();

            if ($persona) {
                // B) VALIDACIÓN CRÍTICA: ¿Es esta persona un Cliente?
                $cliente = Cliente::where('id_persona', $persona->id)->first();
                
                if ($cliente) {
                    // C) ES UN CLIENTE VÁLIDO. Ahora asegurar que tenga un registro en User.
                    $user = User::with('cliente', 'persona')
                                ->where('id_persona', $persona->id)
                                ->first();

                    if ($user) {
                        // Cliente con User existente: Actualizamos el telegram_id y asignamos perfil 3 (si no lo tenía)
                        $user->telegram_id = $telegramId;
                        // Forzamos el id_perfil a 3 si el User ya existía pero lo tenía diferente.
                        if ($user->id_perfil !== 3) {
                             $user->id_perfil = 3;
                        }
                        $user->save();
                        return $this->handleUserFound($user, $response);
                    } else {
                        // Cliente sin User: Lo creamos con perfil 3
                        $newUser = User::create([
                            'id_persona' => $persona->id,
                            'id_perfil' => 3, // Perfil de Cliente
                            'telegram_id' => $telegramId,
                            'email' => $persona->correo ?? "telegram_{$telegramId}@placeholder.com",
                            'password' => Hash::make('combustible123'),
                        ]);
                        $newUser->load('cliente', 'persona');
                        return $this->handleUserFound($newUser, $response);
                    }
                }
                
                // Si la Persona existe pero NO es Cliente (Lógica de rechazo de contacto)
            }
            
            // Falla la búsqueda por contacto o persona no es cliente
            $response['response'] = 'El contacto proporcionado no está asociado a una cuenta activa de cliente.';
            return response()->json($response);
        }

        // ==========================================================
        // ETAPA 3: FALLO FINAL DE IDENTIFICACIÓN
        // ==========================================================
        $response['response'] = 'No se pudo identificar. Por favor, proporcione su número de teléfono o correo.';
        return response()->json($response);
    }
    
    // ==========================================================
    // MÉTODO AUXILIAR: Manejar User Encontrado (Se mantiene igual)
    // ==========================================================
    private function handleUserFound($user, $response)
    {
        // Asegurar que las relaciones estén cargadas
        if (!$user->relationLoaded('persona')) {
             $user->load('persona', 'cliente');
        }

        $response['success'] = true;
        $response['data']['userEncontrado'] = true;
        $response['data']['perfil'] = $user->id_perfil;
        
        // Asignación de Funciones basada en el Perfil
        if ($user->id_perfil == 3) {
            // PERFIL CLIENTE (Datos de cliente)
            $cliente = $user->cliente;
            $response['data']['clienteEncontrado'] = true;
            $response['data']['clienteId'] = $cliente->id ?? null;
            $response['data']['nombreCliente'] = $user->persona->nombre ?? 'Cliente';
            $response['data']['cupo'] = $cliente->cupo ?? 0;
            $response['data']['disponible'] = $cliente->disponible ?? 0;
            $response['response'] = "Bienvenido, {$response['data']['nombreCliente']}. Eres un Cliente.";
        } else {
            // PERFIL ADMINISTRATIVO / SISTEMAS (Funciones amplias)
            $response['data']['nombreCliente'] = $user->persona->nombre ?? 'Usuario de Sistema';
            $response['response'] = "Bienvenido, {$response['data']['nombreCliente']}. Eres un Usuario Administrativo.";
        }

        return response()->json($response);
    }
    
   
    // --- FUNCIONES DE LÓGICA DE NEGOCIO ---

    /**
     * Cliente consulta cupo disponible.
     */
    protected function consultarCupo(Request $request)
    {
        $clienteId = $request->input('clienteId');
        
        if (!$clienteId) {
            return response()->json(['success' => false, 'response' => 'ID de cliente requerido.'], 400);
        }

        try {
            $cliente = Cliente::findOrFail($clienteId);
            $cupo = $cliente->cupo_disponible; // Asumiendo que el modelo tiene este campo

            return response()->json([
                'success' => true,
                'response' => "El cupo disponible es de {$cupo} unidades.",
                'data' => ['cupo' => $cupo]
            ]);
        } catch (\Exception $e) {
            Log::error("Error al consultar cupo: " . $e->getMessage());
            return response()->json(['success' => false, 'response' => 'No se pudo consultar el cupo.']);
        }
    }

     protected function identificarClientePorTelefono(Request $request)
    {
        // Botpress debe extraer el número y enviarlo en el payload
        $telefono = $request->telefono;
        $telegramId=$request->telegramId ?? null;
        // Limpia el número: elimina espacios, guiones, y el código de país (si aplica)
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
        
        if (empty($telefonoLimpio)) {
            return response()->json([
                'success' => false, 
                'response' => 'Número de teléfono no válido o no detectado.', 
                'data' => ['clienteEncontrado' => false]
            ], 400);
        }

        try {
            // Asumimos que tu modelo Cliente tiene un campo 'telefono'
            $cliente = Cliente::where('telefono', $telefonoLimpio)
                              ->first();

            if ($cliente) {
                if(!is_null($telegramId)){
                    $cliente->telegram_id = $telegramId;
                    $cliente->save();
                }
                Log::info('cliente encontrado');
                // Éxito: Cliente encontrado
                $mensajeBienvenida = "Hola, {$cliente->contacto}. ¿En qué puedo ayudarte hoy?";
                
                return response()->json([
                    'success' => true,
                    'response' => $mensajeBienvenida,
                    'data' => [
                        'clienteEncontrado' => true,
                        'clienteId' => $cliente->id,
                        'nombreCliente' => $cliente->contacto ?? $cliente->nombre,
                        // Puedes incluir el cupo disponible de una vez para un saludo más personalizado
                        'cupo' => $cliente->cupo ?? 0,
                        'perfil' => 3,
                        'disponible' => $cliente->disponible ?? 0
                    ]
                ]);
            } else {
                $persona = Persona::where('telefono',$telefonoLimpio)->first();
                if($persona){
                    Log::info('persona encontrada '.$persona);
               
                    $user=User::where('id_persona', $persona->id)->first();
                    Log::info($user);
            // Asignación de Funciones basada en el Perfil
                    if ($user->id_perfil == 3) {
                        // PERFIL CLIENTE (Datos de cliente)
                        $cliente = $user->cliente;

                        $response['success'] = true;
                        $response['data']['userEncontrado'] = true;
                                    
                        $response['data']['clienteEncontrado'] = true;
                        $response['data']['clienteId'] = $cliente->id ?? null;
                        $response['data']['nombreCliente'] = $user->persona->nombre ?? 'Cliente';
                        $response['data']['cupo'] = $cliente->cupo ?? 0;
                        $response['data']['perfil'] = $user->id_perfil;
                        $response['data']['disponible'] = $cliente->disponible ?? 0;
                        $response['response'] = "Bienvenido, {$response['data']['nombreCliente']}. Eres un Cliente.";
                    } else {

                        $response['success'] = true;
                        $response['data']['userEncontrado'] = true;
                        
                        // PERFIL ADMINISTRATIVO / SISTEMAS (Funciones amplias)
                        $response['data']['nombreCliente'] = $persona->nombre ?? 'Usuario de Sistema';
                        $response['data']['perfil'] = $user->id_perfil;
                        $response['response'] = "Bienvenido, {$response['data']['nombreCliente']}. Eres un Usuario Administrativo.";
                    }

                    
                }else{
                    // Fallo: No se encontró el cliente
                    return response()->json([
                        'success' => true, // La llamada a la API tuvo éxito, pero el cliente no fue encontrado
                        'response' => 'No se encontró ningún cliente asociado a ese número. Por favor, verifica el número o habla con un agente.',
                        'data' => ['clienteEncontrado' => false]
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error de identificación por teléfono: " . $e->getMessage());
            return response()->json(['success' => false, 'response' => 'Error interno al buscar el cliente.'], 500);
        }
        Log::info('response: '.json_encode($response));
        return response()->json($response);
    }


    /**
     * Cliente crea una nueva solicitud de servicio.
     */
    protected function crearSolicitud(Request $request)
    {
        $clienteId = $request->input('clienteId');
        $cantidad = $request->input('cantidad');
        // ... otros datos como destino, producto, etc.
        
        if (!$clienteId || !$cantidad) {
            return response()->json(['success' => false, 'response' => 'Datos incompletos para la solicitud.'], 400);
        }

        try {
            // 1. Verificar cupo (Lógica de negocio)
            $cliente = Cliente::findOrFail($clienteId);
            if ($cantidad > $cliente->cupo_disponible) {
                 return response()->json(['success' => false, 'response' => 'Cantidad excede el cupo disponible.']);
            }
            
            // 2. Crear la solicitud
            $solicitud = Pedido::create([
                'id_cliente' => $clienteId,
                'cantidad' => $cantidad,
                'estado' => 'Pendiente Aprobación' 
                // ... otros campos
            ]);

            // 3. Notificar al administrador o sistema de aprobación (opcional)

            return response()->json([
                'success' => true,
                'response' => "Solicitud #{$solicitud->id} creada exitosamente. Esperando aprobación.",
                'data' => ['solicitudId' => $solicitud->id]
            ]);
        } catch (\Exception $e) {
            Log::error("Error al crear solicitud: " . $e->getMessage());
            return response()->json(['success' => false, 'response' => 'Ocurrió un error al procesar la solicitud.']);
        }
    }
    
    /**
     * Cliente confirma la recepción del servicio/producto.
     */
    protected function confirmarRecepcion(Request $request)
    {
        $solicitudId = $request->input('solicitudId');
        
        if (!$solicitudId) {
            return response()->json(['success' => false, 'response' => 'ID de solicitud requerido.'], 400);
        }

        try {
            $solicitud = Pedido::findOrFail($solicitudId);
            
            // 1. Lógica de negocio: Cambiar estado
            $solicitud->estado = 'Recibida por Cliente';
            $solicitud->fecha_recepcion = now();
            $solicitud->save();
            
            // 2. Lógica de negocio: Actualizar inventario/facturación (opcional)

            return response()->json([
                'success' => true,
                'response' => "Recepción de solicitud #{$solicitudId} confirmada.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'response' => 'Error al confirmar recepción.']);
        }
    }

    /**
     * Conductor reporta una falla o incidente.
     */
    protected function reportarFalla(Request $request)
    {
        $conductorId = $request->input('conductorId');
        $descripcion = $request->input('descripcion');
        // ... datos de ubicación, vehículo, etc.

        if (!$conductorId || !$descripcion) {
            return response()->json(['success' => false, 'response' => 'Datos de falla incompletos.'], 400);
        }
        
        try {
            // Lógica de negocio: Guardar el reporte en una tabla de incidentes
            // $incidente = Incidente::create([...]);
            
            // Lógica de notificación al administrador (ej. envío de email o alerta)

            return response()->json([
                'success' => true,
                'response' => "Falla reportada por el conductor ID {$conductorId}. El incidente será atendido.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'response' => 'Error al guardar el reporte de falla.']);
        }
    }
    
    /**
     * Administrador aprueba una solicitud (puede ser llamado por el propio Botpress 
     * o por un sistema interno que Botpress monitorea).
     */
    protected function aprobarSolicitud(Request $request)
    {
        $solicitudId = $request->input('solicitudId');
        $adminId = $request->input('adminId'); // Opcional, si Botpress lo sabe
        
        if (!$solicitudId) {
            return response()->json(['success' => false, 'response' => 'ID de solicitud requerido.'], 400);
        }

        try {
            $solicitud = Pedido::findOrFail($solicitudId);
            
            // 1. Lógica de negocio: Cambiar estado
            $solicitud->estado = 'Aprobada';
            $solicitud->id_administrador = $adminId;
            $solicitud->save();
            
            // 2. Notificar a cliente (Función clave que el IA necesita)
            // Aquí iría la lógica para enviar la notificación de vuelta al cliente
            $cliente = $solicitud->cliente; // Asumiendo relación Eloquent
            $mensaje = "Tu solicitud #{$solicitudId} ha sido aprobada y está en proceso de envío.";
            
            // La IA usará esta respuesta para continuar la conversación con el cliente.
            return response()->json([
                'success' => true,
                'response' => $mensaje,
                'data' => ['notificarA' => $cliente->id, 'mensaje' => $mensaje]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'response' => 'Error al aprobar la solicitud.']);
        }
    }


    protected function ajustarNivelTanque(Request $request)
    {
        $adminId = $request->input('admin_id');
        $tanqueId = $request->input('tanque_id');
        $nuevoNivelCm = $request->input('nuevo_nivel_cm');
        $nuevoNivelitros = $request->input('nuevo_nivel_litros');
        Log::info('inicia ajuste');
        Log::info('adminId: '.$adminId);
        Log::info('tanqueId: '.$tanqueId);
        Log::info('nuevoNivelCm: '.$nuevoNivelCm);
        Log::info('nuevoNivelitros: '.$nuevoNivelitros);
        
        // 1. **VALIDACIÓN DE DATOS BÁSICOS**
        // if (!$tanqueId || !is_numeric($nuevoNivelCm)) {
        //     return response()->json([
        //         'success' => false, 
        //         'response' => 'Faltan parámetros de tanque (ID o Nivel). Por favor, repite el comando completo.'
        //     ]);
        // }

        // 2. **VALIDACIÓN DE PERMISOS (Opcional, pero recomendado)**
        // Puedes verificar el perfil del $adminId aquí si es necesario

        try {
            // 3. **LÓGICA DE NEGOCIO: ENCONTRAR Y ACTUALIZAR**
            $tanque = Deposito::where('serial', $tanqueId)->firstOrFail();
            if(!is_null($nuevoNivelCm)){
                $aforo = Aforo::where('deposito_id',$tanque->id)->where('profundidad_cm',$nuevoNivelCm)->get()->first()->litros;
            }else{
                $aforo=$nuevoNivelitros;
            }

            $tanque->nivel_actual_litros = $aforo;
            $tanque->save();
            
            // 4. **RESPUESTA DE ÉXITO**
            return response()->json([
                'success' => true,
                'response' => "El nivel del Tanque **{$tanque->serial}** ha sido ajustado exitosamente a **{$aforo} Litros**.",
                'data' => ['tanque_id' => $tanqueId]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'response' => "Error: No se encontró ningún tanque con el identificador **{$tanqueId}**."
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'response' => 'Error interno del sistema al intentar ajustar el nivel.'
            ]);
        }
}
}