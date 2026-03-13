<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\TankRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\PurchaseRepository;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\ClienteCupo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected $vehicleRepo, $orderRepo, $userRepo, $tankRepo, $maintenanceRepo, $purchaseRepo, $clientRepo;

    public function __construct(
        VehicleRepository $vehicleRepo,
        OrderRepository $orderRepo,
        UserRepository $userRepo,
        TankRepository $tankRepo,
        MaintenanceRepository $maintenanceRepo,
        PurchaseRepository $purchaseRepo,
        ClienteRepository $clientRepo
    ) {
        $this->vehicleRepo = $vehicleRepo;
        $this->orderRepo = $orderRepo;
        $this->userRepo = $userRepo;
        $this->tankRepo = $tankRepo;
        $this->maintenanceRepo = $maintenanceRepo;
        $this->purchaseRepo = $purchaseRepo;
        $this->clientRepo = $clientRepo;
    }

    public function getDashboardData($user)
    {
        if ($user->id_perfil == 3) {
            $cliente = Cliente::find($user->cliente_id);

            if (!$cliente) {
                return [
                    'perfil' => 'cliente_sin_vincular',
                    'mensaje' => 'No se encontró un expediente asociado.'
                ];
            }

            $pasoActual = $cliente->registro_paso;

            if ($pasoActual < 10) {
                return [
                    'perfil' => 'cliente_proceso',
                    'paso_actual' => $pasoActual,
                    'nombre_paso' => $this->getNombrePaso($pasoActual),
                    'porcentaje' => ($pasoActual / 10) * 100,
                    'cliente' => $cliente
                ];
            }

            // --- LÓGICA DE JERARQUÍA (FASE 3) ---
            // Un cliente es padre si su columna 'parent' es 0 o NULL
            $esPadre = ($cliente->parent == 0 || is_null($cliente->parent));
            
            // Si es padre, traemos sus sucursales con información de su progreso de registro
            $sucursales = $esPadre 
                ? Cliente::where('parent', $cliente->id)
                    ->withCount('documentos')
                    ->orderBy('nombre', 'asc')
                    ->get() 
                : collect();
            
            // Los IDs para el historial incluyen al padre y a todas sus sucursales vinculadas
            $idsClientesParaHistorial = $esPadre 
                ? $sucursales->pluck('id')->push($cliente->id)->toArray() 
                : [$cliente->id];

            // --- OBTENER PEDIDOS ---
            $pedidos = Pedido::whereIn('cliente_id', $idsClientesParaHistorial)
                ->with('cliente')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // --- LÓGICA DE KPIs CORREGIDA SEGÚN DDL ---
            // Sumamos 'cantidad_solicitada' porque 'cantidad_aprobada' NO existe en pedidos
            $consumidoTotal = (float) Pedido::whereIn('cliente_id', $idsClientesParaHistorial)
                ->where('estado', 'completado')
                ->sum('cantidad_solicitada');
            
            // Obtenemos el cupo real de la tabla cliente_cupos para mayor precisión
            $cupoData = ClienteCupo::where('cliente_id', $cliente->id)->first();
            $cupoAsignado = $cupoData ? (float)$cupoData->litros_aprobados : 0;
            
            // El disponible es el cupo total menos lo consumido en el mes (lógica dinámica)
            $consumidoMesActual = Pedido::where('cliente_id', $cliente->id)
                ->whereIn('estado', ['pendiente', 'aprobado', 'en_proceso', 'completado'])
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('cantidad_solicitada');

            $disponible = $cupoAsignado - $consumidoMesActual;

            $pedidosActivos = Pedido::whereIn('cliente_id', $idsClientesParaHistorial)
                ->whereIn('estado', ['pendiente', 'aprobado', 'en_proceso'])
                ->count();

            return [
                'perfil'   => $esPadre ? 'cliente_padre' : 'cliente_sucursal',
                'cliente'  => $cliente,
                'es_padre' => $esPadre,
                'sucursales' => $sucursales,
                'pedidos'  => $pedidos,
                'stats'    => [
                    'cupo'            => $cupoAsignado,
                    'disponible'      => $disponible < 0 ? 0 : $disponible,
                    'consumido'       => $consumidoTotal,
                    'pedidos_activos' => $pedidosActivos,
                    'sucursales_vinculadas' => $sucursales->count()
                ],
                'chartData' => $this->getChartData($cliente->id)
            ];
        }

        return [
            'perfil' => 'admin_sistema',
            'stats' => [
                'totalVehiculos' => $this->vehicleRepo->countAll(),
                'totalUsuarios' => $this->userRepo->countAll(),
                'totalOrdenesAbiertas' => $this->orderRepo->countAbiertas(),
                'totalTanques' => $this->tankRepo->countAll(),
                'programadosHoy' => $this->maintenanceRepo->countHoy(),
                'clientes_activos'      => $this->clientRepo->countByPaso(10),
                'clientes_en_registro'  => $this->clientRepo->countByPaso('<', 10),
            ],
            'ultimasOrdenes' => $this->orderRepo->getUltimas(5)
        ];
    }

    private function getChartData($clienteId)
    {
        $cupos = ClienteCupo::where('cliente_id', $clienteId)->get();
        $data = [];
        
        foreach($cupos as $c) {
            // CORRECCIÓN: 'deposito_id' en lugar de 'tipo_combustible_id' para match con tabla pedidos
            $consumidoPorTipo = (float) Pedido::where('cliente_id', $clienteId)
                ->where('estado', 'completado')
                ->where('deposito_id', $c->tipo_combustible_id) 
                ->sum('cantidad_solicitada');

            $data[] = [
                'name' => $c->tipo_combustible_id == 1 ? 'DIESEL' : 'MGO',
                'consumido' => $consumidoPorTipo,
                'disponible' => (float)($c->litros_aprobados - $consumidoPorTipo)
            ];
        }
        return $data;
    }

    public function getNombrePaso($paso)
    {
        // Se accede a la constante del modelo Cliente
        return \App\Models\Cliente::PASOS_REGISTRO[$paso] ?? 'Estatus Pendiente';
    }
}