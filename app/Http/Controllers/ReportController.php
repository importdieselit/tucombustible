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
use App\Models\CaptacionCliente; // Para nuevos clientes
use PDF; // Para generación de PDF
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
        // ... (Validación y obtención de fechas sin cambios) ...

        [$startDate, $endDate] = $this->getDateRange(
            $request->range, 
            $request->start_date, 
            $request->end_date
        );

        if (!$startDate || !$endDate) {
             return response()->json(['message' => 'Rango de fechas no válido.'], 400);
        }
        
        $results = [
            'totals' => [],
            'details' => [],
            'indicators' => $request->indicators // Devolver los indicadores solicitados para el JS
        ];
        $indicators = $request->indicators;

        $results['report_dates'] = [ // <-- NUEVO: Exponemos las fechas
            'start_date' => $startDate->toDateString(), 
            'end_date' => $endDate->toDateString(),
        ];
        // ------------------------------------------------------------------
        // 1. Gasto Total en Suministros
        // ------------------------------------------------------------------
        if (in_array('gasto_suministros', $indicators)) {
            $requerimientosData = SuministroCompra::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('estatus', [2, 3]) 
                ->with('detalles') // Cargar detalles para el listado
                ->get();
                
            $totalGasto = $requerimientosData->sum(function($req) {
                 return $req->detalles->sum(fn($d) => $d->costo_unitario_aprobado * $d->cantidad_solicitada);
            });
                
            $results['totals']['gasto_suministros'] = $totalGasto;
            $results['details']['gasto_suministros_data'] = $requerimientosData;
        }

        // ------------------------------------------------------------------
        // 2. Total Litros Despachados (Ventas)
        // ------------------------------------------------------------------
       // ------------------------------------------------------------------
    // Base Query para Combustible (Excluye Fletes de Compra y Venta)
    // ------------------------------------------------------------------
    $baseQuery = Viaje::whereBetween('fecha_salida', [$startDate, $endDate])
        // Excluir Fletes de la ciudad destino
        ->where('destino_ciudad', 'NOT LIKE', 'FLETE%'); 


    // ------------------------------------------------------------------
    // 2A. Total Litros Comprados (Purchases)
    // ------------------------------------------------------------------
    if (in_array('compras_litros', $indicators)) {
        // Viajes que SÍ tienen un registro en CompraCombustible
        $comprasLitrosQuery = (clone $baseQuery)->has('compraCombustible');
        
        // 1. CÁLCULO DEL TOTAL (Sumamos directamente los litros de la tabla de compras)
        $viajeIdsCompra = (clone $comprasLitrosQuery)->pluck('id');
        $litrosComprados = CompraCombustible::whereIn('viaje_id', $viajeIdsCompra)->sum('cantidad_litros');
            
        $results['totals']['compras_litros'] = $litrosComprados;

        // 2. CARGA DE DETALLES
        $comprasData = (clone $comprasLitrosQuery)
            ->with(['compraCombustible', 'vehiculo'])
            ->get();
        
        $results['details']['compras_litros_data'] = $comprasData;
    }


    // ------------------------------------------------------------------
    // 2B. Total Litros Vendidos (Sales)
    // ------------------------------------------------------------------
    if (in_array('ventas_litros', $indicators)) {
        // Viajes que NO tienen un registro en CompraCombustible
        $ventasLitrosQuery = (clone $baseQuery)->doesntHave('compraCombustible');
        
        // 1. CÁLCULO DEL TOTAL (OPTIMIZADO a nivel de DB: Suma los despachos de viajes de Venta)
        $litrosVendidos = (clone $ventasLitrosQuery)
            ->withSum('despachos', 'litros')
            ->get()
            ->sum('despachos_sum_litros');
            
        $results['totals']['ventas_litros'] = $litrosVendidos;

        // 2. CARGA DE DETALLES PARA LA TABLA Y EL GRÁFICO
        $ventasData = (clone $ventasLitrosQuery)
            ->with(['despachos' => function($query) {
                $query->with('cliente'); // Cargar la relación cliente del despacho
            }, 'vehiculo'])
            ->get();
        
        $results['details']['ventas_litros_data'] = $ventasData;

        // Lógica para Gráfico de Torta por Cliente (SOLO VENTAS)
        $despachosPorCliente = $ventasData->pluck('despachos')->flatten() 
            ->groupBy(function($despacho) {
                return $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'Cliente No Especificado';
            })
            ->map(fn($group) => $group->sum('litros'))
            ->sortDesc()
            ->toArray();
            
        $results['details']['despachos_by_client_data'] = $despachosPorCliente;
    }
        
        // ------------------------------------------------------------------
        // 3. Órdenes Abiertas (Conteo)
        // ------------------------------------------------------------------
        if (in_array('ordenes_abiertas', $indicators)) {
            $ordenesAbiertas = Orden::where('estatus', 2)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
                
            $results['totals']['ordenes_abiertas'] = $ordenesAbiertas;
        }
        
        // ------------------------------------------------------------------
        // 4. Nuevos Clientes Registrados
        // ------------------------------------------------------------------
        if (in_array('nuevos_clientes', $indicators)) {
            $clientesData = CaptacionCliente::whereBetween('created_at', [$startDate, $endDate])
                ->get(['id', 'razon_social as nombre', 'direccion', 'created_at']);
                
            $results['totals']['nuevos_clientes'] = $clientesData->count();
            $results['details']['nuevos_clientes_data'] = $clientesData;
        }
        
        // ------------------------------------------------------------------
        // 5. Reportes de Falla/Mantenimiento
        // ------------------------------------------------------------------
         if (in_array('reportes_falla', $indicators)) {
            $ordenesFallaData = Orden::whereBetween('created_at', [$startDate, $endDate])
                ->with('vehiculoBelong') // La relación es opcional, Laravel maneja el LEFT JOIN automáticamente
                ->get();
                
            $results['totals']['reportes_falla'] = $ordenesFallaData->count();
            
            // Agrupar las órdenes por Placa/Flota o 'N/A' si no tienen vehículo
            $reportesAgrupados = $ordenesFallaData->groupBy(function($orden) {
                // Usamos null-safe operator (PHP 8) o chequeo manual (PHP 7.4)
                // PHP 8: return $orden->vehiculo?->placa ?? 'N/A (Sin Unidad)';
                
                // PHP 7.4 y anteriores (más seguro en entornos variados):
                if ($orden->vehiculoBelong) {
                    return "{$orden->vehiculoBelong->flota} ({$orden->vehiculoBelong->placa})";
                }
                return 'N/A (Sin Unidad)';
            })
            ->map(function($group) {
                $vehiculoId = $group->first()->vehiculoBelong->id ?? 0; // 0 si no tiene unidad
                return [
                    'count' => $group->count(),
                    'vehiculo_id' => $vehiculoId,
                    'ordenes' => $group->pluck('nro_orden', 'id')->toArray() // Devolvemos IDs y Nro. de Orden
                ];
            })
            ->sortDesc()
            ->toArray();
            
            $results['details']['reportes_falla_data'] = $ordenesFallaData; // Se usa para la tabla de listado
            $results['details']['reportes_falla_grouped'] = $reportesAgrupados; // Se usa para el nuevo agrupamiento visual
        }

        // El resultado AJAX final ahora contiene Totales y Detalles
        return response()->json($results);
    }

    
public function exportPdf(Request $request)
{
    // Reutilizar la validación
    $request->validate([
        'range' => 'required|string|in:day,week,month,custom',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        // Aquí NO validamos indicators, solo necesitamos la data que venga
        'indicators' => 'nullable|array', 
        'indicators.*' => 'string',
    ]);

    // Reutilizar el método getSummary, pero necesitamos la lógica que genera $results.
    // Lo más eficiente es llamar a la lógica de getSummary aquí para generar $results.
    // Si la lógica de getSummary está en un método privado auxiliar, la reutilizas directamente.
    
    // --- Lógica de getSummary replicada/llamada ---
    [$startDate, $endDate] = $this->getDateRange(
        $request->range, 
        $request->start_date, 
        $request->end_date
    );

    // *Aquí llamas o replicamos la lógica completa de tu getSummary()*
    // Por ejemplo:
    $results = $this->generateReportData($request, $startDate, $endDate); // Asumiendo que moves la lógica a este helper

    // Si no puedes mover la lógica a un helper, pon toda la lógica de getSummary (sin el return json) aquí.

    // Añadir fechas para el título y metadatos
    $results['report_dates'] = [
        'start_date' => $startDate->toDateString(), 
        'end_date' => $endDate->toDateString(),
    ];
    
    // -----------------------------------------------------

    $pdf = PDF::loadView('reports.pdf_template', $results);

    $filename = 'Reporte_Gerencial_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.pdf';
    
    return $pdf->download($filename);
}



}