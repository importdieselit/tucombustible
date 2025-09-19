<?php
/**
 * Script de prueba para el sistema de notificaciones de bajo disponible para super admins
 * Este script verifica que todo esté configurado correctamente antes de configurar el cron job
 */

// Configuración de rutas
$basePath = __DIR__;
require_once $basePath . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cliente;
use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

// Función para mostrar mensajes con colores
function showMessage($message, $type = 'info') {
    $colors = [
        'error' => "\033[31m",    // Rojo
        'success' => "\033[32m",  // Verde
        'warning' => "\033[33m",  // Amarillo
        'info' => "\033[36m",     // Cian
        'reset' => "\033[0m"      // Reset
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    $reset = $colors['reset'];
    
    echo "{$color}{$message}{$reset}\n";
}

// Función para mostrar separador
function showSeparator($title = '') {
    echo "\n" . str_repeat('=', 60) . "\n";
    if ($title) {
        echo "  {$title}\n";
        echo str_repeat('=', 60) . "\n";
    }
}

showSeparator('PRUEBA DEL SISTEMA DE BAJO DISPONIBLE PARA SUPER ADMINS');

// 1. Verificar conexión a la base de datos
showMessage("1. Verificando conexión a la base de datos...", 'info');
try {
    $clientesCount = Cliente::count();
    showMessage("   ✅ Conexión exitosa. Total de clientes: {$clientesCount}", 'success');
} catch (\Exception $e) {
    showMessage("   ❌ Error de conexión: " . $e->getMessage(), 'error');
    exit(1);
}

// 2. Verificar configuración de Firebase
showMessage("\n2. Verificando configuración de Firebase...", 'info');
try {
    $projectId = config('services.fcm.project_id');
    if (!$projectId) {
        showMessage("   ❌ FCM Project ID no configurado en config/services.php", 'error');
    } else {
        showMessage("   ✅ FCM Project ID configurado: {$projectId}", 'success');
    }
    
    $credentialsFile = storage_path("tucombustible-76660-firebase-adminsdk-fbsvc-186df7ef1c.json");
    if (!file_exists($credentialsFile)) {
        showMessage("   ❌ Archivo de credenciales no encontrado: {$credentialsFile}", 'error');
    } else {
        showMessage("   ✅ Archivo de credenciales encontrado", 'success');
    }
} catch (\Exception $e) {
    showMessage("   ❌ Error verificando Firebase: " . $e->getMessage(), 'error');
}

// 3. Verificar usuarios super admins
showMessage("\n3. Verificando usuarios super admins...", 'info');
try {
    $superAdmins = User::where('id_perfil', 1)->get();
    if ($superAdmins->isEmpty()) {
        showMessage("   ⚠️ No se encontraron usuarios con perfil de super admin (id_perfil = 1)", 'warning');
    } else {
        showMessage("   ✅ Super admins encontrados: {$superAdmins->count()}", 'success');
        
        foreach ($superAdmins as $admin) {
            $tokenStatus = $admin->fcm_token ? '✅' : '❌';
            showMessage("      - {$admin->email} (Token FCM: {$tokenStatus})", 'info');
        }
    }
} catch (\Exception $e) {
    showMessage("   ❌ Error verificando super admins: " . $e->getMessage(), 'error');
}

// 4. Verificar clientes con bajo disponible
showMessage("\n4. Verificando clientes con bajo disponible...", 'info');
try {
    $clientes = Cliente::where('parent', 0)
        ->where('disponible', '>', 0)
        ->where('cupo', '>', 0)
        ->get();
    
    $clientesConBajoDisponible = 0;
    
    foreach ($clientes as $cliente) {
        $porcentaje = ($cliente->disponible / $cliente->cupo) * 100;
        if ($porcentaje < 10) {
            $clientesConBajoDisponible++;
            showMessage("   ⚠️ Cliente con bajo disponible: {$cliente->nombre} ({$porcentaje}%)", 'warning');
        }
    }
    
    if ($clientesConBajoDisponible > 0) {
        showMessage("   ✅ Clientes con bajo disponible encontrados: {$clientesConBajoDisponible}", 'success');
    } else {
        showMessage("   ℹ️ No hay clientes con bajo disponible en este momento", 'info');
    }
    
    showMessage("   📊 Total de clientes revisados: {$clientes->count()}", 'info');
    
} catch (\Exception $e) {
    showMessage("   ❌ Error verificando clientes: " . $e->getMessage(), 'error');
}

// 5. Prueba del servicio de notificaciones (modo dry-run)
showMessage("\n5. Probando servicio de notificaciones (modo dry-run)...", 'info');
try {
    // Simular un cliente con bajo disponible para la prueba
    $clientePrueba = $clientes->first();
    if ($clientePrueba) {
        showMessage("   🔍 Simulando notificación para: {$clientePrueba->nombre}", 'info');
        
        // No enviar notificación real, solo verificar que el método existe
        if (method_exists(FcmNotificationService::class, 'sendBajoDisponibleNotificationToSuperAdmins')) {
            showMessage("   ✅ Método de notificación a super admins disponible", 'success');
        } else {
            showMessage("   ❌ Método de notificación a super admins no encontrado", 'error');
        }
    } else {
        showMessage("   ⚠️ No hay clientes para probar", 'warning');
    }
} catch (\Exception $e) {
    showMessage("   ❌ Error probando servicio: " . $e->getMessage(), 'error');
}

// 6. Verificar archivos del sistema
showMessage("\n6. Verificando archivos del sistema...", 'info');
$archivos = [
    'cron_check_bajo_disponible_admin.php' => 'Script principal para cron job',
    'app/Console/Commands/CheckBajoDisponibleAdminCommand.php' => 'Comando de Laravel Artisan',
    'app/Services/FcmNotificationService.php' => 'Servicio de notificaciones FCM'
];

foreach ($archivos as $archivo => $descripcion) {
    $ruta = $basePath . '/' . $archivo;
    if (file_exists($ruta)) {
        showMessage("   ✅ {$descripcion}: {$archivo}", 'success');
    } else {
        showMessage("   ❌ Archivo faltante: {$archivo}", 'error');
    }
}

// 7. Resumen y recomendaciones
showSeparator('RESUMEN Y RECOMENDACIONES');

showMessage("📋 Resumen de la prueba:", 'info');
showMessage("   - Conexión a base de datos: " . (isset($clientesCount) ? "✅ OK" : "❌ Error"), 'info');
showMessage("   - Configuración Firebase: " . (isset($projectId) && $projectId ? "✅ OK" : "❌ Error"), 'info');
showMessage("   - Super admins: " . (isset($superAdmins) && !$superAdmins->isEmpty() ? "✅ {$superAdmins->count()} encontrados" : "⚠️ Ninguno"), 'info');
showMessage("   - Clientes con bajo disponible: " . (isset($clientesConBajoDisponible) ? "{$clientesConBajoDisponible}" : "0"), 'info');

showMessage("\n🚀 Próximos pasos:", 'info');
showMessage("   1. Si todo está OK, configurar el cron job en cPanel:", 'info');
showMessage("      Comando: /usr/bin/php /ruta/tucombustible/cron_check_bajo_disponible_admin.php", 'info');
showMessage("      Frecuencia: Cada hora (0 * * * *)", 'info');
showMessage("   2. Probar con dry-run: php cron_check_bajo_disponible_admin.php --dry-run", 'info');
showMessage("   3. Monitorear logs en storage/logs/laravel.log", 'info');

showMessage("\n⚠️ IMPORTANTE:", 'warning');
showMessage("   - Este cron job se ejecuta CADA HORA", 'warning');
showMessage("   - Solo notifica a super admins (id_perfil = 1)", 'warning');
showMessage("   - Es independiente del cron job diario para clientes", 'warning');

showSeparator('PRUEBA COMPLETADA');
