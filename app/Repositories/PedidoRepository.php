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
    public function getAllPedidosAdmin(array $filters = [], $limit = 20)
    {
        return Pedido::with(['cliente', 'usuario'])
            ->when(isset($filters['estado']), function ($query) use ($filters) {
                return $query->where('estado', $filters['estado']);
            })
            ->when(isset($filters['cliente_id']), function ($query) use ($filters) {
                return $query->where('cliente_id', $filters['cliente_id']);
            })
            ->when(isset($filters['fecha']), function ($query) use ($filters) {
                return $query->whereDate('fecha_entrega', $filters['fecha']);
            })
            ->orderBy('fecha_solicitud', 'desc')
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
            // Ajustamos los nombres de los campos para que coincidan con el Service y el Modal
            $pedido = Pedido::create([
                'cliente_id'          => $data['cliente_id'],
                'user_id'             => $data['user_id'] ?? auth()->id(), // Agregamos quién solicita
                'cantidad_solicitada' => $data['cantidad_solicitada'], // Antes decía 'cantidad'
                'fecha_entrega'       => $data['fecha_entrega'] ?? null,  // Campo nuevo del modal
                'direccion_despacho'  => $data['direccion_despacho'] ?? null, // Campo nuevo del modal
                'observaciones'       => $data['observaciones'] ?? null,
                'estado'              => 'pendiente',
                'fecha_solicitud'     => now(),
            ]);

            // 2. Generar Alerta para el Administrador
            try {
                $cliente = Cliente::find($data['cliente_id']);
                DB::table('alertas')->insert([
                    'id_usuario'  => 1, // Puedes luego iterar sobre los roles 1, 2, 6, 11, 12 y 18
                    'id_rel'      => $pedido->id,
                    'fecha'       => now(),
                    'observacion' => "Nuevo pedido #{$pedido->id} de {$cliente->nombre} por " . number_format($data['cantidad_solicitada'], 0) . "L.",
                    'estatus'     => 0,
                    'accion'      => route('logistica.planificacion'), // Apunta al nuevo módulo
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