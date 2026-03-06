<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\TankRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\PurchaseRepository;
use Illuminate\Support\Facades\Auth;

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
        // --- LÓGICA PARA CLIENTES (id_perfil = 3) ---
        if ($user->id_perfil == 3) {
            $cliente = $user->cliente;
            $pasoActual = $cliente->registro_paso;

            // CASO: CLIENTE EN PROCESO DE REGISTRO (Dashboard Nivel 0)
            if ($pasoActual < 10) {
                return [
                    'perfil' => 'cliente_proceso',
                    'paso_actual' => $pasoActual,
                    'nombre_paso' => $this->getNombrePaso($pasoActual),
                    'porcentaje' => ($pasoActual / 10) * 100,
                    'cliente' => $cliente
                ];
            }

            // CASO: CLIENTE ACTIVO (Padre vs Sucursal)
            $esPadre = ($cliente->parent == 0);
            return [
                'perfil'  => $esPadre ? 'cliente_padre' : 'cliente_sucursal',
                'cliente' => $cliente,
                'es_padre' => $esPadre,
                'stats'   => [
                    'consumo_mes' => 0, 
                    'pedidos_activos' => 0,
                    'sucursales_vinculadas' => $esPadre ? $cliente->sucursales()->count() : 0
                ]
            ];
        }

        // --- LÓGICA PARA ADMIN / SUPER (Dashboard Principal del Sistema) ---
        return [
            'perfil' => 'admin_sistema',
            'stats' => [
                'totalVehiculos' => $this->vehicleRepo->countAll(),
                'totalUsuarios' => $this->userRepo->countAll(),
                'totalOrdenesAbiertas' => $this->orderRepo->countAbiertas(),
                'totalTanques' => $this->tankRepo->countAll(),
                'programadosHoy' => $this->maintenanceRepo->countHoy(),
                
                // Stats de Gestión de Clientes (Sin usar la palabra "prospecto")
                'clientes_activos'      => $this->clientRepo->countByPaso(10),
                'clientes_en_registro'  => $this->clientRepo->countByPaso('<', 10),
            ],
            'ultimasOrdenes' => $this->orderRepo->getUltimas(5)
        ];
    }

    public function getNombrePaso($paso)
    {
        $pasos = [
            1  => 'Registro Inicial',
            2  => 'Envío de Planillas',
            3  => 'Recepción de Documentos',
            4  => 'Documentos en Revisión',
            5  => 'Carpeta Realizada',
            6  => 'Expediente enviado al Ministerio de Hidrocarburos',
            7  => 'Esperando Respuesta del Ministerio',
            8  => 'Inspección Asignada',
            9  => 'Validación Final de Expediente',
            10 => 'Cliente Activo / Cupo Aprobado'
        ];
        return $pasos[$paso] ?? 'Estatus Pendiente';
    }

    /**
    * Gestiona la progresión de pasos (Llamado por Admin)
    */
    public function avanzarPasoCliente($clienteId, $nuevoPaso)
    {
        // El estatus 1 se asigna manualmente o por lógica de aprobación, 
        // NO depende de la carga de vehículos/chóferes.
        return $this->clientRepo->updatePaso($clienteId, $nuevoPaso);
    }

    public function validarDocumento($documentoId, $status, $observaciones = null)
    {
        return $this->clientRepo->updateDocumentStatus($documentoId, [
            'estatus_archivo' => $status,
            'observaciones' => $observaciones,
            'validado_por' => auth()->id(),
            'fecha_validacion' => now()
        ]);
    }
}