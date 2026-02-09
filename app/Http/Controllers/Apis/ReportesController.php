<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\MovimientoCombustible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportesController extends Controller
{
    /**
     * Generar reporte general de combustibles
     */
    public function generarReporteGeneral(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_desde' => 'required|date',
                'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
                'cliente_id' => 'nullable|integer|exists:clientes,id',
                'formato' => 'nullable|in:json,pdf,excel'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fechaDesde = Carbon::parse($request->fecha_desde)->startOfDay();
            $fechaHasta = Carbon::parse($request->fecha_hasta)->endOfDay();
            $clienteId = $request->cliente_id;

            // Obtener datos del reporte
            $reporte = $this->obtenerDatosReporte($fechaDesde, $fechaHasta, $clienteId);

            return response()->json([
                'success' => true,
                'data' => $reporte,
                'message' => 'Reporte generado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos del reporte
     */
    private function obtenerDatosReporte($fechaDesde, $fechaHasta, $clienteId = null)
    {
        // Obtener clientes
        $query = Cliente::query();
        
        if ($clienteId) {
            $query->where('id', $clienteId);
        }

        $clientes = $query->with(['pedidos' => function($q) use ($fechaDesde, $fechaHasta) {
            $q->whereBetween('created_at', [$fechaDesde, $fechaHasta])
              ->where('estado', 'completado'); // Solo pedidos completados
        }])->get();

        // Obtener movimientos de combustible con depósitos
        $movimientosQuery = MovimientoCombustible::with(['cliente', 'deposito'])
            ->whereBetween('created_at', [$fechaDesde, $fechaHasta]);

        if ($clienteId) {
            $movimientosQuery->where('cliente_id', $clienteId);
        }

        $movimientos = $movimientosQuery->get();

        // Obtener pedidos (solo completados para el reporte)
        $pedidosQuery = Pedido::with(['cliente'])
            ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
            ->where('estado', 'completado'); // Solo pedidos completados

        if ($clienteId) {
            $pedidosQuery->where('cliente_id', $clienteId);
        }

        $pedidos = $pedidosQuery->get();

        // Calcular estadísticas
        $estadisticas = $this->calcularEstadisticas($clientes, $movimientos, $pedidos);

        // Formatear datos para el reporte
        $datosReporte = $this->formatearDatosReporte($clientes, $movimientos, $pedidos);

        // Obtener productos disponibles dinámicamente
        $productosDisponibles = \DB::table('depositos')
            ->select('producto')
            ->whereNotNull('producto')
            ->distinct()
            ->orderBy('producto')
            ->pluck('producto')
            ->toArray();

        return [
            'periodo' => [
                'desde' => $fechaDesde->format('d/m/Y'),
                'hasta' => $fechaHasta->format('d/m/Y'),
                'dias' => $fechaDesde->diffInDays($fechaHasta) + 1
            ],
            'filtros' => [
                'cliente_id' => $clienteId,
                'cliente_nombre' => $clienteId ? Cliente::find($clienteId)->nombre : 'Todos los clientes'
            ],
            'productos_disponibles' => $productosDisponibles,
            'estadisticas' => $estadisticas,
            'datos' => $datosReporte,
            'resumen' => $this->generarResumen($estadisticas, $datosReporte)
        ];
    }

    /**
     * Calcular estadísticas del reporte
     */
    private function calcularEstadisticas($clientes, $movimientos, $pedidos)
    {
        $totalClientes = $clientes->count();
        $totalPedidos = $pedidos->count();
        $totalMovimientos = $movimientos->count();
        
        // Obtener productos disponibles dinámicamente
        $productosDisponibles = \DB::table('depositos')
            ->select('producto')
            ->whereNotNull('producto')
            ->distinct()
            ->pluck('producto')
            ->toArray();
        
        // Calcular totales por tipo de producto basado en depósitos reales
        $totalesPorProducto = [];
        foreach ($productosDisponibles as $producto) {
            $totalesPorProducto[strtolower(str_replace('.', '_', $producto))] = 0;
        }
        
        foreach ($movimientos as $movimiento) {
            if ($movimiento->deposito && $movimiento->deposito->producto) {
                $producto = $movimiento->deposito->producto;
                $cantidad = $movimiento->cantidad_litros;
                $key = strtolower(str_replace('.', '_', $producto));
                
                if (isset($totalesPorProducto[$key])) {
                    $totalesPorProducto[$key] += $cantidad;
                }
            }
        }

        // Ya que filtramos solo completados, todos los pedidos son completados
        $pedidosCompletados = $pedidos->count();
        $pedidosAprobados = 0; // No incluimos otros estados en el reporte
        $pedidosPendientes = 0;
        $pedidosCancelados = 0;

        // Calcular total de combustibles
        $totalCombustibles = array_sum($totalesPorProducto);
        
        // Agregar campos de compatibilidad con valores 0
        $combustibles = array_merge($totalesPorProducto, [
            'gasolina_91' => 0, // Mantenemos para compatibilidad pero siempre 0
            'gasolina_95' => 0, // Mantenemos para compatibilidad pero siempre 0
            'kerosen' => 0, // Mantenemos para compatibilidad pero siempre 0
            'total' => $totalCombustibles
        ]);

        return [
            'total_clientes' => $totalClientes,
            'total_pedidos' => $totalPedidos,
            'total_movimientos' => $totalMovimientos,
            'combustibles' => $combustibles,
            'pedidos' => [
                'completados' => $pedidosCompletados,
                'aprobados' => $pedidosAprobados,
                'pendientes' => $pedidosPendientes,
                'cancelados' => $pedidosCancelados,
                'total' => $totalPedidos
            ]
        ];
    }

    /**
     * Formatear datos para el reporte
     */
    private function formatearDatosReporte($clientes, $movimientos, $pedidos)
    {
        $datos = [];

        foreach ($clientes as $cliente) {
            $clienteMovimientos = $movimientos->where('cliente_id', $cliente->id);
            $clientePedidos = $pedidos->where('cliente_id', $cliente->id);

            // Obtener productos disponibles dinámicamente
            $productosDisponibles = \DB::table('depositos')
                ->select('producto')
                ->whereNotNull('producto')
                ->distinct()
                ->pluck('producto')
                ->toArray();
            
            // Calcular totales por tipo de combustible basado en depósitos reales
            $totalesPorProductoCliente = [];
            foreach ($productosDisponibles as $producto) {
                $key = 'total_' . strtolower(str_replace('.', '_', $producto));
                $totalesPorProductoCliente[$key] = 0;
            }
            
            foreach ($clienteMovimientos as $movimiento) {
                if ($movimiento->deposito && $movimiento->deposito->producto) {
                    $producto = $movimiento->deposito->producto;
                    $cantidad = $movimiento->cantidad_litros;
                    $key = 'total_' . strtolower(str_replace('.', '_', $producto));
                    
                    if (isset($totalesPorProductoCliente[$key])) {
                        $totalesPorProductoCliente[$key] += $cantidad;
                    }
                }
            }
            
            // Para compatibilidad con el frontend, mantenemos gasolinas en 0
            $totalesPorProductoCliente['total_gasolina_91'] = 0;
            $totalesPorProductoCliente['total_gasolina_95'] = 0;
            $totalesPorProductoCliente['total_kerosen'] = 0;

            // Construir el array de datos del cliente
            $datosCliente = [
                'numero' => $datos ? count($datos) + 1 : 1,
                'responsable' => $cliente->contacto ?? 'N/A',
                'ci_responsable' => $cliente->dni ?? 'N/A',
                'razon_social' => $cliente->nombre,
                'rif_contribuyente' => $cliente->rif ?? 'N/A',
                'domicilio_fiscal' => $cliente->direccion ?? 'N/A',
                'total_pedidos' => $clientePedidos->count(), // Solo completados
                'pedidos_completados' => $clientePedidos->count(), // Todos son completados
                'pedidos_aprobados' => 0, // No incluimos otros estados
                'disponible_actual' => $cliente->disponible,
                'cupo_total' => $cliente->cupo
            ];
            
            // Agregar totales por producto dinámicamente
            $datosCliente = array_merge($datosCliente, $totalesPorProductoCliente);
            
            $datos[] = $datosCliente;
        }

        return $datos;
    }

    /**
     * Generar resumen del reporte
     */
    private function generarResumen($estadisticas, $datosReporte)
    {
        $totalVolumenes = $estadisticas['combustibles']['total'];
        $promedioPorCliente = $estadisticas['total_clientes'] > 0 
            ? $totalVolumenes / $estadisticas['total_clientes'] 
            : 0;

        return [
            'total_volumenes_combustibles' => $totalVolumenes,
            'promedio_por_cliente' => round($promedioPorCliente, 2),
            'cliente_mayor_consumo' => $this->obtenerClienteMayorConsumo($datosReporte),
            'distribucion_combustibles' => $estadisticas['combustibles']
        ];
    }

    /**
     * Obtener cliente con mayor consumo
     */
    private function obtenerClienteMayorConsumo($datosReporte)
    {
        $clienteMayor = null;
        $mayorConsumo = 0;

        foreach ($datosReporte as $cliente) {
            $consumoTotal = 0;
            
            // Sumar todos los campos que empiecen con 'total_' y sean productos
            foreach ($cliente as $key => $value) {
                if (strpos($key, 'total_') === 0 && is_numeric($value) && $key !== 'total_pedidos') {
                    $consumoTotal += $value;
                }
            }

            if ($consumoTotal > $mayorConsumo) {
                $mayorConsumo = $consumoTotal;
                $clienteMayor = $cliente;
            }
        }

        return $clienteMayor ? [
            'nombre' => $clienteMayor['razon_social'],
            'consumo_total' => $mayorConsumo
        ] : null;
    }

    /**
     * Obtener lista de clientes para filtros
     */
    public function obtenerClientesParaFiltro(): JsonResponse
    {
        try {
            $clientes = Cliente::select('id', 'nombre', 'contacto', 'rif')
                ->orderBy('nombre', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $clientes,
                'message' => 'Clientes obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de productos disponibles en depósitos
     */
    public function obtenerProductosDisponibles(): JsonResponse
    {
        try {
            $productos = \DB::table('depositos')
                ->select('producto')
                ->whereNotNull('producto')
                ->distinct()
                ->orderBy('producto')
                ->pluck('producto');

            return response()->json([
                'success' => true,
                'data' => $productos,
                'message' => 'Productos obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar PDF del reporte
     */
    public function generarPdfReporte(Request $request): JsonResponse
    {
        try {
            // Esta función se implementará con una librería de PDF como DomPDF
            // Por ahora retornamos los datos para que el frontend los procese
            
            $reporte = $this->generarReporteGeneral($request);
            
            return response()->json([
                'success' => true,
                'data' => $reporte->getData(),
                'message' => 'Datos del reporte listos para generar PDF'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
