<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ResumenDiario;
use App\Models\Vehiculo;
// Asegúrate de que estos modelos existan en tu sistema:
use App\Models\Orden; 
use App\Models\Inventario; 
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailySummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:daily-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula y almacena las métricas operativas clave para el resumen diario.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Iniciando cálculo del resumen diario para la fecha: {$today->toDateString()}");

        // ----------------------------------------------------------------------
        // 1. CÁLCULO DE EFICIENCIA DE FLOTA (DISPONIBILIDAD)
        // ----------------------------------------------------------------------
        $totalVehiculos = Vehiculo::where('es_flota', true)->count();
        $unidadesDisponibles = Vehiculo::where('es_flota', true)
            ->where('status',1) // Asumiendo que status 1 = disponible
            ->count(); 

        $disponibilidad = $totalVehiculos > 0 
            ? round(($unidadesDisponibles / $totalVehiculos) * 100, 2)
            : 0.00;
            
        $this->info("-> Disponibilidad de Flota: {$disponibilidad}% ({$unidadesDisponibles}/{$totalVehiculos})");


        // ----------------------------------------------------------------------
        // 2. CÁLCULO DE MANTENIMIENTOS (PLAN y REAL)
        // ----------------------------------------------------------------------
        
        // Planificados (Plan): Asumimos que es el total de planificados en el rango de hoy
        $mantenimientosPlan = Vehiculo::where('km_mantt','>' ,4800)->count();      
        // $mantenimientosPlan = Mantenimiento::where('status', 'PLANIFICADO')
        //     ->whereDate('fecha_programada', $today)
        //     ->count();

        // Realizados (Real): Asumimos que es el total de finalizados el día de hoy
        $mantenimientosReal = Orden::whereIn('tipo', [1,5])->where('estatus',1)
            ->whereDate('fecha_out', $today)
            ->count();
            
        $this->info("-> Mantenimientos: Planificados ({$mantenimientosPlan}), Realizados ({$mantenimientosReal})");


        // ----------------------------------------------------------------------
        // 3. CÁLCULO DE PLAN MODELS (Agrupación por Modelo de Vehículo)
        // ----------------------------------------------------------------------
        // Obtener los IDs de vehículos planificados para hoy
        // Contar por modelo (asumiendo que 'modelo' es un campo en la tabla 'vehiculos')
        $planModelsRaw = Vehiculo::where('km_mantt','>' ,4800)
            ->select('modelo', DB::raw('count(*) as total'))
            ->groupBy('modelo')
            ->pluck('total', 'modelo')
            ->toArray();
            
        $this->info("-> Modelos planificados: " . json_encode($planModelsRaw));


        // ----------------------------------------------------------------------
        // 4. CÁLCULO DE CONTEO (Efectividad de Almacén)
        // ----------------------------------------------------------------------
        // SIMULACIÓN: Asume que Inventario tiene registros diarios de conteo.
        // $totalIntentosConteo = Inventario::whereDate('fecha_conteo', $today)->count();
        // $conteoExitoso = Inventario::whereDate('fecha_conteo', $today)
        //     ->where('conteo_exitoso', true) // Asume este campo
        //     ->count();

        // $conteoEfectividad = $totalIntentosConteo > 0
        //     ? round(($conteoExitoso / $totalIntentosConteo) * 100, 2)
        //     : 0.00;

        // $this->info("-> Efectividad de Conteo: {$conteoEfectividad}%");
        
        
        // ----------------------------------------------------------------------
        // 5. ALMACENAR O ACTUALIZAR EL REGISTRO
        // ----------------------------------------------------------------------
        ResumenDiario::updateOrCreate(
            ['fecha' => $today],
            [
                'plan' => $mantenimientosPlan,
                'real' => $mantenimientosReal,
                'disponibilidad' => $disponibilidad,
                'conteo' => 0, //$conteoEfectividad,
                'plan_models' => $planModelsRaw,
            ]
        );

        $this->info('✅ Resumen diario guardado/actualizado exitosamente.');

        return Command::SUCCESS;
    }
}
