<?php
// ----------------------------------------------------------------------
// Estándar de Procesos - TuCombustible
// Script: Generación de Resumen Diario de Flota y Mantenimiento
// ----------------------------------------------------------------------

$basePath = __DIR__;
require_once $basePath . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Vehiculo;
use App\Models\Orden;
use App\Models\ResumenDiario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

function logStatus($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $icons = ['error' => '❌', 'success' => '✅', 'info' => '🔍', 'step' => '📈'];
    $icon = $icons[$type] ?? '📋';
    echo "[{$timestamp}] {$icon} {$message}\n";
    Log::info("Cron Resumen - {$message}");
}

logStatus('Iniciando cálculo del resumen diario...', 'info');

try {
    $today = Carbon::now();
    $fechaString = $today->toDateString();

    // 1. DISPONIBILIDAD DE FLOTA
    // ----------------------------------------------------------------------
    $totalVehiculos = Vehiculo::where('es_flota', true)->count();
    $unidadesDisponibles = Vehiculo::where('es_flota', true)
        ->where('estatus', 1) 
        ->count(); 

    $disponibilidad = $totalVehiculos > 0 
        ? round(($unidadesDisponibles / $totalVehiculos) * 100, 2)
        : 0.00;
        
    logStatus("Disponibilidad: {$disponibilidad}% ({$unidadesDisponibles}/{$totalVehiculos})", 'step');


    // 2. MANTENIMIENTOS (PLAN vs REAL)
    // ----------------------------------------------------------------------
    // Planificados: Basado en umbrales de KM u Horas
    $mantenimientosPlan = Vehiculo::where('km_mantt', '>', 4800)
        ->orWhere('hrs_mantt', '>', 180)
        ->count();      

    // Realizados: Ordenes tipo 1 (Preventivo) o 5 (Correctivo) finalizadas hoy
    $mantenimientosReal = Orden::whereIn('tipo', [1, 5])
        ->where('estatus', 1)
        ->whereDate('fecha_out', $fechaString)
        ->count();
            
    logStatus("Mantenimientos: Plan ({$mantenimientosPlan}) / Real ({$mantenimientosReal})", 'step');


    // 3. AGRUPACIÓN POR MODELOS (PLAN MODELS)
    // ----------------------------------------------------------------------
    $planModelsRaw = Vehiculo::where('km_mantt', '>', 4800)
        ->select('modelo', DB::raw('count(*) as total'))
        ->groupBy('modelo')
        ->pluck('total', 'modelo')
        ->toArray();
            
    logStatus("Modelos detectados: " . count($planModelsRaw), 'step');


    // 4. PERSISTENCIA DE DATOS
    // ----------------------------------------------------------------------
    // Usamos updateOrCreate para evitar duplicados si el cron corre dos veces el mismo día
    $resumen = ResumenDiario::updateOrCreate(
        ['fecha' => $fechaString],
        [
            'plan' => $mantenimientosPlan,
            'real' => $mantenimientosReal,
            'disponibilidad' => $disponibilidad,
            'conteo' => 0, // Placeholder para efectividad de almacén
            'plan_models' => $planModelsRaw,
        ]
    );

    logStatus("Resumen diario guardado exitosamente para la fecha {$fechaString}", 'success');
    
    exit(0);

} catch (\Exception $e) {
    logStatus("ERROR: " . $e->getMessage(), 'error');
    Log::error("Fallo en Cron Resumen Diario", [
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
    exit(1);
}