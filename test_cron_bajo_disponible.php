<?php
/**
 * Script de prueba para verificar el funcionamiento del cron job de bajo disponible
 * Este script simula la ejecución sin enviar notificaciones reales
 */

// Configuración de rutas
$basePath = __DIR__;
require_once $basePath . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

// Función para mostrar mensajes con formato
function showMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $prefix = match($type) {
        'error' => '❌',
        'warning' => '⚠️',
        'success' => '✅',
        'info' => '🔍',
        'test' => '🧪',
        default => '📋'
    };
    
    echo "[{$timestamp}] {$prefix} {$message}\n";
}

showMessage('Iniciando prueba del sistema de bajo disponible...', 'test');

try {
    // 1. Verificar conexión a la base de datos
    showMessage('Verificando conexión a la base de datos...', 'test');
    $totalClientes = Cliente::count();
    showMessage("Total de clientes en la base de datos: {$totalClientes}", 'success');

    // 2. Verificar clientes principales
    $clientesPrincipales = Cliente::where('parent', 0)->count();
    showMessage("Clientes principales (parent = 0): {$clientesPrincipales}", 'info');

    // 3. Verificar clientes con disponible > 0
    $clientesConDisponible = Cliente::where('parent', 0)
        ->where('disponible', '>', 0)
        ->where('cupo', '>', 0)
        ->count();
    showMessage("Clientes principales con disponible > 0: {$clientesConDisponible}", 'info');

    // 4. Simular la lógica de revisión
    showMessage('Simulando revisión de clientes con bajo disponible...', 'test');
    
    $clientes = Cliente::where('parent', 0)
        ->where('disponible', '>', 0)
        ->where('cupo', '>', 0)
        ->get();

    $clientesConBajoDisponible = 0;
    $ejemplos = [];

    foreach ($clientes as $cliente) {
        $porcentajeDisponible = ($cliente->disponible / $cliente->cupo) * 100;
        
        if ($porcentajeDisponible < 10) {
            $clientesConBajoDisponible++;
            
            // Guardar algunos ejemplos para mostrar
            if (count($ejemplos) < 5) {
                $ejemplos[] = [
                    'nombre' => $cliente->nombre,
                    'disponible' => $cliente->disponible,
                    'cupo' => $cliente->cupo,
                    'porcentaje' => $porcentajeDisponible
                ];
            }
        }
    }

    showMessage("Clientes con disponible < 10%: {$clientesConBajoDisponible}", 'warning');

    // 5. Mostrar ejemplos
    if (!empty($ejemplos)) {
        showMessage('Ejemplos de clientes con bajo disponible:', 'test');
        foreach ($ejemplos as $ejemplo) {
            showMessage("  - {$ejemplo['nombre']}: {$ejemplo['disponible']}L / {$ejemplo['cupo']}L (" . number_format($ejemplo['porcentaje'], 2) . "%)", 'warning');
        }
    }

    // 6. Verificar configuración de FCM
    showMessage('Verificando configuración de FCM...', 'test');
    
    $fcmConfig = config('services.fcm');
    if ($fcmConfig && isset($fcmConfig['project_id'])) {
        showMessage("FCM Project ID configurado: {$fcmConfig['project_id']}", 'success');
    } else {
        showMessage('FCM Project ID no configurado', 'error');
    }

    // 7. Verificar archivo de credenciales
    $credentialsPath = storage_path("tucombustible-76660-firebase-adminsdk-fbsvc-186df7ef1c.json");
    if (file_exists($credentialsPath)) {
        showMessage('Archivo de credenciales FCM encontrado', 'success');
    } else {
        showMessage('Archivo de credenciales FCM no encontrado', 'error');
    }

    // 8. Verificar logs
    showMessage('Verificando sistema de logs...', 'test');
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logSize = filesize($logPath);
        showMessage("Archivo de log encontrado (tamaño: " . number_format($logSize / 1024, 2) . " KB)", 'success');
    } else {
        showMessage('Archivo de log no encontrado', 'warning');
    }

    // Resumen final
    echo "\n";
    showMessage('RESUMEN DE LA PRUEBA:', 'test');
    showMessage("  ✅ Conexión a BD: OK");
    showMessage("  ✅ Clientes principales: {$clientesPrincipales}");
    showMessage("  ✅ Clientes con disponible: {$clientesConDisponible}");
    showMessage("  ⚠️ Clientes con bajo disponible: {$clientesConBajoDisponible}");
    
    if ($clientesConBajoDisponible > 0) {
        showMessage("  📱 Se enviarían {$clientesConBajoDisponible} notificaciones", 'warning');
    } else {
        showMessage("  📱 No hay clientes que requieran notificación", 'success');
    }

    showMessage('Prueba completada exitosamente', 'success');

} catch (\Exception $e) {
    showMessage("Error durante la prueba: {$e->getMessage()}", 'error');
    showMessage("Trace: {$e->getTraceAsString()}", 'error');
    exit(1);
}
