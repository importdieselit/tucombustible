<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClienteCupo;
use App\Repositories\ClienteRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TankRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\PurchaseRepository;
use App\Services\GascoCupoService;

class DashboardService
{
    protected $vehicleRepo;
    protected $orderRepo;
    protected $userRepo;
    protected $tankRepo;
    protected $maintenanceRepo;
    protected $purchaseRepo;
    protected $clientRepo;
    protected $gascoCupoService;

    public function __construct(
        VehicleRepository     $vehicleRepo,
        OrderRepository       $orderRepo,
        UserRepository        $userRepo,
        TankRepository        $tankRepo,
        MaintenanceRepository $maintenanceRepo,
        PurchaseRepository    $purchaseRepo,
        ClienteRepository     $clientRepo,
        GascoCupoService      $gascoCupoService
    ) {
        $this->vehicleRepo     = $vehicleRepo;
        $this->orderRepo       = $orderRepo;
        $this->userRepo        = $userRepo;
        $this->tankRepo        = $tankRepo;
        $this->maintenanceRepo = $maintenanceRepo;
        $this->purchaseRepo    = $purchaseRepo;
        $this->clientRepo      = $clientRepo;
        $this->gascoCupoService = $gascoCupoService;
    }

    public function getDashboardData($user, $sucursalId = null): array
    {
        // -------------------------------------------------------
        // DASHBOARD DEL CLIENTE (perfil 3)
        // -------------------------------------------------------
        if ($user->id_perfil == 3) {
            // Cargamos el cliente original del usuario
            $cliente = Cliente::with([
                'registroPaso',
                'cupos.tipoCombustible',
                'placas',
                'choferes',
                'sucursales.registroPaso',
            ])->find($user->cliente_id);

            if (!$cliente) {
                return [
                    'perfil'  => 'cliente_sin_vincular',
                    'mensaje' => 'No se encontró un expediente asociado a su cuenta.',
                ];
            }

            // --- INICIO LÓGICA MODO ESPEJO ---
            // Si el cliente es padre y seleccionó una sucursal, cambiamos el objeto $cliente
            if ($cliente->es_padre && $sucursalId) {
                $hijo = $cliente->sucursales()->with([
                    'registroPaso',
                    'cupos.tipoCombustible',
                    'placas',
                    'choferes',
                ])->find($sucursalId);

                if ($hijo) {
                    $cliente = $hijo; // Ahora todo el código de abajo usará los datos del hijo
                }
            }
            // --- FIN LÓGICA MODO ESPEJO ---

            // Cliente en proceso de registro
            if ($cliente->status === Cliente::STATUS_EN_REGISTRO) {
                return [
                    'perfil'      => 'cliente_en_registro',
                    'cliente'     => $cliente,
                    'paso_actual' => $cliente->registro_paso,
                    'nombre_paso' => $cliente->nombre_paso_actual,
                    'porcentaje'  => $cliente->porcentaje_registro,
                    'pasos'       => $this->clientRepo->getPasos(),
                ];
            }

            // El resto del código (Rechazado, Inactivo, Aprobado) 
            // funcionará igual pero usando el $cliente (Padre o Hijo) según corresponda.

            if ($cliente->status === Cliente::STATUS_RECHAZADO) {
                return ['perfil' => 'cliente_rechazado', 'cliente' => $cliente];
            }

            if ($cliente->status === Cliente::STATUS_INACTIVO) {
                return ['perfil' => 'cliente_inactivo', 'cliente' => $cliente];
            }

            // Cliente aprobado
            // NUEVO: Consultamos el saldo GASCO usando el servicio
            $saldoGasco = $this->gascoCupoService->obtenerSaldoActual($cliente->id);

            return [
                'perfil'     => $cliente->es_padre ? 'cliente_padre' : 'cliente_sucursal',
                'cliente'    => $cliente,
                'es_padre'   => $cliente->es_padre,
                'cupos'      => $cliente->cupos,
                // Agregamos la data de GASCO para la vista del cliente:
                'cupo_gasco' => $saldoGasco['autorizados'],
                'saldo_gasco'=> $saldoGasco['disponible'],
                'placas'     => $cliente->placas()->activas()->get(),
                'choferes'   => $cliente->choferes()->activos()->get(),
                'sucursales' => $cliente->es_padre
                    ? $cliente->sucursales()->with('registroPaso')->get()
                    : collect(),
            ];
        }

        // -------------------------------------------------------
        // DASHBOARD DEL ADMINISTRADOR (perfiles 1 y 2)
        // -------------------------------------------------------
        return [
            'perfil' => 'admin_sistema',
            'stats'  => [
                'totalVehiculos'       => $this->vehicleRepo->countAll(),
                'totalUsuarios'        => $this->userRepo->countAll(),
                'totalOrdenesAbiertas' => $this->orderRepo->countAbiertas(),
                'totalTanques'         => $this->tankRepo->countAll(),
                'programadosHoy'       => $this->maintenanceRepo->countHoy(),
                'clientes_aprobados'   => Cliente::aprobados()->count(),
                'clientes_en_registro' => Cliente::enRegistro()->count(),
                'clientes_rechazados'  => Cliente::rechazados()->count(),
            ],
            'ultimasOrdenes' => $this->orderRepo->getUltimas(5),
        ];
    }

    public function getNombrePaso(int $pasoId): string
    {
        $pasos = $this->clientRepo->getPasos();
        return $pasos->firstWhere('id', $pasoId)?->nombre ?? 'Estatus Pendiente';
    }

    /**
     * Obtiene las estadísticas para el Dashboard Administrativo
     */
    public function getAdminStats(): array
    {
        return [
            'totalVehiculos'       => $this->vehicleRepo->countAll(),
            'totalUsuarios'        => $this->userRepo->countAll(),
            'totalOrdenesAbiertas' => $this->orderRepo->countAbiertas(),
            'totalTanques'         => $this->tankRepo->countAll(),
            'programadosHoy'       => $this->maintenanceRepo->countHoy(),
            // Estos nombres deben coincidir con lo que usa tu Blade
            'clientes_activos'     => Cliente::aprobados()->count(), 
            'clientes_en_registro' => Cliente::enRegistro()->count(),
            'clientes_rechazados'  => Cliente::rechazados()->count(),
        ];
    }
    
}