<?php

namespace App\Repositories;

use App\Models\Pedido;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoRepository
{
    /**
     * Obtiene pedidos de un cliente o grupo de clientes
     */
    public function getPedidosPorClientes(array $clienteIds, $limit = 15)
    {
        // Importante: Asegúrate de que 'cliente_id' sea el nombre de la columna en tu tabla pedidos
        return Pedido::whereIn('cliente_id', $clienteIds)
            ->orderBy('fecha_solicitud', 'desc')
            ->get(); // Cámbialo a get() para probar; si funciona, luego vuelves a paginate()
    }

    /**
     * Obtiene todos los pedidos para el Admin
     */
    public function getAllPedidosAdmin($limit = 20)
    {
        return Pedido::with(['cliente'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Crea el registro del pedido y la alerta.
     * No descuenta de la tabla 'clientes' para permitir el reinicio mensual dinámico.
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            
            // 1. Crear el registro en la tabla pedidos
            $pedido = Pedido::create([
                'cliente_id'          => $data['cliente_id'],
                'deposito_id'         => $data['tipo_combustible_id'],
                'cantidad_solicitada' => $data['cantidad'],
                'estado'              => 'pendiente',
                'fecha_solicitud'     => now(),
            ]);

            // 2. Generar Alerta para el Administrador
            try {
                $cliente = Cliente::find($data['cliente_id']);
                DB::table('alertas')->insert([
                    'id_usuario'  => 1, // Admin principal
                    'id_rel'      => $pedido->id,
                    'fecha'       => now(),
                    'observacion' => "Nuevo pedido #{$pedido->id} de {$cliente->nombre} por " . number_format($data['cantidad'], 2) . "L.",
                    'estatus'     => 0,
                    'accion'      => "/admin/pedidos/{$pedido->id}",
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            } catch (\Exception $e) {
                Log::error("Error al crear alerta para el pedido {$pedido->id}: " . $e->getMessage());
            }

            return $pedido;
        });
    }

    public function find($id)
    {
        return Pedido::with(['cliente'])->findOrFail($id);
    }

    public function update($id, array $data)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update($data);
        return $pedido;
    }
}