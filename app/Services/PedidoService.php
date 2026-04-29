<?php

namespace App\Services;

use App\Repositories\PedidoRepository;
use App\Repositories\GascoCupoRepository;
use App\Repositories\DepositoRepository;
use App\Models\Cliente; 
use App\Models\GascoCupoMensual;
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

            // 1. FORZAR DIESEL: El ID 1 es Diesel en tu sistema
            $tipoCombustibleId = 1; 

            // 2. OBTENER CUPO MES ACTUAL (Usando tu repositorio existente)
            $cupoMensual = $this->gascoRepository->getOrCreateMonthlyQuota($clienteId);
            
            if (!$cupoMensual) {
                throw new Exception("El cliente no tiene un Cupo GASCO asignado para este mes.");
            }

            $cantidad = $data['cantidad_solicitada'];
            $disponibleReal = $cupoMensual->litros_autorizados - $cupoMensual->litros_consumidos;

            // 3. VALIDACIÓN DE DISPONIBLE
            if ($cantidad > $disponibleReal) {
                throw new Exception("Solicitud excede el disponible. Saldo actual: " . number_format($disponibleReal, 0) . " Ltrs.");
            }

            // 4. CREAR PEDIDO (Campos ajustados a tu ModeloPedido.txt)
            $pedido = $this->repository->create([
                'cliente_id'          => $clienteId,
                'user_id'             => $user->id,
                'tipo_combustible_id' => $tipoCombustibleId, 
                'cantidad_solicitada' => $cantidad,
                'estado'              => 'pendiente',
                'fecha_solicitud'     => now(),
                'direccion_despacho'  => $data['direccion_despacho'] ?? $cliente->direccion_operativa,
            ]);

            // 5. ACTUALIZAR CONSUMO Y DISPONIBLE
            $cupoMensual->increment('litros_consumidos', $cantidad);
            
            // Sincronizamos la columna 'disponible' en la tabla clientes para las vistas
            $cliente->decrement('disponible', $cantidad);

            return $pedido;
        });
    }

    // ELIMINADO: planificarYDespachar() -> Esta responsabilidad ahora es 100% del LogisticaService.

    public function cancelarPedido($pedidoId, $user)
    {
        return DB::transaction(function () use ($pedidoId) {
            $pedido = $this->repository->find($pedidoId);

            if (in_array($pedido->estado, ['despachado', 'cancelado', 'en_proceso'])) {
                throw new Exception("No se puede cancelar un pedido en estado {$pedido->estado}.");
            }

            // Devolver litros al cliente
            Cliente::where('id', $pedido->cliente_id)->increment('disponible', $pedido->cantidad_solicitada);

            // Devolver litros al cupo mensual
            $cupoGasco = GascoCupoMensual::where('cliente_id', $pedido->cliente_id)
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