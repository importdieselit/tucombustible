<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

class CheckBajoDisponibleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:bajo-disponible {--dry-run : Ejecutar sin enviar notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa clientes con disponible por debajo del 10% y envía notificaciones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando revisión de clientes con bajo disponible...');
        
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('⚠️ MODO DRY-RUN: No se enviarán notificaciones reales');
        }

        try {
            // Obtener todos los clientes principales (parent = 0) con disponible > 0
            $clientes = Cliente::where('parent', 0)
                ->where('disponible', '>', 0)
                ->where('cupo', '>', 0)
                ->get();

            $this->info("📊 Total de clientes a revisar: {$clientes->count()}");

            $clientesConBajoDisponible = 0;
            $notificacionesEnviadas = 0;

            foreach ($clientes as $cliente) {
                // Calcular el porcentaje de disponible
                $porcentajeDisponible = ($cliente->disponible / $cliente->cupo) * 100;
                
                // Verificar si está por debajo del 10%
                if ($porcentajeDisponible < 10) {
                    $clientesConBajoDisponible++;
                    
                    $this->line("⚠️ Cliente: {$cliente->nombre} - Disponible: {$cliente->disponible}L ({$porcentajeDisponible}%)");
                    
                    if (!$isDryRun) {
                        // Enviar notificación al cliente
                        $successCliente = FcmNotificationService::sendBajoDisponibleNotification(
                            $cliente,
                            $cliente->disponible,
                            $cliente->cupo,
                            null // No es sucursal
                        );
                        
                        // Enviar notificación a super admins
                        $successAdmins = FcmNotificationService::sendBajoDisponibleNotificationToSuperAdmins(
                            $cliente,
                            $cliente->disponible,
                            $cliente->cupo
                        );
                        
                        if ($successCliente) {
                            $notificacionesEnviadas++;
                            $this->info("✅ Notificación enviada al cliente: {$cliente->nombre}");
                            
                            // Log de la notificación enviada al cliente
                            // Log::info("Notificación de bajo disponible enviada al cliente por cron job", [
                            //     'cliente_id' => $cliente->id,
                            //     'cliente_nombre' => $cliente->nombre,
                            //     'disponible' => $cliente->disponible,
                            //     'cupo' => $cliente->cupo,
                            //     'porcentaje' => $porcentajeDisponible,
                            //     'fecha' => now()->toDateTimeString()
                            // ]);
                        } else {
                            $this->error("❌ Error enviando notificación al cliente: {$cliente->nombre}");
                        }
                        
                        if ($successAdmins) {
                            $this->info("✅ Notificación enviada a super admins sobre: {$cliente->nombre}");
                            
                            // Log de la notificación enviada a super admins
                            // Log::info("Notificación de bajo disponible enviada a super admins por cron job", [
                            //     'cliente_id' => $cliente->id,
                            //     'cliente_nombre' => $cliente->nombre,
                            //     'disponible' => $cliente->disponible,
                            //     'cupo' => $cliente->cupo,
                            //     'porcentaje' => $porcentajeDisponible,
                            //     'fecha' => now()->toDateTimeString()
                            // ]);
                        } else {
                            $this->warn("⚠️ Error enviando notificación a super admins sobre: {$cliente->nombre}");
                        }
                    } else {
                        $this->line("🔍 [DRY-RUN] Se enviaría notificación al cliente: {$cliente->nombre}");
                        $this->line("🔍 [DRY-RUN] Se enviaría notificación a super admins sobre: {$cliente->nombre}");
                    }
                }
            }

            // Resumen final
            $this->newLine();
            $this->info('📋 RESUMEN:');
            $this->info("   Clientes revisados: {$clientes->count()}");
            $this->info("   Clientes con bajo disponible: {$clientesConBajoDisponible}");
            
            if (!$isDryRun) {
                $this->info("   Notificaciones enviadas: {$notificacionesEnviadas}");
            } else {
                $this->warn("   [DRY-RUN] Notificaciones que se enviarían: {$clientesConBajoDisponible}");
            }

            // Log del resumen
            // Log::info("Cron job de bajo disponible completado", [
            //     'clientes_revisados' => $clientes->count(),
            //     'clientes_con_bajo_disponible' => $clientesConBajoDisponible,
            //     'notificaciones_enviadas' => $notificacionesEnviadas,
            //     'fecha_ejecucion' => now()->toDateTimeString(),
            //     'modo_dry_run' => $isDryRun
            // ]);

            $this->info('✅ Revisión completada exitosamente');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error ejecutando el comando: {$e->getMessage()}");
            
            Log::error("Error en cron job de bajo disponible", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'fecha' => now()->toDateTimeString()
            ]);
            
            return 1;
        }
    }
}
