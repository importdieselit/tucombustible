<?php
/**
 * Script de prueba para simular notificación consolidada de bajo disponible
 * 
 * Este script simula el envío de una notificación consolidada para probar
 * la funcionalidad de navegación en la app Flutter.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

echo "🧪 Iniciando prueba de notificación consolidada...\n";

// Datos de prueba - simular clientes con bajo disponible
$clientesConBajoDisponible = [
    [
        'id' => 1,
        'nombre' => 'Empresa ABC S.A.',
        'disponible' => 150.5,
        'cupo' => 1800.0,
        'porcentaje_disponible' => 8.4,
        'telefono' => '+1234567890',
        'email' => 'contacto@empresaabc.com',
        'direccion' => 'Av. Principal 123, Ciudad',
    ],
    [
        'id' => 2,
        'nombre' => 'Transportes XYZ',
        'disponible' => 75.2,
        'cupo' => 1200.0,
        'porcentaje_disponible' => 6.3,
        'telefono' => '+0987654321',
        'email' => 'admin@transportesxyz.com',
        'direccion' => 'Calle Secundaria 456, Ciudad',
    ],
    [
        'id' => 3,
        'nombre' => 'Logística DEF',
        'disponible' => 45.8,
        'cupo' => 800.0,
        'porcentaje_disponible' => 5.7,
        'telefono' => '+1122334455',
        'email' => 'info@logisticadef.com',
        'direccion' => 'Zona Industrial 789, Ciudad',
    ],
];

echo "📊 Datos de prueba preparados:\n";
foreach ($clientesConBajoDisponible as $cliente) {
    echo "   - {$cliente['nombre']}: {$cliente['disponible']}L ({$cliente['porcentaje_disponible']}%)\n";
}

echo "\n🚀 Enviando notificación consolidada...\n";

try {
    // Enviar notificación consolidada
    $success = FcmNotificationService::sendBajoDisponibleConsolidatedNotificationToSuperAdmins(
        $clientesConBajoDisponible
    );
    
    if ($success) {
        echo "✅ Notificación consolidada enviada exitosamente\n";
        echo "📱 Revisa tu dispositivo móvil para ver la notificación\n";
        echo "🔍 Verifica que el botón diga 'Ver Detalles' y navegue correctamente\n";
    } else {
        echo "❌ Error enviando notificación consolidada\n";
    }
    
} catch (Exception $e) {
    echo "❌ Excepción durante el envío: " . $e->getMessage() . "\n";
    echo "📋 Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n📋 Logs del sistema:\n";
echo "   - Revisa storage/logs/laravel.log para más detalles\n";
echo "   - Verifica que los super admins tengan tokens FCM válidos\n";

echo "\n🧪 Prueba completada.\n";
?>
