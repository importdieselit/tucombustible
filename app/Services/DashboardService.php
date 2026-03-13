<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\TankRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\PurchaseRepository;

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
            $cliente = $user->cliente;

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

            $esPadre = ($cliente->parent == 0);
            $sucursales = $esPadre ? $this->clientRepo->getSucursales($cliente->id) : collect();

            return [
                'perfil'   => $esPadre ? 'cliente_padre' : 'cliente_sucursal',
                'cliente'  => $cliente,
                'es_padre' => $esPadre,
                'sucursales' => $sucursales,
                'stats'    => [
                    'consumo_mes' => 0, 
                    'pedidos_activos' => 0,
                    'sucursales_vinculadas' => $sucursales->count()
                ]
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

    public function getNombrePaso($paso)
    {
        return \App\Models\Cliente::PASOS_REGISTRO[$paso] ?? 'Estatus Pendiente';
    }
    
}