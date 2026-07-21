<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\TransaccionCombustibleRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class TrasegadoService
{
    protected $viajeRepo;
    protected $transaccionRepo;

    public function __construct(ViajeRepository $viajeRepo, TransaccionCombustibleRepository $transaccionRepo)
    {
        $this->viajeRepo = $viajeRepo;
        $this->transaccionRepo = $transaccionRepo;
    }

    public function procesarTrasegado(array $data)
    {
        return DB::transaction(function () use ($data) {
            $tipoTrasegado = $data['tipo_trasegado']; // 'interno', 'inter_sede', 'externo'
            $totalLitros   = (float) ($data['cantidad_litros'] ?? 0);
            $userId        = $data['user_id'] ?? (auth()->id() ?? 1);

            if ($totalLitros <= 0) {
                throw new Exception("La cantidad de litros debe ser mayor a cero.");
            }

            $sedeOrigenId      = $data['sede_origen_id'] ?? null;
            $depositoOrigenId  = $data['deposito_origen_id'] ?? null;
            $sedeDestinoId     = $data['sede_destino_id'] ?? null;
            $depositoDestinoId = $data['deposito_destino_id'] ?? null;

            // Obtención de ID del Aliado y Nombre de Entidad Externa
            $aliadoIdRaw = $data['cliente_id'] ?? $data['aliado_comercial_id'] ?? null;
            $aliadoId    = (is_numeric($aliadoIdRaw) && $aliadoIdRaw > 0) ? (int)$aliadoIdRaw : null;
            $entidadExterna = !empty($data['entidad_externa']) ? trim((string)$data['entidad_externa']) : null;

            // Si vino un aliado seleccionado del select, pero no texto manual, buscamos su nombre para el registro de texto
            if ($aliadoId && empty($entidadExterna)) {
                $clienteObj = DB::table('clientes')->where('id', $aliadoId)->first();
                if ($clienteObj) {
                    $entidadExterna = $clienteObj->nombre ?? $clienteObj->razon_social ?? "Aliado #{$aliadoId}";
                }
            }

            // =========================================================
            // 1. REGLAS DE NEGOCIO Y AFECTACIÓN FÍSICA SEGÚN TIPO
            // =========================================================

            if ($tipoTrasegado === 'interno' || $tipoTrasegado === 'inter_sede') {
                if (!$depositoOrigenId || !$depositoDestinoId) {
                    throw new Exception("Debes especificar los depósitos de origen y destino.");
                }

                $tanqueOrigen  = DB::table('depositos')->where('id', $depositoOrigenId)->first();
                $tanqueDestino = DB::table('depositos')->where('id', $depositoDestinoId)->first();

                if (!$tanqueOrigen || !$tanqueDestino) {
                    throw new Exception("Uno de los depósitos especificados no existe.");
                }

                if ($tipoTrasegado === 'interno' && $tanqueOrigen->id_sede !== $tanqueDestino->id_sede) {
                    throw new Exception("En trasegados internos ambos tanques deben pertenecer a la misma sede.");
                }

                if ($tipoTrasegado === 'inter_sede' && $tanqueOrigen->id_sede === $tanqueDestino->id_sede) {
                    throw new Exception("En trasegados inter-sedes las sedes de origen y destino deben ser distintas.");
                }

                if ($tanqueOrigen->nivel_actual_litros < $totalLitros) {
                    throw new Exception("Stock insuficiente en el origen '{$tanqueOrigen->serial}'. Disponible: {$tanqueOrigen->nivel_actual_litros}L.");
                }

                $espacioDisponible = (float)$tanqueDestino->capacidad_litros - (float)$tanqueDestino->nivel_actual_litros;
                if ($totalLitros > $espacioDisponible) {
                    throw new Exception("Capacidad excedida en el destino. Espacio libre: {$espacioDisponible}L.");
                }

                DB::table('depositos')->where('id', $tanqueOrigen->id)->decrement('nivel_actual_litros', $totalLitros);
                DB::table('depositos')->where('id', $tanqueDestino->id)->increment('nivel_actual_litros', $totalLitros);

            } elseif ($tipoTrasegado === 'externo') {
                if (!$aliadoId && empty($entidadExterna)) {
                    throw new Exception("Debes seleccionar un aliado comercial o especificar el nombre de la entidad externa.");
                }

                $esSalida  = !empty($depositoOrigenId);
                $esEntrada = !empty($depositoDestinoId);

                if (!$esSalida && !$esEntrada) {
                    throw new Exception("Debes indicar un depósito de origen (salida) o de destino (entrada).");
                }

                if ($esSalida && $esEntrada) {
                    throw new Exception("Un trasegado externo solo debe indicar origen O destino, no ambos.");
                }

                if ($esSalida) {
                    $tanqueOrigen = DB::table('depositos')->where('id', $depositoOrigenId)->first();
                    if (!$tanqueOrigen) {
                        throw new Exception("El depósito de origen especificado no existe.");
                    }

                    if ($tanqueOrigen->nivel_actual_litros < $totalLitros) {
                        throw new Exception("Stock insuficiente en el origen '{$tanqueOrigen->serial}'. Disponible: {$tanqueOrigen->nivel_actual_litros}L.");
                    }

                    DB::table('depositos')->where('id', $tanqueOrigen->id)->decrement('nivel_actual_litros', $totalLitros);
                    $sedeOrigenId = $tanqueOrigen->id_sede;
                    $sedeDestinoId = null;
                    $depositoDestinoId = null;

                } else {
                    $tanqueDestino = DB::table('depositos')->where('id', $depositoDestinoId)->first();
                    if (!$tanqueDestino) {
                        throw new Exception("El depósito de destino especificado no existe.");
                    }

                    $espacioDisponible = (float)$tanqueDestino->capacidad_litros - (float)$tanqueDestino->nivel_actual_litros;
                    if ($totalLitros > $espacioDisponible) {
                        throw new Exception("Capacidad excedida en el tanque destino. Espacio libre: {$espacioDisponible}L.");
                    }

                    DB::table('depositos')->where('id', $tanqueDestino->id)->increment('nivel_actual_litros', $totalLitros);
                    $sedeDestinoId = $tanqueDestino->id_sede;
                    $sedeOrigenId = null;
                    $depositoOrigenId = null;
                }
            } else {
                throw new Exception("Tipo de trasegado no válido.");
            }

            // =========================================================
            // 2. CONSTRUCCIÓN DE OBSERVACIONES Y REGISTRO EN BASE DE DATOS
            // =========================================================
            $obsPartes = [];
            if (!empty($entidadExterna)) {
                $obsPartes[] = "Entidad/Aliado: {$entidadExterna}";
            }
            if (!empty($data['observaciones'])) {
                $obsPartes[] = $data['observaciones'];
            }
            $observacionesFinales = !empty($obsPartes) ? implode(' | ', $obsPartes) : null;

            // Inserción en la tabla trasegados
            $trasegadoId = DB::table('trasegados')->insertGetId([
                'tipo_trasegado'      => $tipoTrasegado,
                'sede_origen_id'      => $sedeOrigenId,
                'deposito_origen_id'  => $depositoOrigenId,
                'bolsa_origen_tipo'   => $data['bolsa_origen_tipo'] ?? 'general', 
                'sede_destino_id'     => $sedeDestinoId,
                'deposito_destino_id' => $depositoDestinoId,
                'bolsa_destino_tipo'  => $data['bolsa_destino_tipo'] ?? 'general', 
                'cliente_id'          => $aliadoId,
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'cantidad_litros'     => $totalLitros,
                'user_id'             => $userId,
                'status'              => 'completado',
                'observaciones'       => $observacionesFinales,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // =========================================================
            // 3. REGISTRO EN LEDGER (transacciones_combustible)
            // =========================================================
            // tipo_movimiento exacto: 'trasegado_externo', 'trasegado_interno', 'trasegado_inter_sede'
            $tipoMovimientoLedger = 'trasegado_' . $tipoTrasegado;
            $etiquetaExterna = $entidadExterna ? " ({$entidadExterna})" : "";

            if ($depositoOrigenId) {
                $this->transaccionRepo->registrar([
                    'sede_id'             => $sedeOrigenId,
                    'deposito_id'         => $depositoOrigenId, 
                    'tipo_combustible_id' => $data['tipo_combustible_id'],
                    'bolsa_tipo'          => $data['bolsa_origen_tipo'] ?? 'general',
                    'tipo_movimiento'     => $tipoMovimientoLedger,
                    'cantidad_litros'     => -abs($totalLitros), 
                    'cliente_id'          => $aliadoId,
                    'user_id'             => $userId,
                    'observaciones'       => "Salida por Trasegado {$tipoTrasegado} #{$trasegadoId}" . $etiquetaExterna
                ]);
            }

            if ($depositoDestinoId) {
                $this->transaccionRepo->registrar([
                    'sede_id'             => $sedeDestinoId,
                    'deposito_id'         => $depositoDestinoId, 
                    'tipo_combustible_id' => $data['tipo_combustible_id'],
                    'bolsa_tipo'          => $data['bolsa_destino_tipo'] ?? 'general',
                    'tipo_movimiento'     => $tipoMovimientoLedger,
                    'cantidad_litros'     => abs($totalLitros), 
                    'cliente_id'          => $aliadoId,
                    'user_id'             => $userId,
                    'observaciones'       => "Ingreso por Trasegado {$tipoTrasegado} #{$trasegadoId}" . $etiquetaExterna
                ]);
            }

            return $trasegadoId;
        });
    }

    public function anularTrasegado(int $trasegadoId, ?string $motivo = null)
    {
        return DB::transaction(function () use ($trasegadoId, $motivo) {
            $trasegado = DB::table('trasegados')->where('id', $trasegadoId)->first();

            if (!$trasegado) {
                throw new Exception("El registro de trasegado no existe.");
            }

            if ($trasegado->status === 'ANULADO') {
                throw new Exception("El trasegado ya se encuentra anulado.");
            }

            $userId = auth()->id() ?? $trasegado->user_id;

            if ($trasegado->deposito_origen_id) {
                DB::table('depositos')->where('id', $trasegado->deposito_origen_id)
                    ->increment('nivel_actual_litros', $trasegado->cantidad_litros);

                $this->transaccionRepo->registrar([
                    'sede_id'             => $trasegado->sede_origen_id,
                    'deposito_id'         => $trasegado->deposito_origen_id,
                    'tipo_combustible_id' => $trasegado->tipo_combustible_id,
                    'bolsa_tipo'          => $trasegado->bolsa_origen_tipo ?? 'general',
                    'tipo_movimiento'     => 'anulacion_trasegado',
                    'cantidad_litros'     => abs($trasegado->cantidad_litros),
                    'user_id'             => $userId,
                    'observaciones'       => "Reversión por anulación de Trasegado #{$trasegadoId}"
                ]);
            }

            if ($trasegado->deposito_destino_id) {
                $tanqueDestino = DB::table('depositos')->where('id', $trasegado->deposito_destino_id)->first();

                if (!$tanqueDestino) {
                    throw new Exception("El depósito de destino para la anulación no existe.");
                }

                if ($tanqueDestino->nivel_actual_litros < $trasegado->cantidad_litros) {
                    throw new Exception("No se puede anular el trasegado: El stock actual en el tanque '{$tanqueDestino->serial}' ({$tanqueDestino->nivel_actual_litros}L) es menor a los litros a revertir ({$trasegado->cantidad_litros}L).");
                }

                DB::table('depositos')->where('id', $trasegado->deposito_destino_id)
                    ->decrement('nivel_actual_litros', $trasegado->cantidad_litros);

                $this->transaccionRepo->registrar([
                    'sede_id'             => $trasegado->sede_destino_id,
                    'deposito_id'         => $trasegado->deposito_destino_id,
                    'tipo_combustible_id' => $trasegado->tipo_combustible_id,
                    'bolsa_tipo'          => $trasegado->bolsa_destino_tipo ?? 'general',
                    'tipo_movimiento'     => 'anulacion_trasegado',
                    'cantidad_litros'     => -abs($trasegado->cantidad_litros),
                    'user_id'             => $userId,
                    'observaciones'       => "Reversión por anulación de Trasegado #{$trasegadoId}"
                ]);
            }

            DB::table('trasegados')->where('id', $trasegadoId)->update([
                'status'        => 'ANULADO',
                'observaciones' => trim(($trasegado->observaciones ?? '') . " | ANULADO: " . $motivo),
                'updated_at'    => now(),
            ]);

            return true;
        });
    }
}