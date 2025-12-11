<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Viaje; // Para ventas/litros
use App\Models\Orden; // Para órdenes abiertas y fallas
use App\Models\Cliente; // Para nuevos clientes
use App\Models\SuministroCompra; // Para gasto de suministros
use App\Models\SuministroCompraDetalle; // Para detalles de suministros
use App\Models\CompraCombustible; // Para gasto de combustible

use Illuminate\View\View; // Para tipado de retorno

class ReportController extends Controller
{
    /**
     * Muestra la vista principal del Resumen Gerencial y Reportes.
     */
    public function index(): View
    {
        // En este caso, solo necesitamos devolver la vista.
        // Toda la lógica de carga de datos es asíncrona (AJAX) 
        // y se maneja en getSummary().
        return view('reports.index');
    }

    /**
     * Define las fechas de inicio y fin basadas en el rango y las fechas personalizadas.
     */
    protected function getDateRange(string $range, ?string $start = null, ?string $end = null): array
    {
        // ... (resto del método getDateRange) ...
        $startDate = null;
        $endDate = now();

        switch ($range) {
            case 'day':
                $startDate = now()->startOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                break;
            case 'custom':
                if ($start && $end) {
                    $startDate = Carbon::parse($start)->startOfDay();
                    $endDate = Carbon::parse($end)->endOfDay();
                }
                break;
        }

        return [$startDate, $endDate];
    }
    
    /**
     * Genera el resumen gerencial basado en los filtros (método AJAX).
     */
    public function getSummary(Request $request)
    {
        // ... (resto del método getSummary) ...
        $request->validate([
            'range' => 'required|string|in:day,week,month,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'indicators' => 'required|array',
            'indicators.*' => 'string', 
        ]);

        [$startDate, $endDate] = $this->getDateRange(
            $request->range, 
            $request->start_date, 
            $request->end_date
        );

        if (!$startDate || !$endDate) {
             return response()->json(['message' => 'Rango de fechas no válido.'], 400);
        }
        
        $results = [];
        $indicators = $request->indicators;

        // Lógica de cálculo de indicadores (igual a la provista anteriormente)
        
        // 1. Gasto Total en Suministros (Gasto total por requerimientos aprobados/recibidos)
        if (in_array('gasto_suministros', $indicators)) {
            $totalGasto = SuministroCompra::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('estatus', [2, 3]) 
                ->withSum('detalles', 'costo_unitario_aprobado') 
                ->get()
                ->sum('detalles_sum_costo_unitario_aprobado'); 
                
            $results['gasto_suministros'] = $totalGasto;
        }

        // 2. Total Litros Despachados (Ventas)
        if (in_array('ventas_litros', $indicators)) {
            $litrosVendidos = Viaje::whereBetween('fecha_salida', [$startDate, $endDate])
                ->withSum('despachos', 'litros')
                ->get()
                ->sum('despachos_sum_litros');
                
            $results['ventas_litros'] = $litrosVendidos;
        }
        
        // 3. Órdenes Abiertas (Conteo)
        if (in_array('ordenes_abiertas', $indicators)) {
            $ordenesAbiertas = Orden::where('estatus', 2)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
                
            $results['ordenes_abiertas'] = $ordenesAbiertas;
        }
        
        // 4. Nuevos Clientes Registrados
        if (in_array('nuevos_clientes', $indicators)) {
            $nuevosClientes = Cliente::whereBetween('created_at', [$startDate, $endDate])
                ->count();
                
            $results['nuevos_clientes'] = $nuevosClientes;
        }
        
        // 5. Reportes de Falla/Mantenimiento
         if (in_array('reportes_falla', $indicators)) {
            $reportesFalla = Orden::whereBetween('created_at', [$startDate, $endDate])
                ->count();
                //whereIn('tipo', ['Mantenimiento', 'Falla'])
                
            $results['reportes_falla'] = $reportesFalla;
        }

        return response()->json($results);
    }
}