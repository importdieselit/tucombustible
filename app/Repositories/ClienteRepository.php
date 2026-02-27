<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Cliente;

class ClienteRepository
{
    public function create(array $data)
    {
        return Cliente::create($data);
    }

    public function findByRif($rif)
    {
        return Cliente::where('rif', $rif)->first();
    }

    /**
     * Obtener prospectos (clientes en proceso de captación)
     */
    public function getProspectos($limit = 10)
    {
        return User::where('id_perfil', 3)
            ->whereHas('cliente', function($query) {
                $query->where('registro_paso', '<', 10);
            })
            ->with('cliente')
            ->orderBy('updated_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Contar cuántos clientes hay en cada paso (para estadísticas)
     */
    public function countByPaso($operador,$paso = null)
    {
        // Si solo pasas un número, asume que es una búsqueda exacta (ej: 4)
        if ($paso === null) {
            return Cliente::where('registro_paso', $operador)->count();
        }
    
        // Si pasas ('<', 10), usa el operador
        return Cliente::where('registro_paso', $operador, $paso)->count();
    }

    /**
     * Actualizar el paso de registro de un cliente
     */
    public function updatePaso($clienteId, $nuevoPaso)
    {
        $cliente = Cliente::find($clienteId);
        if ($cliente) {
            $cliente->registro_paso = $nuevoPaso;
            return $cliente->save();
        }
        return false;
    }

    /**
     * Obtener un cliente específico con su usuario y documentos
     */
    public function findWithDetails($id)
    {
        return User::with(['cliente', 'documentos'])->findOrFail($id);
    }

    /**
    * Sustituye la lógica de filtros del index viejo
    */
    public function getFiltrados($filtros)
    {
        $query = Cliente::query()->with('user');

        if (isset($filtros['search'])) {
            $query->where('razon_social', 'like', "%{$filtros['search']}%")
                  ->orWhere('rif', 'like', "%{$filtros['search']}%");
        }

        if (isset($filtros['paso'])) {
            $query->where('registro_paso', $filtros['paso']);
        } else {
            $query->where('registro_paso', '<', 10);
        }

        return $query->paginate(20);
    }

    /**
    * Actualiza el estatus de un documento específico
    */
    public function updateDocumentStatus($documentoId, array $data)
    {
        // Usamos el modelo CaptacionDocumento que es el que maneja los archivos
        $documento = \App\Models\CaptacionDocumento::find($documentoId);
        if ($documento) {
            return $documento->update($data);
        }
        return false;
    }
}