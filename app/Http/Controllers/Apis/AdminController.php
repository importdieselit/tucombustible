<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\User;
use App\Models\MovimientoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Obtener estadísticas generales del sistema
     */
    public function getEstadisticasGenerales()
    {
        try {
            $estadisticas = [
                // Estadísticas de pedidos
                'total_pedidos' => Pedido::count(),
                'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
                'pedidos_aprobados' => Pedido::where('estado', 'aprobado')->count(),
                'pedidos_en_proceso' => Pedido::where('estado', 'en_proceso')->count(),
                'pedidos_completados' => Pedido::where('estado', 'completado')->count(),
                'pedidos_cancelados' => Pedido::where('estado', 'cancelado')->count(),
                
                // Estadísticas de clientes
                'total_clientes' => Cliente::count(),
                'clientes_con_disponible' => Cliente::where('disponible', '>', 0)->count(),
                'clientes_sin_disponible' => Cliente::where('disponible', '<=', 0)->count(),
                'clientes_principales' => Cliente::where('parent', 0)->count(),
                'sucursales' => Cliente::where('parent', '>', 0)->count(),
                'total_disponible_clientes_padres' => Cliente::where('parent', 0)->sum('disponible'),
                
                // Estadísticas de depósitos
                'total_depositos' => Deposito::count(),
                'capacidad_total' => Deposito::sum('capacidad_litros'),
                'disponible_total' => Deposito::sum('nivel_actual_litros'),
                'depositos_en_alerta' => Deposito::whereRaw('nivel_actual_litros <= nivel_alerta_litros')->count(),
                
                // Estadísticas de usuarios
                'total_usuarios' => User::count(),
                'usuarios_activos' => User::count(), // Todos activos por defecto
                'usuarios_inactivos' => 0, // No tenemos campo activo
                
                // Estadísticas de movimientos (últimos 30 días)
                'movimientos_ultimo_mes' => MovimientoCombustible::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
                'egresos_ultimo_mes' => MovimientoCombustible::where('tipo_movimiento', 'salida')
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->sum('cantidad_litros'),
                'ingresos_ultimo_mes' => MovimientoCombustible::where('tipo_movimiento', 'entrada')
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->sum('cantidad_litros'),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas generales obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de pedidos por período
     */
    public function getReportePedidos(Request $request)
    {
        try {
            $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
            $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

            $pedidos = Pedido::with(['cliente'])
                ->whereBetween('fecha_solicitud', [$fechaInicio, $fechaFin])
                ->orderBy('fecha_solicitud', 'desc')
                ->get()
                ->map(function ($pedido) {
                    return [
                        'id' => $pedido->id,
                        'cliente_id' => $pedido->cliente_id,
                        'cliente_nombre' => $pedido->cliente ? $pedido->cliente->nombre : 'Cliente no encontrado',
                        'cantidad_solicitada' => $pedido->cantidad_solicitada,
                        'cantidad_aprobada' => $pedido->cantidad_aprobada,
                        'cantidad_recibida' => $pedido->cantidad_recibida,
                        'estado' => $pedido->estado,
                        'fecha_solicitud' => $pedido->fecha_solicitud,
                        'fecha_aprobacion' => $pedido->fecha_aprobacion,
                        'fecha_completado' => $pedido->fecha_completado,
                        'calificacion' => $pedido->calificacion,
                    ];
                });

            // Estadísticas del período
            $estadisticas = [
                'total_pedidos' => $pedidos->count(),
                'pedidos_por_estado' => $pedidos->groupBy('estado')->map->count(),
                'cantidad_total_solicitada' => $pedidos->sum('cantidad_solicitada'),
                'cantidad_total_aprobada' => $pedidos->sum('cantidad_aprobada'),
                'cantidad_total_recibida' => $pedidos->sum('cantidad_recibida'),
                'promedio_calificacion' => $pedidos->where('calificacion', '!=', null)->avg('calificacion'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'pedidos' => $pedidos,
                    'estadisticas' => $estadisticas,
                    'periodo' => [
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                    ]
                ],
                'message' => 'Reporte de pedidos obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte de pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de clientes
     */
    public function getReporteClientes(Request $request)
    {
        try {
            $clientes = Cliente::with(['pedidos' => function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            }])
            ->get()
            ->map(function ($cliente) {
                $pedidosRecientes = $cliente->pedidos;
                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'email' => $cliente->email,
                    'telefono' => $cliente->telefono,
                    'disponible' => $cliente->disponible,
                    'parent' => $cliente->parent,
                    'es_cliente_principal' => $cliente->parent == 0,
                    'es_sucursal' => $cliente->parent > 0,
                    'pedidos_ultimo_mes' => $pedidosRecientes->count(),
                    'cantidad_solicitada_ultimo_mes' => $pedidosRecientes->sum('cantidad_solicitada'),
                    'cantidad_aprobada_ultimo_mes' => $pedidosRecientes->sum('cantidad_aprobada'),
                    'cantidad_recibida_ultimo_mes' => $pedidosRecientes->sum('cantidad_recibida'),
                    'ultimo_pedido' => $pedidosRecientes->max('fecha_solicitud'),
                ];
            });

            // Estadísticas de clientes
            $estadisticas = [
                'total_clientes' => $clientes->count(),
                'clientes_con_disponible' => $clientes->where('disponible', '>', 0)->count(),
                'clientes_sin_disponible' => $clientes->where('disponible', '<=', 0)->count(),
                'clientes_principales' => $clientes->where('es_cliente_principal', true)->count(),
                'sucursales' => $clientes->where('es_sucursal', true)->count(),
                'disponible_total' => $clientes->sum('disponible'),
                'clientes_con_pedidos_recientes' => $clientes->where('pedidos_ultimo_mes', '>', 0)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'clientes' => $clientes,
                    'estadisticas' => $estadisticas,
                ],
                'message' => 'Reporte de clientes obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte de clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de depósitos
     */
    public function getReporteDepositos(Request $request)
    {
        try {
            $depositos = Deposito::with(['movimientos' => function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            }])
            ->get()
            ->map(function ($deposito) {
                $movimientosRecientes = $deposito->movimientos;
                $porcentaje = $deposito->capacidad_litros > 0 
                    ? ($deposito->nivel_actual_litros / $deposito->capacidad_litros * 100)
                    : 0;
                
                return [
                    'id' => $deposito->id,
                    'serial' => $deposito->serial,
                    'product_type' => $deposito->product_type,
                    'producto' => $deposito->producto,
                    'capacidad_litros' => $deposito->capacidad_litros,
                    'nivel_actual_litros' => $deposito->nivel_actual_litros,
                    'nivel_alerta_litros' => $deposito->nivel_alerta_litros,
                    'porcentaje' => round($porcentaje, 2),
                    'estado' => $porcentaje > 50 ? 'optimo' : ($porcentaje > 25 ? 'bajo' : 'critico'),
                    'en_alerta' => $deposito->nivel_actual_litros <= $deposito->nivel_alerta_litros,
                    'movimientos_ultimo_mes' => $movimientosRecientes->count(),
                    'ingresos_ultimo_mes' => $movimientosRecientes->where('tipo_movimiento', 'entrada')->sum('cantidad_litros'),
                    'egresos_ultimo_mes' => $movimientosRecientes->where('tipo_movimiento', 'salida')->sum('cantidad_litros'),
                ];
            });

            // Estadísticas de depósitos
            $estadisticas = [
                'total_depositos' => $depositos->count(),
                'capacidad_total' => $depositos->sum('capacidad_litros'),
                'disponible_total' => $depositos->sum('nivel_actual_litros'),
                'porcentaje_promedio' => $depositos->avg('porcentaje'),
                'depositos_en_alerta' => $depositos->where('en_alerta', true)->count(),
                'depositos_optimos' => $depositos->where('estado', 'optimo')->count(),
                'depositos_bajos' => $depositos->where('estado', 'bajo')->count(),
                'depositos_criticos' => $depositos->where('estado', 'critico')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'depositos' => $depositos,
                    'estadisticas' => $estadisticas,
                ],
                'message' => 'Reporte de depósitos obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte de depósitos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de consumo
     */
    public function getReporteConsumo(Request $request)
    {
        try {
            $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
            $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

            // Movimientos de combustible en el período
            $movimientos = MovimientoCombustible::with(['deposito', 'cliente'])
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($movimiento) {
                    return [
                        'id' => $movimiento->id,
                        'tipo_movimiento' => $movimiento->tipo_movimiento,
                        'cantidad_litros' => $movimiento->cantidad_litros,
                        'deposito_serial' => $movimiento->deposito ? $movimiento->deposito->serial : 'N/A',
                        'cliente_nombre' => $movimiento->cliente ? $movimiento->cliente->nombre : 'N/A',
                        'observaciones' => $movimiento->observaciones,
                        'fecha' => $movimiento->created_at,
                    ];
                });

            // Estadísticas de consumo
            $estadisticas = [
                'total_movimientos' => $movimientos->count(),
                'total_ingresos' => $movimientos->where('tipo_movimiento', 'entrada')->sum('cantidad_litros'),
                'total_egresos' => $movimientos->where('tipo_movimiento', 'salida')->sum('cantidad_litros'),
                'balance_neto' => $movimientos->where('tipo_movimiento', 'entrada')->sum('cantidad_litros') - 
                                 $movimientos->where('tipo_movimiento', 'salida')->sum('cantidad_litros'),
                'promedio_diario_egresos' => $movimientos->where('tipo_movimiento', 'salida')->sum('cantidad_litros') / 
                                           max(1, Carbon::parse($fechaInicio)->diffInDays(Carbon::parse($fechaFin))),
            ];

            // Consumo por depósito
            $consumoPorDeposito = $movimientos->where('tipo_movimiento', 'salida')
                ->groupBy('deposito_serial')
                ->map(function ($movimientosDeposito) {
                    return [
                        'deposito' => $movimientosDeposito->first()['deposito_serial'],
                        'total_egresos' => $movimientosDeposito->sum('cantidad_litros'),
                        'cantidad_movimientos' => $movimientosDeposito->count(),
                    ];
                })
                ->values();

            // Consumo por cliente
            $consumoPorCliente = $movimientos->where('tipo_movimiento', 'salida')
                ->groupBy('cliente_nombre')
                ->map(function ($movimientosCliente) {
                    return [
                        'cliente' => $movimientosCliente->first()['cliente_nombre'],
                        'total_consumido' => $movimientosCliente->sum('cantidad_litros'),
                        'cantidad_movimientos' => $movimientosCliente->count(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'movimientos' => $movimientos,
                    'estadisticas' => $estadisticas,
                    'consumo_por_deposito' => $consumoPorDeposito,
                    'consumo_por_cliente' => $consumoPorCliente,
                    'periodo' => [
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                    ]
                ],
                'message' => 'Reporte de consumo obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte de consumo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener dashboard completo del administrador
     */
    public function getDashboard()
    {
        try {
            $estadisticas = $this->getEstadisticasGenerales()->getData(true)['data'];
            
            // Pedidos recientes (últimos 10)
            $pedidosRecientes = Pedido::with(['cliente'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($pedido) {
                    return [
                        'id' => $pedido->id,
                        'cliente_nombre' => $pedido->cliente ? $pedido->cliente->nombre : 'Cliente no encontrado',
                        'cantidad_solicitada' => $pedido->cantidad_solicitada,
                        'estado' => $pedido->estado,
                        'fecha_solicitud' => $pedido->fecha_solicitud,
                    ];
                });

            // Depósitos en alerta
            $depositosEnAlerta = Deposito::whereRaw('nivel_actual_litros <= nivel_alerta_litros')
                ->orderBy('nivel_actual_litros', 'asc')
                ->limit(5)
                ->get()
                ->map(function ($deposito) {
                    $porcentaje = $deposito->capacidad_litros > 0 
                        ? ($deposito->nivel_actual_litros / $deposito->capacidad_litros * 100)
                        : 0;
                    
                    return [
                        'id' => $deposito->id,
                        'serial' => $deposito->serial,
                        'producto' => $deposito->producto,
                        'nivel_actual' => $deposito->nivel_actual_litros,
                        'capacidad' => $deposito->capacidad_litros,
                        'porcentaje' => round($porcentaje, 2),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'estadisticas' => $estadisticas,
                    'pedidos_recientes' => $pedidosRecientes,
                    'depositos_en_alerta' => $depositosEnAlerta,
                ],
                'message' => 'Dashboard obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}
