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

        // ------------------------------------------------------------------
        // 1. Gasto Total en Suministros
        // ------------------------------------------------------------------
        if (in_array('gasto_suministros', $indicators)) {
            $requerimientosData = SuministroCompra::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('estatus', [2, 3]) 
                ->with('detalles') // Cargar detalles para el listado
                ->get();
                
            $totalGasto = $requerimientosData->sum(function($req) {
                 return $req->detalles->sum(fn($d) => $d->costo_unitario_aprobado * $d->cantidad_aprobada);
            });
                
            $results['totals']['gasto_suministros'] = $totalGasto;
            $results['details']['gasto_suministros_data'] = $requerimientosData;
        }

        // ------------------------------------------------------------------
        // 2. Total Litros Despachados (Ventas)
        // ------------------------------------------------------------------
        if (in_array('ventas_litros', $indicators)) {
            $viajesData = Viaje::whereBetween('fecha_salida', [$startDate, $endDate])
                ->with(['despachos' => function($query) {
                    $query->with('cliente'); // Cargar la relación cliente del despacho
                }, 'vehiculo']) // Cargar vehículo del viaje
                ->get();

            $litrosVendidos = $viajesData->sum('despachos_sum_litros'); // $viajesData ya tiene la suma gracias a withSum
            
            // Recalcular la suma si no usaste withSum en la consulta principal (mejor usar withSum en la consulta principal)
            // Aquí lo hacemos manualmente para asegurar que funciona con la data cargada:
            $litrosVendidos = $viajesData->sum(fn($v) => $v->despachos->sum('litros'));
            
            $results['totals']['ventas_litros'] = $litrosVendidos;
            $results['details']['ventas_litros_data'] = $viajesData;

            // Lógica para Gráfico de Torta por Cliente
            $despachosPorCliente = $viajesData->pluck('despachos')->flatten() // Obtener todos los despachos en un array plano
                ->groupBy(function($despacho) {
                    // Agrupar por nombre de cliente registrado o por el campo 'otro_cliente'
                    return $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'Cliente No Especificado';
                })
                ->map(fn($group) => $group->sum('litros')) // Sumar los litros por cada grupo
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
            $clientesData = Cliente::whereBetween('created_at', [$startDate, $endDate])
                ->get(['id', 'nombre', 'direccion', 'created_at']);
                
            $results['totals']['nuevos_clientes'] = $clientesData->count();
            $results['details']['nuevos_clientes_data'] = $clientesData;
        }
        
        // ------------------------------------------------------------------
        // 5. Reportes de Falla/Mantenimiento
        // ------------------------------------------------------------------
         if (in_array('reportes_falla', $indicators)) {
            $ordenesFallaData = Orden::whereIn('tipo', ['Mantenimiento', 'Falla'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with('vehiculo') // Cargar el vehículo para agrupar
                ->get();
                
            $results['totals']['reportes_falla'] = $ordenesFallaData->count();
            // Para el detalle, es suficiente la colección con el vehículo cargado.
            $results['details']['reportes_falla_data'] = $ordenesFallaData;
        }

        // El resultado AJAX final ahora contiene Totales y Detalles
        return response()->json($results);
    }

}