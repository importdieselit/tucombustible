<?php

namespace App\Observers;

use App\Models\Viaje;
use App\Models\Vehiculo;
use App\Services\LogisticaInventarioService;
use App\Services\CombustibleService;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\ViajeCreadoNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ViajeObserver
{
    protected $inventarioService;
    protected $combustibleService;
    protected $whatsappService;

    public function __construct(
        LogisticaInventarioService $inventarioService, 
        CombustibleService $combustibleService,
        WhatsappApiService $whatsappService
    ) {
        $this->inventarioService = $inventarioService;
        $this->combustibleService = $combustibleService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Disparado al crear un nuevo viaje.
     */
    public function created(Viaje $viaje): void
    {
        try {
            DB::transaction(function () use ($viaje) {
                $vehiculo = Vehiculo::lockForUpdate()->find($viaje->vehiculo_id);
                
                if ($vehiculo) {
                    $vehiculo->chofer_id = $viaje->chofer_id;

                    // Desacople preventivo y nuevo acople de cisterna
                    if (!is_null($viaje->cisterna)) {
                        Vehiculo::where('acoplado_id', $viaje->cisterna)
                            ->where('id', '!=', $vehiculo->id)
                            ->update(['acoplado_id' => null]);

                        $vehiculo->acoplado_id = $viaje->cisterna;
                    }
                    
                    $vehiculo->save();
                }
            });

            // Envío de notificaciones fuera de la transacción DB
            $this->notificarAsignacionViaje($viaje);

        } catch (Throwable $e) {
            Log::error("Error en ViajeObserver@created para Viaje #{$viaje->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    /**
     * Disparado al actualizar un viaje existente.
     */
    public function updated(Viaje $viaje): void
    {
        // Evaluación correcta post-persistencia
        if (!$viaje->wasChanged('status')) {
            return;
        }

        $viejoStatus = strtoupper((string) $viaje->getOriginal('status'));
        $nuevoStatus = strtoupper((string) $viaje->status);

        try {
            // 1. TRANSICIÓN A "EN RUTA" (Salida Confirmada)
            if ($viejoStatus === 'PROGRAMADO' && $nuevoStatus === 'EN RUTA') {
                
                DB::transaction(function () use ($viaje) {
                    // Sincronizar estatus físico de la unidad y acoplado
                    $this->actualizarEstatusFlota($viaje, 2);

                    // LEDGER AUTOMÁTICO: Única fuente de verdad para el descuento de stock
                    $this->inventarioService->registrarSalidaFisicaDespacho($viaje);
                    if (in_array((int) $viaje->tipo_planificacion, [1, 2], true)) {
                        // 📱 ENVÍO DE WHATSAPP AUTOMÁTICO A CLIENTES Y A LA COORDINADORA
                        $this->notificarDespachoEnRutaWhatsApp($viaje);
                    }
                });

                // Solo aplicará para Planificaciones de MGO (1) o Diésel (2). Fletes (3) y Compras (4) quedan excluidos.
                if (in_array((int) $viaje->tipo_planificacion, [1, 2], true)) {
                    $this->combustibleService->descontarCombustiblePlanificado(
                         $viaje->sede_id,
                         $viaje->litros,
                         $viaje->id
                    );
                }

            // 2. TRANSICIÓN A "COMPLETADO" (Llegada / Descarga)
            } elseif ($nuevoStatus === 'COMPLETADO') {
                
                DB::transaction(function () use ($viaje) {
                    // Liberar acople y cambiar estatus a Disponible (1)
                    Vehiculo::where('id', $viaje->vehiculo_id)->update(['acoplado_id' => null]);
                    $this->actualizarEstatusFlota($viaje, 1);

                    // Si es una Compra de Combustible (Tipo 4), ingresar stock al Ledger
                    if ((int) $viaje->tipo_planificacion === 4) {
                        $this->inventarioService->registrarEntradaCompra($viaje);
                        
                        DB::table('compras_combustible')
                            ->where('viaje_id', $viaje->id)
                            ->update([
                                'estatus' => 'COMPLETADO',
                                'updated_at' => now()
                            ]);
                    }
                });
            }
        } catch (Throwable $e) {
            Log::critical("Error procesando transición de estado para Viaje #{$viaje->id} ({$viejoStatus} -> {$nuevoStatus}): " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    /**
     * Procesa el envío de mensajes de WhatsApp a clientes y coordinadora cuando un viaje de MGO o Diésel pasa a EN RUTA.
     */
    /**
     * Procesa el envío de mensajes de WhatsApp a clientes y coordinadora cuando un viaje de MGO o Diésel pasa a EN RUTA.
     */
    /**
     * Procesa el envío de mensajes de WhatsApp a clientes y coordinadora cuando un viaje de MGO o Diésel pasa a EN RUTA.
     */
    /**
     * Procesa el envío de mensajes de WhatsApp a clientes y coordinadora cuando un viaje de MGO o Diésel pasa a EN RUTA.
     */
    /**
     * Procesa el envío de mensajes de WhatsApp a clientes y coordinadora cuando un viaje de MGO o Diésel pasa a EN RUTA.
     */
    private function notificarDespachoEnRutaWhatsApp(Viaje $viaje): void
    {
        try {
            // Cargamos de forma segura las relaciones necesarias
            $viaje->load(['detalles.cliente', 'vehiculo', 'chofer.persona', 'ayudante.persona']);
            
            // Definición basada estrictamente en tipo_planificacion (1 = MGO, 2 = DIÉSEL)
            $tipoTexto = ((int) $viaje->tipo_planificacion === 1) ? 'MGO' : 'DIÉSEL';

            // Datos del Chofer
            $choferNombre = optional($viaje->chofer)->persona 
                ? mb_strtoupper("{$viaje->chofer->persona->nombre} {$viaje->chofer->persona->apellido}") 
                : ($viaje->chofer_externo ? mb_strtoupper($viaje->chofer_externo) : 'N/A');

            // Datos del Ayudante
            $ayudanteNombre = null;
            if (optional($viaje->ayudante_chofer)->persona) {
                $ayudanteNombre = mb_strtoupper("{$viaje->ayudante_chofer->persona->nombre} {$viaje->ayudante_chofer->persona->apellido}");
            } elseif (!empty($viaje->ayudante_externo)) {
                $ayudanteNombre = mb_strtoupper($viaje->ayudante_externo);
            }
            
            $vehiculoInfo = $viaje->vehiculo 
                ? mb_strtoupper($viaje->vehiculo->placa) 
                : ($viaje->vehiculo_externo ? mb_strtoupper($viaje->vehiculo_externo) : 'N/A');

            $destinosResumen = [];

            // 1. Notificar a los clientes asociados en los detalles del viaje
            foreach ($viaje->detalles as $detalle) {
                $cliente = $detalle->cliente;
                if (!$cliente) continue;

                $litrosFmt = number_format($detalle->litros, 2, ',', '.');
                $clienteNombreMayus = mb_strtoupper($cliente->nombre);

                // --- 1.1 NOTIFICAR AL CONTACTO PRINCIPAL ---
                if ($this->esNombreValido($cliente->contacto)) {
                    $telPrincipal = $this->limpiarYValidarTelefono($cliente->telefono);
                    if ($telPrincipal) {
                        $contactoPrincipalMayus = mb_strtoupper($cliente->contacto);
                        
                        $mensajePrincipal = "🚀 *IMPORDIESEL - DESPACHO EN RUTA*\n\n" .
                            "Estimado/a *{$contactoPrincipalMayus}* ({$clienteNombreMayus}),\n" .
                            "Le informamos que su despacho de *{$tipoTexto}* ya se encuentra *EN RUTA* hacia su destino.\n\n" .
                            "• *Volumen Asignado:* {$litrosFmt} Litros de {$tipoTexto}\n" .
                            "• *Placa de Unidad de Transporte:* {$vehiculoInfo}\n" .
                            "• *Chofer:* {$choferNombre}\n" .
                            ($ayudanteNombre ? "• *Ayudante:* {$ayudanteNombre}\n" : "") . "\n" .
                            "¡Gracias por confiar en nosotros!";

                        $this->whatsappService->enviarMensaje($mensajePrincipal, $telPrincipal);
                    }
                }

                // --- 1.2 NOTIFICAR AL CONTACTO ALTERNATIVO ---
                if ($this->esNombreValido($cliente->contacto_alt)) {
                    $telAlt = $this->limpiarYValidarTelefono($cliente->telefono_alt);
                    if ($telAlt) {
                        $contactoAltMayus = mb_strtoupper($cliente->contacto_alt);
                        
                        $mensajeAlternativo = "🚀 *IMPORDIESEL - DESPACHO EN RUTA*\n\n" .
                            "Estimado/a *{$contactoAltMayus}* ({$clienteNombreMayus}),\n" .
                            "Le informamos que su despacho de *{$tipoTexto}* ya se encuentra *EN RUTA* hacia su destino.\n\n" .
                            "• *Volumen Asignado:* {$litrosFmt} Litros de {$tipoTexto}\n" .
                            "• *Placa de Unidad de Transporte:* {$vehiculoInfo}\n" .
                            "• *Chofer:* {$choferNombre}\n" .
                            ($ayudanteNombre ? "• *Ayudante:* {$ayudanteNombre}\n" : "") . "\n" .
                            "¡Gracias por confiar en nosotros!";

                        $this->whatsappService->enviarMensaje($mensajeAlternativo, $telAlt);
                    }
                }

                // Resumen para la coordinadora
                $contactoRef = $this->esNombreValido($cliente->contacto) ? mb_strtoupper($cliente->contacto) : 'RESPONSABLE';
                $destinosResumen[] = "• *{$clienteNombreMayus}* ({$contactoRef}): {$litrosFmt} L de {$tipoTexto}";
            }

            // 2. Notificar obligatoriamente a la Coordinadora de Logística
            $resumenDetalleStr = implode("\n", $destinosResumen);
            $mensajeMaryori = "📋 *REPORTE DE DESPACHO EN RUTA*\n\n" .
                "• *Tipo de Combustible:* {$tipoTexto}\n" .
                "• *Unidad:* {$vehiculoInfo}\n" .
                "• *Chofer:* {$choferNombre}\n" .
                ($ayudanteNombre ? "• *Ayudante:* {$ayudanteNombre}\n" : "• *Ayudante:* SIN AYUDANTE\n") .
                "• *Destinos / Clientes:*\n{$resumenDetalleStr}\n\n" .
                "_Notificación automática del sistema._";

            // Avisa a Maryori
            $this->whatsappService->enviarMensaje($mensajeMaryori, '584249653081');
            //Avisa a Lemir
            $this->whatsappService->enviarMensaje($mensajeMaryori, '584241455523');

        } catch (Throwable $e) {
            Log::error("Error enviando WhatsApps de despacho en ruta para Viaje #{$viaje->id}: " . $e->getMessage());
        }
    }

    /**
     * Valida que el nombre del contacto no sea genérico o basura.
     */
    private function esNombreValido(?string $nombre): bool
    {
        if (!is_string($nombre) || trim($nombre) === '') {
            return false;
        }

        $n = strtoupper(trim($nombre));
        $palabrasBasura = ['SIN CONTACTO', 'N/A', 'NINGUNO', 'SIN', 'CLIENTE', 'PRUEBA', 'ANONIMO', 'DEFAULT'];

        foreach ($palabrasBasura as $basura) {
            if (str_contains($n, $basura)) {
                return false;
            }
        }

        return strlen($n) >= 3;
    }

    /**
     * Limpia, formatea y valida números telefónicos para WhatsApp en Venezuela.
     */
    private function limpiarYValidarTelefono(?string $telefono): ?string
    {
        if (!is_string($telefono) || trim($telefono) === '') {
            return null;
        }

        // Extraer únicamente los dígitos numéricos
        $num = preg_replace('/[^0-9]/', '', $telefono);

        if (strlen($num) < 10) {
            return null;
        }

        // Descartar números repetitivos falsos (ej: 04125555555 o 04141111111)
        if (preg_match('/^(\d)\1+$/', $num)) {
            return null;
        }

        // Adaptación a formato internacional (58)
        if (str_starts_with($num, '0') && strlen($num) === 11) {
            $num = '58' . substr($num, 1);
        } elseif (strlen($num) === 10) {
            $num = '58' . $num;
        } elseif (str_starts_with($num, '58') && strlen($num) === 12) {
            // Ya viene formateado correctamente con código de país
        } else {
            return null;
        }

        // Validar prefijos de operadoras móviles y fijas válidas en Venezuela tras el código 58
        $prefijo = substr($num, 2, 3);
        $prefijosValidos = ['412', '422', '414', '424', '416', '426', '212', '239', '241', '243', '245', '246', '251', '261', '281', '291', '276'];
        
        if (!in_array($prefijo, $prefijosValidos, true)) {
            return null;
        }

        return $num;
    }

    /**
     * Actualiza el estatus operativo del Chuto y su Cisterna de forma atómica.
     */
    private function actualizarEstatusFlota(Viaje $viaje, int $estatusDestino): void
    {
        // Actualizar vehículo principal
        Vehiculo::where('id', $viaje->vehiculo_id)->update(['estatus' => $estatusDestino]);

        // Actualizar cisterna si aplica
        if (!is_null($viaje->cisterna)) {
            Vehiculo::where('id', $viaje->cisterna)->update(['estatus' => $estatusDestino]);
        }
    }

    /**
     * Notificación multicanal aislada de fallos de red.
     */
    private function notificarAsignacionViaje(Viaje $viaje): void
    {
        try {
            $viaje->loadMissing(['chofer', 'ayudante_chofer', 'vehiculo', 'cisternaAcoplada']);

            $destinatarios = collect();

            if ($viaje->chofer) {
                $destinatarios->push($viaje->chofer);
            }
            
            if ($viaje->ayudante_chofer) {
                $destinatarios->push($viaje->ayudante_chofer);
            }

            if ($destinatarios->isNotEmpty()) {
                Notification::send($destinatarios, new ViajeCreadoNotification($viaje));
            }
        } catch (Throwable $e) {
            Log::error("Fallo al enviar notificación de Viaje Creado #{$viaje->id}: " . $e->getMessage());
        }
    }
}