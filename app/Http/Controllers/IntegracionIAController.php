<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Importa tus modelos de negocio (Ejemplo)
use App\Models\Cliente;
use App\Models\Solicitud;
use App\Models\Conductor;

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
        // El cuerpo del request (payload) de Botpress puede variar. 
        // Es común usar un campo 'action' o 'function' para identificar la tarea.
        $action = $request->input('action');
        $userId = $request->input('userId'); 

        Log::info("Webhook recibido. Acción: {$action}, Usuario: {$userId}");

        if (!$action) {
            return response()->json(['success' => false, 'message' => 'Acción no especificada.'], 400);
        }

        // Ejecutar la función correspondiente basada en la acción
        return match ($action) {
            'consultarCupo'       => $this->consultarCupo($request),
            'crearSolicitud'      => $this->crearSolicitud($request),
            'confirmarRecepcion'  => $this->confirmarRecepcion($request),
            'reportarFalla'       => $this->reportarFalla($request),
            'aprobarSolicitud'    => $this->aprobarSolicitud($request),
            'identificarCliente' => $this->identificarClientePorTelefono($request),
            default               => response()->json(['success' => false, 'message' => 'Acción no soportada.'], 404),
        };
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
        $telefono = $request->input('telefono');
        
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
                              ->orWhere('telefono2', $telefonoLimpio) // Busca en campos secundarios por robustez
                              ->first();

            if ($cliente) {
                // Éxito: Cliente encontrado
                $mensajeBienvenida = "Hola, {$cliente->nombre_contacto}. Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?";
                
                return response()->json([
                    'success' => true,
                    'response' => $mensajeBienvenida,
                    'data' => [
                        'clienteEncontrado' => true,
                        'clienteId' => $cliente->id,
                        'nombreCliente' => $cliente->nombre_contacto ?? $cliente->razon_social,
                        // Puedes incluir el cupo disponible de una vez para un saludo más personalizado
                        'cupoDisponible' => $cliente->cupo_disponible ?? 0
                    ]
                ]);
            } else {
                // Fallo: No se encontró el cliente
                return response()->json([
                    'success' => true, // La llamada a la API tuvo éxito, pero el cliente no fue encontrado
                    'response' => 'No se encontró ningún cliente asociado a ese número. Por favor, verifica el número o habla con un agente.',
                    'data' => ['clienteEncontrado' => false]
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error de identificación por teléfono: " . $e->getMessage());
            return response()->json(['success' => false, 'response' => 'Error interno al buscar el cliente.'], 500);
        }
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
}