<?php

namespace App\Services;

use App\Repositories\PedidoRepository;
use App\Repositories\GascoCupoRepository;
use App\Repositories\DepositoRepository;
use App\Models\Cliente; 
use Exception;
use Illuminate\Support\Facades\DB;

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

    public function listarPedidosParaUsuario($cliente)
    {
        $ids = [$cliente->id];
        if ($cliente->es_padre) {
            $ids = array_merge($ids, $cliente->sucursales()->pluck('id')->toArray());
        }
        return $this->repository->getPedidosPorClientes($ids);
    }

    public function listarPedidosParaAdmin()
    {
        return $this->repository->getAllPedidosAdmin();
    }

    public function actualizarEstadoPedido($pedidoId, $nuevoEstado)
    {
        return $this->repository->update($pedidoId, ['estado' => $nuevoEstado]);
    }

    public function registrarSolicitud(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            $clienteId = $data['cliente_id']; 
            $cliente = Cliente::findOrFail($clienteId);
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

            // 4. CREACIÓN DEL PEDIDO
            $pedido = $this->repository->create([
                'cliente_id'          => $clienteId,
                'user_id'             => $user->id,
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

    // ELIMINADO: planificarYDespachar() -> Esta responsabilidad ahora es 100% del LogisticaService.

    public function cancelarPedido($pedidoId, $user)
    {
        return DB::transaction(function () use ($pedidoId) {
            $pedido = $this->repository->find($pedidoId);

            // Se agrega 'en_proceso' porque si Logística ya armó el viaje, el cliente no puede cancelarlo
            if (in_array($pedido->estado, ['despachado', 'cancelado', 'en_proceso'])) {
                throw new Exception("No se puede cancelar un pedido en estado {$pedido->estado}. Comuníquese con administración.");
            }

            Cliente::where('id', $pedido->cliente_id)->increment('disponible', $pedido->cantidad_solicitada);

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

    public function calificarPedido($pedidoId, array $data, $user)
    {
        return $this->repository->update($pedidoId, [
            'calificacion' => $data['calificacion'],
            'comentario_calificacion' => $data['comentario_calificacion'] ?? null
        ]);
    }
}