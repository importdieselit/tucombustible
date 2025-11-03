<?php
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
    Log::info("Cron Job - {$message}");
}

// Función para mostrar ayuda
function showHelp() {
    echo "Uso: php cron_check_bajo_disponible.php [opciones]\n";
    echo "Opciones:\n";
    echo "  --dry-run    Ejecutar sin enviar notificaciones reales\n";
    echo "  --help       Mostrar esta ayuda\n";
    echo "\n";
    echo "Ejemplos:\n";
    echo "  php cron_check_bajo_disponible.php\n";
    echo "  php cron_check_bajo_disponible.php --dry-run\n";
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
logMessage('Iniciando revisión de clientes con bajo disponible...');

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
    $notificacionesEnviadas = 0;
    $errores = 0;

    foreach ($clientes as $cliente) {
        // Calcular el porcentaje de disponible
        $porcentajeDisponible = ($cliente->disponible / $cliente->cupo) * 100;
        
        // Verificar si está por debajo del 10%
        if ($porcentajeDisponible < 10) {
            $clientesConBajoDisponible++;
            
            logMessage("Cliente: {$cliente->nombre} - Disponible: {$cliente->disponible}L (" . number_format($porcentajeDisponible, 2) . "%)", 'warning');
            
            if (!$isDryRun) {
                try {
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
                        logMessage("Notificación enviada al cliente: {$cliente->nombre}", 'success');
                        
                        // Log detallado de la notificación enviada al cliente
                        Log::info("Notificación de bajo disponible enviada al cliente por cron job", [
                            'cliente_id' => $cliente->id,
                            'cliente_nombre' => $cliente->nombre,
                            'disponible' => $cliente->disponible,
                            'cupo' => $cliente->cupo,
                            'porcentaje' => $porcentajeDisponible,
                            'fecha' => now()->toDateTimeString()
                        ]);
                    } else {
                        $errores++;
                        logMessage("Error enviando notificación al cliente: {$cliente->nombre}", 'error');
                    }
                    
                    if ($successAdmins) {
                        logMessage("Notificación enviada a super admins sobre: {$cliente->nombre}", 'success');
                        
                        // Log detallado de la notificación enviada a super admins
                        Log::info("Notificación de bajo disponible enviada a super admins por cron job", [
                            'cliente_id' => $cliente->id,
                            'cliente_nombre' => $cliente->nombre,
                            'disponible' => $cliente->disponible,
                            'cupo' => $cliente->cupo,
                            'porcentaje' => $porcentajeDisponible,
                            'fecha' => now()->toDateTimeString()
                        ]);
                    } else {
                        logMessage("Error enviando notificación a super admins sobre: {$cliente->nombre}", 'warning');
                    }
                } catch (\Exception $e) {
                    $errores++;
                    logMessage("Excepción enviando notificaciones para {$cliente->nombre}: {$e->getMessage()}", 'error');
                }
            } else {
                logMessage("[DRY-RUN] Se enviaría notificación al cliente: {$cliente->nombre}");
                logMessage("[DRY-RUN] Se enviaría notificación a super admins sobre: {$cliente->nombre}");
            }
        }
    }

    // Resumen final
    echo "\n";
    logMessage('RESUMEN:', 'info');
    logMessage("   Clientes revisados: {$clientes->count()}");
    logMessage("   Clientes con bajo disponible: {$clientesConBajoDisponible}");
    
    if (!$isDryRun) {
        logMessage("   Notificaciones enviadas: {$notificacionesEnviadas}");
        if ($errores > 0) {
            logMessage("   Errores: {$errores}", 'error');
        }
    } else {
        logMessage("[DRY-RUN] Notificaciones que se enviarían: {$clientesConBajoDisponible}");
    }

    // Log del resumen
    Log::info("Cron job de bajo disponible completado", [
        'clientes_revisados' => $clientes->count(),
        'clientes_con_bajo_disponible' => $clientesConBajoDisponible,
        'notificaciones_enviadas' => $notificacionesEnviadas,
        'errores' => $errores,
        'fecha_ejecucion' => now()->toDateTimeString(),
        'modo_dry_run' => $isDryRun
    ]);

    logMessage('Revisión completada exitosamente', 'success');
    
    // Código de salida: 0 = éxito, 1 = error
    exit(0);

} catch (\Exception $e) {
    logMessage("Error ejecutando el script: {$e->getMessage()}", 'error');
    
    Log::error("Error en cron job de bajo disponible", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'fecha' => now()->toDateTimeString()
    ]);
    
    exit(1);
}
