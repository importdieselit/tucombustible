<?php

namespace App\Services;

use App\Repositories\TransaccionCombustibleRepository;
use App\Models\Viaje;
use Illuminate\Support\Facades\DB;

class LogisticaInventarioService
{
    protected $ledgerRepo;

    public function __construct(TransaccionCombustibleRepository $ledgerRepo)
    {
        $this->ledgerRepo = $ledgerRepo;
    }

    private function obtenerTipoCombustibleId(Viaje $viaje): int
    {
        return match ((int) $viaje->tipo_planificacion) {
            1       => 1,
            2       => 2, 
            default => (int) ($viaje->tipo ?? 2),
        };
    }

    // Para que guarde el ID de quien creó la Planificación y no cambie al del Ayudante o Chofer
    private function resolverUsuarioId(Viaje $viaje): int
    {
        return $viaje->usuario_id ?? auth()->id() ?? 1;
    }

    public function registrarCompromisoPlanificacion(Viaje $viaje): void
    {
        switch ((int) $viaje->tipo_planificacion) {
            case 1:
            case 2:
                $tipoMovimiento = 'compromiso_despacho';
                $litros = -abs($viaje->litros);
                $obs = "Combustible comprometido por Planificación de Despacho #{$viaje->id}.";
                break;

            default:
                return;
        }

        $tipoCombustibleId = $this->obtenerTipoCombustibleId($viaje);

        $this->ledgerRepo->registrar([
            'sede_id'             => $viaje->sede_id ?? 1,
            'tipo_combustible_id' => $tipoCombustibleId,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => $tipoMovimiento,
            'cantidad_litros'     => $litros,
            'cliente_id'          => $viaje->cliente_id,
            'user_id'             => $this->resolverUsuarioId($viaje),
            'viaje_id'            => $viaje->id,
            'observaciones'       => $obs
        ]);
    }

    public function revertirCompromisoPlanificacion(Viaje $viaje): void
    {
        switch ((int) $viaje->tipo_planificacion) {
            case 1:
            case 2:
                $tipoMovimiento = 'reverso';
                $litros = abs($viaje->litros);
                $obs = "Reverso de compromiso de combustible por edición/cancelación de Planificación #{$viaje->id}.";
                break;

            default:
                return;
            }

        $tipoCombustibleId = $this->obtenerTipoCombustibleId($viaje);

        $this->ledgerRepo->registrar([
            'sede_id'             => $viaje->sede_id ?? 1,
            'tipo_combustible_id' => $tipoCombustibleId,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => 'reverso',
            'cantidad_litros'     => $litros,
            'cliente_id'          => $viaje->cliente_id,
            'user_id'             => $this->resolverUsuarioId($viaje),
            'viaje_id'            => $viaje->id,
            'observaciones'       => $obs
        ]);
    }

    public function registrarSalidaFisicaDespacho(Viaje $viaje): void
    {
        if (!in_array((int) $viaje->tipo_planificacion, [1, 2])) {
            return;
        }

        $tipoCombustibleId = $this->obtenerTipoCombustibleId($viaje);
        $sedeId            = $viaje->sede_id ?? $viaje->sede_origen_id;
        $usuarioId         = $this->resolverUsuarioId($viaje);

        DB::transaction(function () use ($viaje, $tipoCombustibleId, $sedeId, $usuarioId) {

            // 1. Liberar el compromiso
            $this->ledgerRepo->registrar([
                'sede_id'             => $sedeId,
                'tipo_combustible_id' => $tipoCombustibleId,
                'bolsa_tipo'          => 'general',
                'tipo_movimiento'     => 'compromiso_despacho',
                'cantidad_litros'     => abs($viaje->litros),
                'cliente_id'          => $viaje->cliente_id,
                'user_id'             => $usuarioId,
                'viaje_id'            => $viaje->id,
                'observaciones'       => "Liberación de compromiso por despacho EN RUTA #{$viaje->id}"
            ]);

            // 2. Salida física (Resta directo del tanque)
            if ($viaje->detalles && $viaje->detalles->count() > 0) {
                foreach ($viaje->detalles as $detalle) {
                    $totalLitros = (float) ($detalle->litros ?? $detalle->cantidad_litros);

                    $this->ledgerRepo->registrar([
                        'sede_id'             => $sedeId,
                        'tipo_combustible_id' => $tipoCombustibleId,
                        'bolsa_tipo'          => 'general',
                        'tipo_movimiento'     => 'despacho',
                        'cantidad_litros'     => -abs($totalLitros),
                        'cliente_id'          => $detalle->cliente_id ?? $viaje->cliente_id,
                        'user_id'             => $usuarioId,
                        'viaje_id'            => $viaje->id,
                        'observaciones'       => "Salida física por Despacho #{$viaje->id}"
                    ]);

                    if ($detalle->deposito_origen_id) {
                        DB::table('depositos')
                            ->where('id', $detalle->deposito_origen_id)
                            ->decrement('nivel_actual_litros', abs($totalLitros));
                    }
                }
            } else {
                $totalLitros = (float) $viaje->litros;

                $this->ledgerRepo->registrar([
                    'sede_id'             => $sedeId,
                    'tipo_combustible_id' => $tipoCombustibleId,
                    'bolsa_tipo'          => 'general',
                    'tipo_movimiento'     => 'despacho',
                    'cantidad_litros'     => -abs($totalLitros),
                    'cliente_id'          => $viaje->cliente_id,
                    'user_id'             => $usuarioId,
                    'viaje_id'            => $viaje->id,
                    'observaciones'       => "Salida física por Despacho #{$viaje->id}"
                ]);
            }
        });
    }

    public function registrarEntradaCompra(Viaje $viaje, ?int $depositoDestinoId = null, ?float $litrosRecibidos = null): void
    {
        if ((int) $viaje->tipo_planificacion !== 4) {
            return;
        }

        $litros            = abs($litrosRecibidos ?? (float) $viaje->litros);
        $depositoId        = $depositoDestinoId ?? $viaje->deposito_destino_id ?? null;
        $sedeId            = $viaje->sede_destino_id ?? $viaje->sede_origen_id ?? $viaje->sede_id;
        $tipoCombustibleId = $this->obtenerTipoCombustibleId($viaje);
        $usuarioId         = $this->resolverUsuarioId($viaje);

        DB::transaction(function () use ($viaje, $sedeId, $depositoId, $tipoCombustibleId, $litros, $usuarioId) {
            $this->ledgerRepo->registrar([
                'sede_id'             => $sedeId,
                'deposito_id'         => $depositoId,
                'tipo_combustible_id' => $tipoCombustibleId,
                'bolsa_tipo'          => 'general',
                'tipo_movimiento'     => 'compra',
                'cantidad_litros'     => $litros,
                'cliente_id'          => $viaje->cliente_id,
                'user_id'             => $usuarioId,
                'viaje_id'            => $viaje->id,
                'observaciones'       => "Ingreso de inventario por Compra #{$viaje->id} (Completada)",
            ]);

            if ($depositoId) {
                DB::table('depositos')
                    ->where('id', $depositoId)
                    ->increment('nivel_actual_litros', $litros);
            }
        });
    }
}