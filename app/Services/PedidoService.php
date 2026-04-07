<?php

namespace App\Services;

use App\Repositories\PedidoRepository;
use App\Repositories\GascoCupoRepository;
use App\Models\Cliente; 
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PedidoService
{
    protected $repository;
    protected $gascoRepository;

    public function __construct(
        PedidoRepository $repository,
        GascoCupoRepository $gascoRepository
    ) {
        $this->repository = $repository;
        $this->gascoRepository = $gascoRepository;
    }

    /**
     * Lista pedidos según jerarquía (Padre/Sucursal)
     */
    public function listarPedidosParaUsuario($cliente)
    {
        // Creamos un array con el ID del cliente actual
        $ids = [$cliente->id];

        // LLAMADA AL REPOSITORIO USANDO EL ARRAY DE IDS
        return $this->repository->getPedidosPorClientes($ids);
    }

    /**
     * Obtiene todos los pedidos para el Admin
     */
    public function listarPedidosParaAdmin()
    {
        return $this->repository->getAllPedidosAdmin();
    }

    /**
     * Actualiza el estado del pedido
     */
    public function actualizarEstadoPedido($pedidoId, $nuevoEstado)
    {
        return $this->repository->update($pedidoId, ['estado' => $nuevoEstado]);
    }

    /**
     * Registra solicitud con validación de cupo mensual dinámica.
     */
    public function registrarSolicitud(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            $data['cliente_id'] = $user->cliente_id;
            $cliente = Cliente::findOrFail($data['cliente_id']);
            $cantidad = (float)$data['cantidad'];

            // 1. Obtenemos el cupo (ya sea el existente o uno nuevo heredado del mes pasado)
            $cupoGasco = $this->gascoRepository->getOrCreateMonthlyQuota($cliente->id);

            if (!$cupoGasco) {
                throw new Exception("Operación rechazada: No se ha configurado un cupo GASCO inicial para este cliente.");
            }

            // 2. El disponible ahora siempre será correcto. 
            // Si es un registro nuevo creado por herencia, consumidos será 0.
            $disponibleReal = (float)$cupoGasco->litros_autorizados - (float)$cupoGasco->litros_consumidos;

            if ($cantidad > $disponibleReal) {
                throw new Exception("Cupo mensual insuficiente en GASCO. Le quedan " . number_format($disponibleReal, 2) . " L disponibles para este mes.");
            }

            // 3. Crear el pedido
            $pedido = $this->repository->create($data);

            // 4. Actualizar consumos
            $this->gascoRepository->updateConsumed($cupoGasco->id, $cantidad);
            $cliente->decrement('disponible', $cantidad);

            return $pedido;
        });
    }
}