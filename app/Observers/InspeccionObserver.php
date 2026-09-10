<?php

namespace App\Observers;

use App\Models\Inspeccion;
use App\Models\Vehiculo;
use App\Models\Viaje;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class InspeccionObserver
{
    protected WhatsappApiService $whatsappService;

    public function __construct(WhatsappApiService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * CHECK-OUT (Salida): Se ejecuta al crear la inspección de salida.
     */
    public function created(Inspeccion $inspeccion): void
    {
        try {
            $nombreUsuario = $this->obtenerNombreUsuario();
            $vehiculo = Vehiculo::find($inspeccion->vehiculo_id);
            
            if (!$vehiculo) return;

            // 1. Extraer datos del JSON (Viaje y Observaciones)
            $datosForm = $this->extraerDatosFormulario($inspeccion->respuesta_json);
            $observacion = $datosForm['observacion'];
            $viajeIdExtraido = $datosForm['viaje_id'] ?? $inspeccion->viaje_id;

            // 2. Resolver el Viaje correspondiente
            $viaje = $this->resolverViajeSalida($vehiculo->id, $viajeIdExtraido);

            if ($viaje) {
                // Asegurar trazabilidad vinculando la inspección al viaje
                if ($inspeccion->viaje_id !== $viaje->id) {
                    $inspeccion->viaje_id = $viaje->id;
                    $inspeccion->saveQuietly(); 
                }

                // 3. Modificar estatus del Viaje. 
                // NOTA: NO cambiamos el estatus del vehículo aquí. Al guardar el viaje a 'EN RUTA', 
                // el ViajeObserver se dispara automáticamente, cambia el vehículo y descuenta el Ledger.
                if (strtoupper((string) $viaje->status) !== 'EN RUTA') {
                    $viaje->status = 'EN RUTA';
                    $viaje->fecha_salida_real = now();
                    $viaje->save(); 
                }

                // 4. Enviar notificación asíncrona (Aislada de fallos DB)
                $this->enviarNotificacionWhatsApp(
                    "🚀 *CHECKOUT (SALIDA)*\n\nEl usuario *{$nombreUsuario}* ha registrado el checklist de salida para la unidad *{$vehiculo->flota}* ({$vehiculo->placa}).\n\n• *Salida:* #{$viaje->id}\n• *Destino:* {$viaje->destino_ciudad}",
                    $observacion
                );
            }
        } catch (Throwable $e) {
            Log::error("Error en InspeccionObserver@created para Inspeccion #{$inspeccion->id}: " . $e->getMessage());
        }
    }

    /**
     * CHECK-IN (Llegada): Se ejecuta al actualizar y completar el checklist de retorno.
     */
    public function updated(Inspeccion $inspeccion): void
    {
        try {
            $eraNull = is_null($inspeccion->getOriginal('respuesta_in'));
            $ahoraTieneDatos = !is_null($inspeccion->respuesta_in);

            // Solo procesar si acaba de llenarse la respuesta de llegada
            if (!($inspeccion->wasChanged('respuesta_in') && $eraNull && $ahoraTieneDatos)) {
                return;
            }

            $nombreUsuario = $this->obtenerNombreUsuario();
            $vehiculo = Vehiculo::find($inspeccion->vehiculo_id);

            if (!$vehiculo) return;

            // 1. Extraer observaciones del JSON de retorno
            $datosForm = $this->extraerDatosFormulario($inspeccion->respuesta_in);
            $observacion = $datosForm['observacion'];

            // 2. Buscar el viaje activo asociado
            $viaje = Viaje::find($inspeccion->viaje_id) 
                  ?? Viaje::where('vehiculo_id', $vehiculo->id)->where('status', 'EN RUTA')->first();

            if ($viaje && strtoupper((string) $viaje->status) !== 'COMPLETADO') {
                // Al marcar como completado, ViajeObserver asume el control: 
                // Libera cisternas, devuelve estatus a 1 e ingresa compras.
                $viaje->status = 'COMPLETADO';
                $viaje->fecha_llegada = now();
                $viaje->save();
            }

            // 3. Notificar Llegada
            $viajeId = $viaje ? $viaje->id : $inspeccion->viaje_id;
            $this->enviarNotificacionWhatsApp(
                "✅ *CHECKIN (LLEGADA)*\n\nEl usuario *{$nombreUsuario}* ha registrado el checklist de llegada para la unidad *{$vehiculo->flota}* ({$vehiculo->placa}).\n\n• *Viaje Cerrado:* #{$viajeId}",
                $observacion
            );

        } catch (Throwable $e) {
            Log::error("Error en InspeccionObserver@updated para Inspeccion #{$inspeccion->id}: " . $e->getMessage());
        }
    }

    /**
     * Extrae de forma segura el ID del viaje y las observaciones del JSON de Formio/Formulario.
     */
    private function extraerDatosFormulario(?string $json): array
    {
        $resultado = ['viaje_id' => null, 'observacion' => false];
        if (!$json) return $resultado;

        $data = json_decode($json, true);
        if (!isset($data['sections']) || !is_array($data['sections'])) return $resultado;

        foreach ($data['sections'] as $section) {
            if (!isset($section['items']) || !is_array($section['items'])) continue;

            foreach ($section['items'] as $item) {
                $label = $item['label'] ?? '';

                if ($label === 'Observaciones Generales' && !empty($item['value'])) {
                    $resultado['observacion'] = trim($item['value']);
                }

                if ($label === 'Seleccione Ruta a Cubrir' && !empty($item['value'])) {
                    if (preg_match('/ID-(\d+)/', $item['value'], $matches)) {
                        $resultado['viaje_id'] = $matches[1];
                    }
                }
            }
        }
        return $resultado;
    }

    /**
     * Resuelve el viaje verificando prioridades (ID explícito vs Histórico pendiente).
     */
    private function resolverViajeSalida(int $vehiculoId, ?int $viajeIdForm): ?Viaje
    {
        if ($viajeIdForm) {
            $viaje = Viaje::find($viajeIdForm);
            if ($viaje) return $viaje;
        }

        // Failsafe: Si no vino ID válido, tomamos el programado más antiguo de la unidad
        return Viaje::where('vehiculo_id', $vehiculoId)
            ->where('status', 'Programado')
            ->orderBy('fecha_salida', 'asc')
            ->first();
    }

    /**
     * Estandariza el envío utilizando el servicio centralizado.
     */
    private function enviarNotificacionWhatsApp(string $mensajeBase, string|bool $observacion): void
    {
        if ($observacion) {
            $mensajeBase .= "\n\n⚠️ *Observación:* {$observacion}";
        }

        try {
            $this->whatsappService->enviarMensaje(
                $mensajeBase, 
                config('services.whatsapp.group_operaciones')
            );
        } catch (Throwable $e) {
            Log::warning("Fallo al enviar notificación de Inspección vía WhatsApp: " . $e->getMessage());
        }
    }

    /**
     * Maneja de forma segura la autenticación por si el evento se dispara vía Job o API.
     */
    private function obtenerNombreUsuario(): string
    {
        $user = auth()->user();
        return $user->persona->nombre ?? 'Sistema / Chofer (App)';
    }
}