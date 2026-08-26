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
            'chofer_cliente_id'   => $data['chofer_cliente_id'],
            'placa_vehiculo_id'   => $data['placa_vehiculo_id'],
            'tipo_combustible_id' => $data['tipo_combustible_id'],
            'litros'              => $data['litros'],
            'observaciones'       => $data['observaciones'] ?? null,
        ]);
    }

    public function obtenerUltimos(int $porPagina = 20): LengthAwarePaginator
    {
        return HistorialLlenadoCupoPrepagado::with(['cliente', 'sede', 'deposito', 'tipoCombustible', 'chofer', 'placa'])
            ->latest()
            ->paginate($porPagina);
    }

    public function obtenerPorCliente(string $busqueda): Collection
    {
        return HistorialLlenadoCupoPrepagado::with(['cliente', 'sede', 'deposito', 'tipoCombustible', 'chofer', 'placa'])
            ->whereHas('cliente', function ($query) use ($busqueda) {
                $query->where('nombre', 'LIKE', '%' . $busqueda . '%')
                      ->orWhere('rif', 'LIKE', '%' . $busqueda . '%');
            })
            ->latest()
            ->get();
    }

    public function obtenerPorRangoFechas(string $desde, string $hasta): Collection
    {
        $fechaInicio = $desde . ' 00:00:00';
        $fechaFin    = $hasta . ' 23:59:59';

        return HistorialLlenadoCupoPrepagado::with(['cliente', 'sede', 'deposito', 'tipoCombustible', 'chofer', 'placa'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->latest()
            ->get();
    }
}