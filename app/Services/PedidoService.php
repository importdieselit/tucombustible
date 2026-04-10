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
        $ids = [$cliente->id];

        // Si es padre, agregamos los IDs de todas sus sucursales
        if ($cliente->es_padre) {
            $ids = array_merge($ids, $cliente->sucursales()->pluck('id')->toArray());
        }

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
     * Registra solicitud con validación de inventario y reserva de cupos.
     */
    public function registrarSolicitud(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            // Usamos el cliente_id que viene del modal (ya validado en el Controller)
            $clienteId = $data['cliente_id']; 
            $cliente = Cliente::findOrFail($clienteId);
            
            // Alineamos con el nombre del input del modal
            $cantidad = (float)$data['cantidad_solicitada']; 

            // 1. VALIDACIÓN DE INVENTARIO FÍSICO
            $stockFisicoTotal = $this->depositoRepository->getNivelTotal(); 
            if ($cantidad > $stockFisicoTotal) {
                throw new Exception("No hay suficiente combustible físico. Stock: " . number_format($stockFisicoTotal, 0) . " L.");
            }

            // 2. VALIDACIÓN DE CUPO GASCO (Mensual)
            $cupoGasco = $this->gascoRepository->getOrCreateMonthlyQuota($cliente->id);
            if (!$cupoGasco) {
                throw new Exception("No hay cupo GASCO configurado para este mes.");
            }

            $disponibleGasco = (float)$cupoGasco->litros_autorizados - (float)$cupoGasco->litros_consumidos;
            if ($cantidad > $disponibleGasco) {
                throw new Exception("Cupo GASCO insuficiente. Disponible: " . number_format($disponibleGasco, 0) . " L.");
            }

            // 3. VALIDACIÓN DE DISPONIBLE EN TABLA CLIENTES
            if ($cantidad > $cliente->disponible) {
                throw new Exception("Saldo insuficiente en cuenta. Saldo: " . number_format($cliente->disponible, 0) . " L.");
            }

            // 4. CREACIÓN DEL PEDIDO (Incluyendo nuevos campos del modal)
            $pedido = $this->repository->create([
                'cliente_id'          => $clienteId,
                'user_id'             => $user->id, // Importante saber quién lo creó
                'cantidad_solicitada' => $cantidad,
                'fecha_entrega'       => $data['fecha_entrega'],
                'direccion_despacho'  => $data['direccion_despacho'],
                'observaciones'       => $data['observaciones'] ?? null,
                'estado'              => 'pendiente',
                'fecha_solicitud'     => now(),
            ]);

            // 5. ACTUALIZACIÓN DE SALDOS
            $this->gascoRepository->updateConsumed($cupoGasco->id, $cantidad);
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