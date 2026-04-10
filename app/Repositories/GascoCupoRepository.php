<?php

namespace App\Repositories;

use App\Models\GascoCupoMensual;
use Carbon\Carbon;

class GascoCupoRepository
{
    /**
     * Obtiene el cupo del mes actual. 
     * Si no existe, lo crea basado en el último registro disponible.
     */
    public function getOrCreateMonthlyQuota(int $clienteId)
    {
        $ahora = Carbon::now();

        // 1. Intentar buscar el del mes actual
        $cupoActual = GascoCupoMensual::where('cliente_id', $clienteId)
            ->where('mes', $ahora->month)
            ->where('anio', $ahora->year)
            ->first();

        if ($cupoActual) {
            return $cupoActual;
        }

        // 2. Si no existe, buscamos el último histórico
        $ultimoCupo = GascoCupoMensual::where('cliente_id', $clienteId)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        if (!$ultimoCupo) {
            return null; // El cliente realmente no tiene nada cargado nunca
        }

        // 3. Creamos el registro del nuevo mes heredando los litros_autorizados
        $nuevoCupo = GascoCupoMensual::create([
            'cliente_id'         => $clienteId,
            'mes'                => $ahora->month,
            'anio'               => $ahora->year,
            'litros_autorizados' => $ultimoCupo->litros_autorizados,
            'litros_consumidos'  => 0 // Reinicio automático de mes
        ]);

        // NUEVO: Como es un mes nuevo (consumo = 0), el disponible de la tabla cliente 
        // vuelve a estar completo igual al autorizado heredado.
        \App\Models\Cliente::where('id', $clienteId)->update([
            'disponible' => $nuevoCupo->litros_autorizados
        ]);

        return $nuevoCupo;
    }

    /**
     * Crea o actualiza el cupo operativo enviado por GASCO (Uso exclusivo del Admin).
     * Si ya existe un registro en el mes, actualiza el autorizado sin tocar el consumo.
     */
    public function updateOrCreateQuota(array $data)
    {
        return GascoCupoMensual::updateOrCreate(
            [
                'cliente_id' => $data['cliente_id'],
                'mes'        => $data['mes'] ?? Carbon::now()->month,
                'anio'       => $data['anio'] ?? Carbon::now()->year,
            ],
            [
                'litros_autorizados' => $data['litros_autorizados']
            ]
        );
    }

    /**
     * Creación básica (si la necesitas para algún seed o proceso manual interno)
     */
    public function create(array $data)
    {
        return GascoCupoMensual::create($data);
    }

    /**
     * Incrementa el consumo cada vez que se aprueba un pedido.
     */
    public function updateConsumed(int $id, float $cantidad)
    {
        $cupo = GascoCupoMensual::findOrFail($id);
        $cupo->increment('litros_consumidos', $cantidad);
        return $cupo;
    }
}