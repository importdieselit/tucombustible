<?php

namespace App\Services;

use App\Repositories\PedidoRepository;
use App\Models\Cliente;
use App\Models\ClienteCupo;
use App\Models\Pedido; 
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PedidoService
{
    protected $repository;

    public function __construct(PedidoRepository $repository)
    {
        $this->repository = $repository;
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
     * Usa 'litros_aprobados' de la tabla cliente_cupos.
     */
    public function registrarSolicitud(array $data, $user)
    {
        $data['cliente_id'] = $user->cliente_id;
        $cliente = Cliente::findOrFail($data['cliente_id']);

        // 1. Obtener el cupo configurado desde cliente_cupos
        $cupoConfig = ClienteCupo::where('cliente_id', $cliente->id)
            ->where('tipo_combustible_id', $data['tipo_combustible_id'])
            ->first();

        if (!$cupoConfig) {
            throw new Exception("No tiene un cupo configurado para este tipo de combustible.");
        }

        // Columna real según DDL: litros_aprobados
        $cupoMensualTotal = (float)$cupoConfig->litros_aprobados;

        // 2. Sumar consumo del mes calendario actual
        // Según DDL: la tabla pedidos usa 'deposito_id'
        $consumoMesActual = Pedido::where('cliente_id', $cliente->id)
            ->where('deposito_id', $data['tipo_combustible_id']) 
            ->whereIn('estado', ['pendiente', 'aprobado', 'en_proceso', 'completado'])
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('cantidad_solicitada');

        // 3. Cálculo del disponible: Cupo Base - Consumido Mes
        $disponibleReal = $cupoMensualTotal - (float)$consumoMesActual;

        // Registro en log para auditoría técnica
        Log::info("Validación Cupo - Cliente: {$cliente->nombre} | Cupo Base: {$cupoMensualTotal} | Consumido: {$consumoMesActual} | Disponible: {$disponibleReal}");

        // 4. Validación de la cantidad solicitada
        if ($data['cantidad'] > $disponibleReal) {
            throw new Exception("Cupo mensual insuficiente. Le quedan " . number_format($disponibleReal, 2) . " L disponibles para este mes.");
        }

        return $this->repository->create($data);
    }
}