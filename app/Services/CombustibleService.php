<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Repositories\GascoCupoRepository;
use Exception;

class CombustibleService
{
    protected $clienteRepo;
    protected $gascoRepo;

    public function __construct(
        ClienteRepository $clienteRepo,
        GascoCupoRepository $gascoRepo
    ) {
        $this->clienteRepo = $clienteRepo;
        $this->gascoRepo = $gascoRepo;
    }

    /**
     * Carga de cupo GASCO (Desde la vista de Admin)
     */
    public function registrarCupoMensualGasco(array $data)
    {
        $cliente = $this->clienteRepo->find($data['cliente_id']);

        if (!$cliente) {
            throw new Exception("Cliente no encontrado.");
        }

        // VALIDACIÓN CRÍTICA: No puede superar el cupo aprobado general de la tabla clientes
        if ($data['litros_autorizados'] > $cliente->cupo) {
            throw new Exception("El cupo GASCO solicitado (" . $data['litros_autorizados'] . "L) supera el cupo aprobado del cliente (" . $cliente->cupo . "L).");
        }

        // LLAMADA CORRECTA: Usando el repositorio inyectado
        return $this->gascoRepo->updateOrCreateQuota($data);
    }
}