<?php

namespace App\Services;

use App\Models\Viaje;
use App\Repositories\TransaccionCombustibleRepository;

class LogisticaInventarioService
{
    protected $transaccionRepo;

    public function __construct(TransaccionCombustibleRepository $transaccionRepo)
    {
        $this->transaccionRepo = $transaccionRepo;
    }

    /**
     * Registra el impacto en la disponibilidad cuando se CREA una planificación (Tipos 1, 2, 3 o 4)
     */
    public function registrarCompromisoPlanificacion(Viaje $viaje): void
    {
        // Determinamos el comportamiento según el tipo de planificación
        switch ($viaje->tipo_planificacion) {
            case 1:
            case 2:
                $tipoMovimiento = 'combustible_comprometido';
                $litros = -abs($viaje->litros); // Resta disponibilidad de venta
                $obs = "Combustible comprometido por Planificación de Despacho #{$viaje->id}.";
                break;

            case 3:
                $tipoMovimiento = 'consumo_flete_comprometido';
                $litros = -abs($viaje->litros); // Resta disponibilidad para consumo operativo de la flota
                $obs = "Diesel comprometido para consumo operativo del Flete #{$viaje->id}.";
                break;

            case 4:
                $tipoMovimiento = 'compra_en_transito';
                $litros = abs($viaje->litros); // Suma a la disponibilidad proyectada (viene en camino)
                $obs = "Combustible en tránsito por Planificación de Compra #{$viaje->id}.";
                break;

            default:
                return; // Si inventan un tipo raro, no hace nada
        }

        $this->transaccionRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $viaje->tipo,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => $tipoMovimiento,
            'cantidad_litros'     => $litros,
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'observaciones'       => $obs
        ]);
    }

    /**
     * Genera el contra-asiento exacto para anular o revertir el impacto previo
     * Se usa al actualizar o cancelar cualquier planificación.
     */
    public function revertirCompromisoPlanificacion(Viaje $viaje): void
    {
        switch ($viaje->tipo_planificacion) {
            case 1:
            case 2:
                $tipoMovimiento = 'liberacion_compromiso';
                $litros = abs($viaje->litros); // (+) Neutraliza la resta anterior
                $obs = "Liberación de compromiso de combustible en Planificación #{$viaje->id}.";
                break;

            case 3:
                $tipoMovimiento = 'liberacion_consumo_flete';
                $litros = abs($viaje->litros); // (+) Neutraliza el consumo del flete
                $obs = "Anulación de Diesel comprometido para Flete #{$viaje->id}.";
                break;

            case 4:
                $tipoMovimiento = 'anulacion_compra_transito';
                $litros = -abs($viaje->litros); // (-) Resta lo que se había sumado en tránsito
                $obs = "Anulación de compra en tránsito por Modificación/Cancelación #{$viaje->id}.";
                break;

            default:
                return;
        }

        $this->transaccionRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $viaje->tipo,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => $tipoMovimiento,
            'cantidad_litros'     => $litros,
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'observaciones'       => $obs
        ]);
    }

    /**
     * Se ejecuta cuando un Despacho (Tipo 1 o 2) pasa a "EN RUTA"
     */
    public function registrarSalidaFisicaDespacho(Viaje $viaje): void
    {
        if (!in_array($viaje->tipo_planificacion, [1, 2])) return;

        // 1. Libera el compromiso comercial
        $this->transaccionRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $viaje->tipo,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => 'liberacion_compromiso',
            'cantidad_litros'     => abs($viaje->litros),
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'observaciones'       => "Liberación de compromiso por salida de planta del Viaje #{$viaje->id}."
        ]);

        // 2. Descuenta el STOCK FÍSICO REAL
        $this->transaccionRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $viaje->tipo,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => 'despacho_fisico',
            'cantidad_litros'     => -abs($viaje->litros),
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'observaciones'       => "Salida física de combustible (Cisterna En Ruta) por Viaje #{$viaje->id}."
        ]);
    }

    /**
     * Se ejecuta cuando una Compra (Tipo 4) pasa a "COMPLETADO"
     */
    public function registrarIngresoFisicoCompra(Viaje $viaje): void
    {
        if ($viaje->tipo_planificacion != 4) return;

        // 1. Damos de baja la disponibilidad que estaba "En Tránsito"
        $this->transaccionRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $viaje->tipo,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => 'liquidacion_compra_transito',
            'cantidad_litros'     => -abs($viaje->litros), // (-) Quita del tránsito
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'observaciones'       => "Liquidación de inventario en tránsito por recepción de Compra #{$viaje->id}."
        ]);

        // 2. Suma el inventario físico real a los tanques
        $this->transaccionRepo->registrar([
            'sede_id'             => $viaje->sede_id,
            'tipo_combustible_id' => $viaje->tipo,
            'bolsa_tipo'          => 'general',
            'tipo_movimiento'     => 'ingreso_compra',
            'cantidad_litros'     => abs($viaje->litros), // (+) Suma al físico real
            'user_id'             => auth()->id() ?? 1,
            'viaje_id'            => $viaje->id,
            'observaciones'       => "Ingreso físico a fosa por Compra Completada #{$viaje->id}."
        ]);
    }
}