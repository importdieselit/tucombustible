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

    /**
     * Compromete combustible únicamente para Planificaciones de Despacho (Tipos 1 y 2) en estado PROGRAMADO.
     * Las Compras (Tipo 4) y Fletes (Tipo 3) no afectan el Ledger en esta fase.
     */
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
                // Fletes (3) y Compras programadas (4) no registran movimiento previo en Ledger
                return;
        }

        $tipoCombustibleId = $viaje->tipo_combustible_id ?? $viaje->tipo;

        $this->ledgerRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $tipoCombustibleId,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => $tipoMovimiento,
            'tipo_transaccion'    => strtoupper($tipoMovimiento),
            'cantidad_litros'     => $litros,
            'cliente_id'          => $viaje->cliente_id,
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'referencia_id'       => $viaje->id,
            'referencia_type'     => Viaje::class,
            'observaciones'       => $obs,
            'observacion'         => $obs
        ]);
    }

    /**
     * Revierte el compromiso de despacho usando el tipo 'reverso'
     */
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

        $tipoCombustibleId = $viaje->tipo_combustible_id ?? $viaje->tipo;

        $this->ledgerRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $tipoCombustibleId,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => $tipoMovimiento,
            'tipo_transaccion'    => 'REVERSO',
            'cantidad_litros'     => $litros,
            'cliente_id'          => $viaje->cliente_id,
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'referencia_id'       => $viaje->id,
            'referencia_type'     => Viaje::class,
            'observaciones'       => $obs,
            'observacion'         => $obs
        ]);
    }

    /**
     * Se ejecuta cuando un Despacho (Tipo 1 o 2) pasa a "EN RUTA"
     */
    public function registrarSalidaFisicaDespacho(Viaje $viaje): void
    {
        DB::transaction(function () use ($viaje) {
            foreach ($viaje->detalles as $detalle) {
                $totalLitros = (float) ($detalle->litros ?? $detalle->cantidad_litros);

                $this->ledgerRepo->registrar([
                    'sede_id'             => $viaje->sede_id ?? $viaje->sede_origen_id,
                    'deposito_id'         => $detalle->deposito_origen_id,
                    'tipo_combustible_id' => $viaje->tipo_combustible_id ?? $viaje->tipo,
                    'tipo_transaccion'    => 'DESPACHO',
                    'tipo_movimiento'     => 'despacho',
                    'cantidad_litros'     => -$totalLitros,
                    'cliente_id'          => $detalle->cliente_id ?? $viaje->cliente_id,
                    'bolsa_tipo'          => 'general',
                    'referencia_id'       => $viaje->id,
                    'viaje_id'            => $viaje->id,
                    'referencia_type'     => Viaje::class,
                    'observacion'         => "Salida física por Despacho #{$viaje->id}",
                    'observaciones'       => "Salida física por Despacho #{$viaje->id}"
                ]);

                if ($detalle->deposito_origen_id) {
                    DB::table('depositos')
                        ->where('id', $detalle->deposito_origen_id)
                        ->decrement('nivel_actual_litros', $totalLitros);
                }
            }
        });
    }

    /**
     * Se ejecuta únicamente cuando una Compra (Tipo 4) pasa a "COMPLETADO"
     */
    public function registrarEntradaCompra(Viaje $viaje, int $depositoDestinoId, float $litrosRecibidos): void
    {
        DB::transaction(function () use ($viaje, $depositoDestinoId, $litrosRecibidos) {
            $this->ledgerRepo->registrar([
                'sede_id'             => $viaje->sede_destino_id ?? $viaje->sede_origen_id ?? $viaje->sede_id,
                'deposito_id'         => $depositoDestinoId,
                'tipo_combustible_id' => $viaje->tipo_combustible_id ?? $viaje->tipo,
                'tipo_transaccion'    => 'COMPRA',
                'tipo_movimiento'     => 'compra',
                'cantidad_litros'     => $litrosRecibidos,
                'bolsa_tipo'          => 'general',
                'referencia_id'       => $viaje->id,
                'viaje_id'            => $viaje->id,
                'referencia_type'     => Viaje::class,
                'observacion'         => "Ingreso de inventario por Compra #{$viaje->id} (Completada)",
                'observaciones'       => "Ingreso de inventario por Compra #{$viaje->id} (Completada)"
            ]);

            DB::table('depositos')
                ->where('id', $depositoDestinoId)
                ->increment('nivel_actual_litros', $litrosRecibidos);
        });
    }
}