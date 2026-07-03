<?php

namespace App\Repositories;

use App\Models\HistorialLlenadoCupoPrepagado;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HistorialLlenadoRepository
{
    public function registrar(array $data): HistorialLlenadoCupoPrepagado
    {
        return HistorialLlenadoCupoPrepagado::create([
            'cliente_id'          => $data['cliente_id'],
            'id_sede'             => $data['id_sede'],
            'id_deposito'         => $data['id_deposito'],
            'tipo_combustible_id' => $data['tipo_combustible_id'],
            'litros'              => $data['litros'],
        ]);
    }

    public function obtenerUltimos(int $porPagina = 20): LengthAwarePaginator
    {
        return HistorialLlenadoCupoPrepagado::with(['cliente', 'sede', 'deposito', 'tipoCombustible'])
            ->latest()
            ->paginate($porPagina);
    }

    public function obtenerPorCliente(int $clienteId): Collection
    {
        return HistorialLlenadoCupoPrepagado::with(['sede', 'deposito', 'tipoCombustible'])
            ->where('cliente_id', $clienteId)
            ->latest()
            ->get();
    }
}