<?php
/**
 * Script para revisar clientes con bajo disponible y enviar notificaciones SOLO a super admins
 * Este script se ejecuta cada hora como cron job en cPanel
 * 
 * Uso: php cron_check_bajo_disponible_admin.php [--dry-run]
 */

// Configuración de rutas
$basePath = __DIR__;
require_once $basePath . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cliente;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

// Función para mostrar mensajes con timestamp
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');

     $prefix = '📋'; // Default
    switch($type) {
        case 'error':
            $prefix = '❌';
            break;
        case 'warning':
            $prefix = '⚠️';
            break;
        case 'success':
            $prefix = '✅';
            break;
        case 'info':
            $prefix = '🔍';
            break;
    }
    
    echo "[{$timestamp}] {$prefix} {$message}\n";
    
    // También logear en archivo
    Log::info("Cron Job Admin - {$message}");
}

// Función para mostrar ayuda
function showHelp() {
    echo "Uso: php cron_check_bajo_disponible_admin.php [opciones]\n";
    echo "Opciones:\n";
    echo "  --dry-run    Ejecutar sin enviar notificaciones reales\n";
    echo "  --help       Mostrar esta ayuda\n";
    echo "\n";
    echo "Ejemplos:\n";
    echo "  php cron_check_bajo_disponible_admin.php\n";
    echo "  php cron_check_bajo_disponible_admin.php --dry-run\n";
    echo "\n";
    echo "NOTA: Este script está diseñado para ejecutarse cada hora y solo notifica a super admins.\n";
}

// Procesar argumentos de línea de comandos
$isDryRun = false;
$showHelp = false;

foreach ($argv as $arg) {
    switch ($arg) {
        case '--dry-run':
            $isDryRun = true;
            break;
        case '--help':
        case '-h':
            $showHelp = true;
            break;
    }
}

if ($showHelp) {
    showHelp();
    exit(0);
}

// Iniciar el proceso
logMessage('Iniciando revisión de clientes con bajo disponible para super admins...');

if ($isDryRun) {
    logMessage('MODO DRY-RUN: No se enviarán notificaciones reales', 'warning');
}

try {
    // Obtener todos los clientes principales (parent = 0) con disponible > 0
    $clientes = Cliente::where('parent', 0)
        ->where('disponible', '>', 0)
        ->where('cupo', '>', 0)
        ->get();

    logMessage("Total de clientes a revisar: {$clientes->count()}");

    $clientesConBajoDisponible = 0;
    $clientesConBajoDisponibleData = [];
    $errores = 0;

    foreach ($clientes as $cliente) {
        // Calcular el porcentaje de disponible
        $porcentajeDisponible = ($cliente->disponible / $cliente->cupo) * 100;
        
        // Verificar si está por debajo del 10%
        if ($porcentajeDisponible < 10) {
            $clientesConBajoDisponible++;
            
            logMessage("Cliente: {$cliente->nombre} - Disponible: {$cliente->disponible}L (" . number_format($porcentajeDisponible, 2) . "%)", 'warning');
            
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
            try {
                // Enviar notificación consolidada SOLO a super admins
                $successAdmins = FcmNotificationService::sendBajoDisponibleConsolidatedNotificationToSuperAdmins(
                    $clientesConBajoDisponibleData
                );
                
                if ($successAdmins) {
                    $notificacionesEnviadas = 1; // Una sola notificación consolidada
                    logMessage("Notificación consolidada enviada a super admins sobre {$clientesConBajoDisponible} clientes", 'success');
                    
                    // Log detallado de la notificación consolidada enviada a super admins
                    Log::info("Notificación consolidada de bajo disponible enviada a super admins por cron job admin", [
                        'total_clientes' => $clientesConBajoDisponible,
                        'clientes' => $clientesConBajoDisponibleData,
                        'fecha' => now()->toDateTimeString(),
                        'tipo_cron' => 'admin_hourly_consolidado'
                    ]);
                } else {
                    $errores++;
                    logMessage("Error enviando notificación consolidada a super admins", 'error');
                }
            } catch (\Exception $e) {
                $errores++;
                logMessage("Excepción enviando notificación consolidada a super admins: {$e->getMessage()}", 'error');
            }
        } else {
            logMessage("[DRY-RUN] Se enviaría notificación consolidada a super admins sobre {$clientesConBajoDisponible} clientes");
        }
    }

    // Resumen final
    echo "\n";
    logMessage('RESUMEN (CRON ADMIN - CADA HORA):', 'info');
    logMessage("   Clientes revisados: {$clientes->count()}");
    logMessage("   Clientes con bajo disponible: {$clientesConBajoDisponible}");
    
    if (!$isDryRun) {
        logMessage("   Notificaciones enviadas a super admins: {$notificacionesEnviadas}");
        if ($errores > 0) {
            logMessage("   Errores: {$errores}", 'error');
        }
    } else {
        logMessage("[DRY-RUN] Notificaciones que se enviarían a super admins: {$clientesConBajoDisponible}");
    }

    // Log del resumen
    Log::info("Cron job de bajo disponible para super admins completado", [
        'clientes_revisados' => $clientes->count(),
        'clientes_con_bajo_disponible' => $clientesConBajoDisponible,
        'notificaciones_enviadas' => $notificacionesEnviadas,
        'errores' => $errores,
        'fecha_ejecucion' => now()->toDateTimeString(),
        'modo_dry_run' => $isDryRun,
        'tipo_cron' => 'admin_hourly'
    ]);

    logMessage('Revisión para super admins completada exitosamente', 'success');
    
    // Código de salida: 0 = éxito, 1 = error
    exit(0);

} catch (\Exception $e) {
    logMessage("Error ejecutando el script: {$e->getMessage()}", 'error');
    
    Log::error("Error en cron job de bajo disponible para super admins", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'fecha' => now()->toDateTimeString(),
        'tipo_cron' => 'admin_hourly'
    ]);
    
    exit(1);
}
