<?php

namespace App\Repositories;

use App\Models\TransaccionCombustible;

class TransaccionCombustibleRepository
{
    public function registrar(array $data): TransaccionCombustible
    {
        return TransaccionCombustible::create($data);
    }

    public function getDisponibilidadPrepagada(?int $sedeId = null, ?int $tipoCombustibleId = null): float
    {
        $query = TransaccionCombustible::where('bolsa_tipo', 'prepagado');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        if ($tipoCombustibleId) {
            $query->where('tipo_combustible_id', $tipoCombustibleId);
        }

        return (float) $query->sum('cantidad_litros');
    }

    public function getSaldoFisicoGeneral(?int $sedeId = null, ?int $tipoCombustibleId = null): float
    {
        $query = TransaccionCombustible::where('bolsa_tipo', 'general')
            ->where('tipo_movimiento', '!=', 'compromiso_despacho');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        if ($tipoCombustibleId) {
            $query->where('tipo_combustible_id', $tipoCombustibleId);
        }

        return (float) $query->sum('cantidad_litros');
    }

    public function getLitrosComprometidos(?int $sedeId = null, ?int $tipoCombustibleId = null): float
    {
        $query = TransaccionCombustible::where('bolsa_tipo', 'general')
            ->where('tipo_movimiento', 'compromiso_despacho');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        if ($tipoCombustibleId) {
            $query->where('tipo_combustible_id', $tipoCombustibleId);
        }

        return abs((float) $query->sum('cantidad_litros'));
    }

    public function getDisponibilidadFisicaTotal(?int $sedeId = null, ?int $tipoCombustibleId = null): float
    {
        $query = TransaccionCombustible::where('tipo_movimiento', '!=', 'compromiso_despacho');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        if ($tipoCombustibleId) {
            $query->where('tipo_combustible_id', $tipoCombustibleId);
        }

        return (float) $query->sum('cantidad_litros');
    }

    public function getSaldoTeoricoPorDeposito(int $depositoId): float
    {
        return (float) TransaccionCombustible::where('deposito_id', $depositoId)
            ->where('tipo_movimiento', '!=', 'compromiso_despacho')
            ->sum('cantidad_litros');
    }

    public function getHistorialMermas(array $filtros = [], int $perPage = 15)
    {
        $query = TransaccionCombustible::with(['sede', 'deposito', 'user', 'tipoCombustible'])
            ->whereIn('tipo_movimiento', ['merma', 'ajuste_negativo', 'ajuste_positivo']);

        if (!empty($filtros['sede_id'])) {
            $query->where('sede_id', $filtros['sede_id']);
        }

        if (!empty($filtros['tipo_combustible_id'])) {
            $query->where('tipo_combustible_id', $filtros['tipo_combustible_id']);
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $query->whereBetween('created_at', [
                $filtros['fecha_inicio'] . ' 00:00:00',
                $filtros['fecha_fin'] . ' 23:59:59'
            ]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getTotalLitrosMermas(array $filtros = []): float
    {
        $query = TransaccionCombustible::whereIn('tipo_movimiento', ['merma', 'ajuste_negativo', 'ajuste_positivo']);

        if (!empty($filtros['sede_id'])) {
            $query->where('sede_id', $filtros['sede_id']);
        }

        if (!empty($filtros['tipo_combustible_id'])) {
            $query->where('tipo_combustible_id', $filtros['tipo_combustible_id']);
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $query->whereBetween('created_at', [
                $filtros['fecha_inicio'] . ' 00:00:00',
                $filtros['fecha_fin'] . ' 23:59:59'
            ]);
        }

        return (float) $query->sum('cantidad_litros');
    }
}