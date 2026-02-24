<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

class CheckBajoDisponibleAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:bajo-disponible-admin {--dry-run : Ejecutar sin enviar notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa clientes con disponible por debajo del 10% y envía notificaciones SOLO a super admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando revisión de clientes con bajo disponible para super admins...');
        
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
            $clientesConBajoDisponibleData = [];

            foreach ($clientes as $cliente) {
                // Calcular el porcentaje de disponible
                $porcentajeDisponible = ($cliente->disponible / $cliente->cupo) * 100;
                
                // Verificar si está por debajo del 10%
                if ($porcentajeDisponible < 10) {
                    $clientesConBajoDisponible++;
                    
                    $this->line("⚠️ Cliente: {$cliente->nombre} - Disponible: {$cliente->disponible}L ({$porcentajeDisponible}%)");
                    
                    // Agregar datos del cliente para notificación consolidada
                    $clientesConBajoDisponibleData[] = [
                        'id' => $cliente->id,
                        'nombre' => $cliente->nombre,
                        'disponible' => $cliente->disponible,
                        'cupo' => $cliente->cupo,
                        'porcentaje_disponible' => $porcentajeDisponible,
                        'telefono' => $cliente->telefono,
                        'email' => $cliente->email,
                        'direccion' => $cliente->direccion,
                    ];
                }
            }

            // Enviar notificación consolidada si hay clientes con bajo disponible
            $notificacionesEnviadas = 0;
            if (!empty($clientesConBajoDisponibleData)) {
                if (!$isDryRun) {
                    // Enviar notificación consolidada SOLO a super admins
                    $successAdmins = FcmNotificationService::sendBajoDisponibleConsolidatedNotificationToSuperAdmins(
                        $clientesConBajoDisponibleData
                    );
                    
                    if ($successAdmins) {
                        $notificacionesEnviadas = 1; // Una sola notificación consolidada
                        $this->info("✅ Notificación consolidada enviada a super admins sobre {$clientesConBajoDisponible} clientes");
                        
                        // Log de la notificación consolidada enviada a super admins
                        // Log::info("Notificación consolidada de bajo disponible enviada a super admins por cron job admin", [
                        //     'total_clientes' => $clientesConBajoDisponible,
                        //     'clientes' => $clientesConBajoDisponibleData,
                        //     'fecha' => now()->toDateTimeString(),
                        //     'tipo_cron' => 'admin_hourly_consolidado'
                        // ]);
                    } else {
                        $this->error("❌ Error enviando notificación consolidada a super admins");
                    }
                } else {
                    $this->line("🔍 [DRY-RUN] Se enviaría notificación consolidada a super admins sobre {$clientesConBajoDisponible} clientes");
                }
            }

            // Resumen final
            $this->newLine();
            $this->info('📋 RESUMEN (CRON ADMIN - CADA HORA):');
            $this->info("   Clientes revisados: {$clientes->count()}");
            $this->info("   Clientes con bajo disponible: {$clientesConBajoDisponible}");
            
            if (!$isDryRun) {
                $this->info("   Notificaciones enviadas a super admins: {$notificacionesEnviadas}");
            } else {
                $this->warn("   [DRY-RUN] Notificaciones que se enviarían a super admins: {$clientesConBajoDisponible}");
            }

            // Log del resumen
            // Log::info("Cron job de bajo disponible para super admins completado", [
            //     'clientes_revisados' => $clientes->count(),
            //     'clientes_con_bajo_disponible' => $clientesConBajoDisponible,
            //     'notificaciones_enviadas' => $notificacionesEnviadas,
            //     'fecha_ejecucion' => now()->toDateTimeString(),
            //     'modo_dry_run' => $isDryRun,
            //     'tipo_cron' => 'admin_hourly'
            // ]);

            $this->info('✅ Revisión para super admins completada exitosamente');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error ejecutando el comando: {$e->getMessage()}");
            
            Log::error("Error en cron job de bajo disponible para super admins", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'fecha' => now()->toDateTimeString(),
                'tipo_cron' => 'admin_hourly'
            ]);
            
            return 1;
        }
    }
}
