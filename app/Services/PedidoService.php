<?php

namespace App\Services;

use App\Repositories\PedidoRepository;
use App\Repositories\GascoCupoRepository;
use App\Repositories\DepositoRepository;
use App\Models\Cliente; 
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PedidoService
{
    protected $repository;
    protected $gascoRepository;
    protected $depositoRepository;

    public function __construct(
        PedidoRepository $repository,
        GascoCupoRepository $gascoRepository,
        DepositoRepository $depositoRepository
    ) {
        $this->repository = $repository;
        $this->gascoRepository = $gascoRepository;
        $this->depositoRepository = $depositoRepository;
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
            // Si el Admin crea el pedido, el cliente_id viene en $data. 
            // Si es el Portal, lo tomamos del usuario logueado.
            $clienteId = $data['cliente_id'] ?? $user->cliente_id;
            
            $cliente = Cliente::findOrFail($clienteId);
            $cantidad = (float)$data['cantidad'];

            $cupoGasco = $this->gascoRepository->getOrCreateMonthlyQuota($cliente->id);

            if (!$cupoGasco) {
                throw new Exception("Operación rechazada: No se ha configurado un cupo GASCO inicial.");
            }

            $disponibleReal = (float)$cupoGasco->litros_autorizados - (float)$cupoGasco->litros_consumidos;

            if ($cantidad > $disponibleReal) {
                throw new Exception("Cupo insuficiente en GASCO. Disponible: " . number_format($disponibleReal, 2) . " L.");
            }

            // Crear el pedido (asegúrate que tu repo soporte los campos adicionales)
            $pedido = $this->repository->create([
                'cliente_id' => $clienteId,
                'cantidad_solicitada' => $cantidad,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => 'pendiente'
            ]);

            $this->gascoRepository->updateConsumed($cupoGasco->id, $cantidad);
            
            // Usamos la columna 'disponible' de la tabla clientes según tu DDL
            $cliente->decrement('disponible', $cantidad);

            return $pedido;
        });
    }

    /**
     * Planificar y Despachar (Afecta el inventario físico del tanque)
     */
    public function planificarYDespachar($pedidoId, array $data)
    {
        return DB::transaction(function () use ($pedidoId, $data) {
            $pedido = $this->repository->find($pedidoId);

            // 1. Descontar del inventario físico (Columna nivel_actual_litros)
            $this->depositoRepository->restarDisponibilidad($data['deposito_id'], $pedido->cantidad_solicitada);

            // 2. Actualizar el pedido
            return $this->repository->update($pedidoId, [
                'estado'         => 'despachado', 
                'deposito_id'    => $data['deposito_id'],
                'vehiculo_id'    => $data['vehiculo_id'] ?? null,
                'fecha_despacho' => $data['fecha_despacho'],
            ]);
        });
    }

    /**
     * Cancela un pedido y REINTEGRA los cupos al cliente
     */
    public function cancelarPedido($pedidoId, $user)
    {
        return DB::transaction(function () use ($pedidoId) {
            $pedido = $this->repository->find($pedidoId);

            if (in_array($pedido->estado, ['despachado', 'cancelado'])) {
                throw new Exception("No se puede cancelar un pedido en estado {$pedido->estado}.");
            }

            // 1. Reintegro a la tabla 'clientes' (columna disponible)
            Cliente::where('id', $pedido->cliente_id)->increment('disponible', $pedido->cantidad_solicitada);

            // 2. Reintegro a GASCO (buscando el registro del mes del pedido)
            $cupoGasco = \App\Models\GascoCupoMensual::where('cliente_id', $pedido->cliente_id)
                ->where('mes', $pedido->created_at->month)
                ->where('anio', $pedido->created_at->year)
                ->first();

            if ($cupoGasco) {
                $cupoGasco->decrement('litros_consumidos', $pedido->cantidad_solicitada);
            }

            return $this->repository->update($pedidoId, ['estado' => 'cancelado']);
        });
    }

    /**
     * Guarda la calificación del cliente
     */
    public function calificarPedido($pedidoId, array $data, $user)
    {
        return $this->repository->update($pedidoId, [
            'calificacion' => $data['calificacion'],
            'comentario_calificacion' => $data['comentario_calificacion'] ?? null
        ]);
    }
}