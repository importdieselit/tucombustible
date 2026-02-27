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
        if ($user->id_perfil == 3) {
            $cliente = $user->cliente;
            $pasoActual = $cliente->registro_paso;

            // Si el paso es 10, es un cliente ya aprobado
            if ($pasoActual >= 10) {
                return [
                    'perfil' => 'cliente',
                    'cliente' => $cliente,
                    'stats' => [
                        // Aquí podrías meter lógica de sus consumos reales
                        'consumo_mes' => 0, 
                        'pedidos_activos' => 0,
                    ]
                ];
            }

            // Si es menor a 10, sigue en proceso de captación
            return [
                'perfil' => 'cliente_proceso',
                'paso_actual' => $pasoActual,
                'nombre_paso' => $this->getNombrePaso($pasoActual),
                'porcentaje' => ($pasoActual / 10) * 100
            ];
        }

        // Para perfiles 1 y 2 (Superadmin y Gerencia)
        return [
            'perfil' => 'admin',
            'stats' => [
                'totalVehiculos' => $this->vehicleRepo->countAll(),
                'totalUsuarios' => $this->userRepo->countAll(),
                'totalOrdenesAbiertas' => $this->orderRepo->countAbiertas(),
                'totalTanques' => $this->tankRepo->countAll(),
                'unidades_disponibles' => $this->vehicleRepo->countDisponibles(),
                'unidades_en_mantenimiento' => $this->vehicleRepo->countEnMantenimiento(),
                'unidades_con_orden_abierta' => $this->vehicleRepo->countConOrdenAbierta(),
                'programados' => $this->maintenanceRepo->countProximos(),
                'programadosHoy' => $this->maintenanceRepo->countHoy(),
                'suministros_compra' => $this->purchaseRepo->countPendientes(),
            ],
            'ultimasOrdenes' => $this->orderRepo->getUltimas(5)
        ];
    }

    public function getNombrePaso($paso)
    {
        $pasos = [
            1  => 'Registro Inicial',
            2  => 'Envio de Planillas por Correo Electronico',
            3  => 'Recepcion de Planillas y Demas Documentos de Cliente',
            4  => 'Documentos en Revision',
            5  => 'Carpeta de Documentos Realizada',
            6  => 'Carpeta Enviada a MINPET',
            7  => 'Esperando Respuesta de MINPET',
            8  => 'Fecha de Inspeccion Asignada',
            9  => 'Esperando Respuesta de MINPET',
            10 => 'Cupo Aprobado'
        ];
        return $pasos[$paso] ?? 'Estatus Pendiente';
    }

    public function getCaptacionStats()
    {
        return [
            'total_prospectos' => $this->clientRepo->countByPaso('<', 10),
            'en_revision'      => $this->clientRepo->countByPaso(4),
            'esperando_minpet' => $this->clientRepo->countByPaso(7),
        ];
    }

    /**
    * Gestiona la progresión de pasos del cliente
    */
    public function avanzarPasoCliente($clienteId, $nuevoPaso)
    {
        // Aquí podrías añadir validaciones adicionales antes de actualizar
        // Ej: if ($nuevoPaso == 4 && !$this->documentosValidados($clienteId)) ...

        $actualizado = $this->clientRepo->updatePaso($clienteId, $nuevoPaso);

        if ($actualizado) {
            // Si el paso llega a 10, activamos al cliente automáticamente
            if ($nuevoPaso == 10) {
                $this->activarClienteFinal($clienteId);
            }
            return true;
        }

        return false;
    }

    protected function activarClienteFinal($clienteId)
    {
        // Lógica para setear status = 1 cuando llega al paso 10
        $cliente = \App\Models\Cliente::find($clienteId);
        $cliente->update(['status' => 1]);
    }

    /**
    * Valida o rechaza un documento individual
    */
    public function validarDocumento($documentoId, $status, $observaciones = null)
    {
        // Usamos el repositorio para la persistencia
        return $this->clientRepo->updateDocumentStatus($documentoId, [
            'estatus_archivo' => $status, // 'validado' o 'rechazado'
            'observaciones' => $observaciones,
            'validado_por' => auth()->id(),
            'fecha_validacion' => now()
        ]);
    }
}