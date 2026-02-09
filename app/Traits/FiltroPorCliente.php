<?php

namespace App\Traits;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait FiltroPorCliente
{
    /**
     * Aplica el filtro de jerarquía de cliente al Query Builder.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorCliente(Builder $query): Builder
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // 1. Verificar si la columna existe antes de aplicar el filtro
        if (! $user || ! $query->getModel()->getConnection()->getSchemaBuilder()->hasColumn($query->getModel()->getTable(), 'id_cliente')) {
            return $query;
        }

        $clienteId = $user->cliente_id ?? 0; // Usamos 0 si no está definido (como invitado)

        // 2. SUPER USUARIO (cliente_id == 0)
        if ($clienteId === 0) {
            return $query; // No se aplica filtro
        }

        // Obtener la instancia del cliente para verificar la jerarquía
        $cliente = Cliente::find($clienteId);

        // 3. CLIENTE PRINCIPAL / PADRE
        if ($cliente && $cliente->parent === 0) {
            $subClientIds = Cliente::where('parent', $clienteId)->pluck('id'); 
            $allowedClientIds = $subClientIds->push($clienteId);
            
            return $query->whereIn('id_cliente', $allowedClientIds);
        }
        
        // 4. CLIENTE HIJO o CLIENTE REGULAR SIN JERARQUÍA
        return $query->where('id_cliente', $clienteId);
    }
}