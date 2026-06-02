<?php

namespace App\Services;

use App\Repositories\DepositoRepository;
use App\Models\Deposito;

class DepositoService
{
    protected $depositoRepo;

    public function __construct(DepositoRepository $depositoRepo)
    {
        $this->depositoRepo = $depositoRepo;
    }

    /**
     * Aplica reglas de negocio iniciales y ordena la persistencia.
     */
    public function registrarDeposito(array $data): Deposito
    {
        // Al registrar un tanque nuevo, su capacidad inicial en litros 
        // se iguala a la capacidad máxima física calculada/nominal.
        $data['capacidad_litros'] = $data['capacidad_maxima'];

        // Regla 2: El nivel de alerta de Stock Bajo SIEMPRE es el 20% de la capacidad máxima
        $data['nivel_alerta_litros'] = (float) $data['capacidad_maxima'] * 0.20;
        
        // Mapeo preventivo por si usas el string legacy 'producto' en tus reportes
        if (isset($data['producto_nombre_legacy'])) {
            $data['producto'] = $data['producto_nombre_legacy'];
        }

        return $this->depositoRepo->create($data);
    }
}