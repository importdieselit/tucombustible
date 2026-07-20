<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\TransaccionCombustibleRepository;
use App\Models\Vehiculo;
use App\Models\Viaje;
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
        if (!empty($data['vehiculo_externo'])) $data['vehiculo_externo'] = strtoupper($data['vehiculo_externo']);
        if (!empty($data['cisterna_externo'])) $data['cisterna_externo'] = strtoupper($data['cisterna_externo']);
        if (!empty($data['chofer_externo']))   $data['chofer_externo']   = strtoupper($data['chofer_externo']);
        if (!empty($data['ayudante_externo'])) $data['ayudante_externo'] = strtoupper($data['ayudante_externo']);

        return DB::transaction(function () use ($data) {
            $tipoTrasegado = $data['tipo_trasegado']; // 'interno', 'inter_sede', 'externo'
            $totalLitros   = (float) ($data['cantidad_litros'] ?? 0);
            $userId        = $data['user_id'] ?? (auth()->id() ?? 1);

            if ($totalLitros <= 0) {
                throw new Exception("La cantidad de litros debe ser mayor a cero.");
            }

            // --- FASE: TRASEGADO INTERNO (Validaciones e Impacto Físico) ---
            if ($tipoTrasegado === 'interno') {
                $tanqueOrigen = DB::table('depositos')->where('id', $data['deposito_origen_id'])->first();
                $tanqueDestino = DB::table('depositos')->where('id', $data['deposito_destino_id'])->first();

                if (!$tanqueOrigen || !$tanqueDestino) {
                    throw new Exception("Uno o ambos tanques de depósito no existen.");
                }

                if ($tanqueOrigen->id_sede !== $tanqueDestino->id_sede) {
                    throw new Exception("Trasegado inválido. Para operaciones internas, ambos tanques deben pertenecer a la misma sede.");
                }

                if ($tanqueOrigen->tipo_combustible_id !== $tanqueDestino->tipo_combustible_id) {
                    throw new Exception("Operación rechazada. Los tanques no poseen el mismo tipo de combustible.");
                }

                if ($tanqueOrigen->nivel_actual_litros < $totalLitros) {
                    throw new Exception("Stock insuficiente en origen '{$tanqueOrigen->serial}'. Disponible: {$tanqueOrigen->nivel_actual_litros}L.");
                }

                $espacioDisponible = (float)$tanqueDestino->capacidad_litros - (float)$tanqueDestino->nivel_actual_litros;
                if ($totalLitros > $espacioDisponible) {
                    throw new Exception("Capacidad excedida en destino '{$tanqueDestino->serial}'. Espacio libre restante: {$espacioDisponible}L.");
                }

                // Descontar e Incrementar los niveles físicos reales de los tanques
                DB::table('depositos')->where('id', $tanqueOrigen->id)->decrement('nivel_actual_litros', $totalLitros);
                DB::table('depositos')->where('id', $tanqueDestino->id)->increment('nivel_actual_litros', $totalLitros);
            }

            // --- INSERTAR CABECERA EN TRASEGADOS ---
            $trasegadoId = DB::table('trasegados')->insertGetId([
                'tipo_trasegado'      => $tipoTrasegado,
                'sede_origen_id'      => $data['sede_origen_id'],
                'deposito_origen_id'  => $data['deposito_origen_id'],
                'bolsa_origen_tipo'   => $data['bolsa_origen_tipo'], 
                'sede_destino_id'     => $data['sede_destino_id'],
                'deposito_destino_id' => $data['deposito_destino_id'],
                'bolsa_destino_tipo'  => $data['bolsa_destino_tipo'], 
                'aliado_comercial_id' => $data['aliado_comercial_id'] ?? null,
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'cantidad_litros'     => $totalLitros,
                'user_id'             => $userId,
                'status'              => 'completado',
                'observaciones'       => $data['observaciones'] ?? null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // --- IMPACTO EN EL LEDGER (Doble Asiento con ENUM Mapeado) ---
            // Mapeo exacto al ENUM de tu BD: 'trasegado_interno', 'trasegado_inter-sede', 'trasegado_externo'
            $tipoMovimientoLedger = 'trasegado_' . str_replace('_', '-', $tipoTrasegado);

            // Origen: Resta combustible (Para interno/intersede, deposito_id obligatorio)
            $this->transaccionRepo->registrar([
                'sede_id'             => $data['sede_origen_id'],
                'deposito_id'         => $data['deposito_origen_id'], 
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => $data['bolsa_origen_tipo'],
                'tipo_movimiento'     => $tipoMovimientoLedger,
                'cantidad_litros'     => -abs($totalLitros), 
                'user_id'             => $userId,
                'observaciones'       => "Salida por Trasegado #{$trasegadoId}."
            ]);

            // Destino: Suma combustible (Para interno/intersede, deposito_id obligatorio)
            $this->transaccionRepo->registrar([
                'sede_id'             => $data['sede_destino_id'],
                'deposito_id'         => $data['deposito_destino_id'], 
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => $data['bolsa_destino_tipo'],
                'tipo_movimiento'     => $tipoMovimientoLedger,
                'cantidad_litros'     => abs($totalLitros), 
                'user_id'             => $userId,
                'observaciones'       => "Ingreso por Trasegado #{$trasegadoId}."
            ]);

            return $trasegadoId;
        });
    }

    public function anularTrasegado($id, $userId = null)
    {
        return DB::transaction(function () use ($id, $userId) {
            $trasegado = DB::table('trasegados')->where('id', $id)->first();
            
            if (!$trasegado) {
                throw new Exception("El registro de trasegado no existe.");
            }

            if ($trasegado->status === 'anulado') {
                throw new Exception("Este trasegado ya se encuentra anulado.");
            }

            $operadorId = $userId ?? (auth()->id() ?? 1);

            // 1. REVERSIÓN FÍSICA (Solo si fue interno)
            if ($trasegado->tipo_trasegado === 'interno') {
                // Devolvemos al origen
                DB::table('depositos')->where('id', $trasegado->deposito_origen_id)->increment('nivel_actual_litros', $trasegado->cantidad_litros);
                // Quitamos del destino
                DB::table('depositos')->where('id', $trasegado->deposito_destino_id)->decrement('nivel_actual_litros', $trasegado->cantidad_litros);
            }

            // 2. CONTRASENTADO EN EL LEDGER (Usando el ENUM 'reverso' que tiene tu BD)
            // Reverso de la salida (Sumamos lo que se restó en origen)
            $this->transaccionRepo->registrar([
                'sede_id'             => $trasegado->sede_origen_id,
                'deposito_id'         => $trasegado->deposito_origen_id,
                'tipo_combustible_id' => $trasegado->tipo_combustible_id,
                'bolsa_tipo'          => $trasegado->bolsa_origen_tipo,
                'tipo_movimiento'     => 'reverso',
                'cantidad_litros'     => abs($trasegado->cantidad_litros), 
                'user_id'             => $operadorId,
                'viaje_id'            => $trasegado->viaje_id,
                'observaciones'       => "Reverso de Salida por Anulación de Trasegado #{$id}."
            ]);

            // Reverso del ingreso (Restamos lo que se sumó en destino)
            $this->transaccionRepo->registrar([
                'sede_id'             => $trasegado->sede_destino_id,
                'deposito_id'         => $trasegado->deposito_destino_id,
                'tipo_combustible_id' => $trasegado->tipo_combustible_id,
                'bolsa_tipo'          => $trasegado->bolsa_destino_tipo,
                'tipo_movimiento'     => 'reverso',
                'cantidad_litros'     => -abs($trasegado->cantidad_litros), 
                'user_id'             => $operadorId,
                'viaje_id'            => $trasegado->viaje_id,
                'observaciones'       => "Reverso de Ingreso por Anulación de Trasegado #{$id}."
            ]);

            // 3. Si tenía un viaje logístico asociado y estaba programado, se cancela o se evalúa
            if ($trasegado->viaje_id) {
                DB::table('viajes')->where('id', $trasegado->viaje_id)->update(['status' => 'ANULADO', 'updated_at' => now()]);
            }

            // 4. CAMBIO DE ESTADO DE LA CABECERA
            DB::table('trasegados')->where('id', $id)->update([
                'status'     => 'anulado',
                'updated_at' => now()
            ]);

            return true;
        });
    }

    // --- MÉTODOS PRIVADOS AUXILIARES (TUS REGLAS EXACTAS) ---

    private function validarCapacidadYRequisitosTrasegado(array $data, float $totalLitros)
    {
        $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';
        
        if ($esPropio && $totalLitros > 0) {
            if (empty($data['vehiculo_id'])) {
                throw new Exception("El vehículo es obligatorio para planificar el transporte del trasegado.");
            }

            $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
            $capacidadReal = $vehiculo->carga_max > 0 ? $vehiculo->carga_max : 0;
            
            $idCisterna = $data['cisterna'] ?? $data['cisterna_id'] ?? null;

            if ($vehiculo->tipo == '3' && empty($idCisterna)) {
                throw new Exception("El vehículo seleccionado (Tipo 3) requiere una cisterna acoplada.");
            }
            
            if (!empty($idCisterna)) {
                $cisterna = Vehiculo::find($idCisterna);
                if ($cisterna) {
                    $capacidadReal = $cisterna->vol > 0 ? $cisterna->vol : $cisterna->carga_max;
                }
            }

            if ($totalLitros > $capacidadReal) {
                throw new Exception("Capacidad excedida. Carga: {$totalLitros}L / Capacidad permitida: {$capacidadReal}L.");
            }
        }
    }

    private function mapearDatosCabeceraViaje(array $data, float $totalLitros): array
    {
        $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';
        $cisternaValor = null;

        if ($esPropio) {
            $idCisterna = $data['cisterna'] ?? $data['cisterna_id'] ?? null;
            if (!empty($idCisterna)) {
                $c = Vehiculo::find($idCisterna);
                $cisternaValor = $c ? $c->id : null;
            }
        }

        $destinoFinal = 'TRASEGADO';
        if ($data['tipo_trasegado'] === 'inter_sede') {
            $destinoFinal = 'SEDE DESTINO: ' . ($data['sede_destino_id'] ?? '');
        } elseif ($data['tipo_trasegado'] === 'externo' && !empty($data['nombre_cliente_externo'])) {
            $destinoFinal = $data['nombre_cliente_externo'];
        }

        return [
            'tipo_planificacion'   => 5, // ID Único que uses para el tipo de viaje "Trasegados"
            'producto_flete'       => null,
            'sede_id'              => $data['sede_origen_id'] ?? null,
            'tipo'                 => $data['tipo_combustible_id'] ?? null, 
            'fecha_salida'         => $data['fecha_programada'] ?? now(),
            'destino_ciudad'       => $destinoFinal,
            'status'               => 'PROGRAMADO',
            'litros'               => $totalLitros, 
            'vehiculo_id'          => $esPropio ? $data['vehiculo_id'] : null,
            'observacion'          => $data['observaciones'] ?? null,
            'cisterna'             => $cisternaValor,
            'chofer_id'            => $esPropio ? $data['chofer_id'] : null,
            'ayudante_id'          => $esPropio ? ($data['ayudante_id'] ?? null) : null,
            'es_transporte_externo'=> !$esPropio,
            'vehiculo_externo'     => !$esPropio ? ($data['vehiculo_externo'] ?? null) : null,
            'cisterna_externo'     => !$esPropio ? ($data['cisterna_externo'] ?? null) : null,
            'chofer_externo'       => !$esPropio ? ($data['chofer_externo'] ?? null) : null,
            'ayudante_externo'     => !$esPropio ? ($data['ayudante_externo'] ?? null) : null,
            'tipo_remolque'        => $data['tipo_remolque'] ?? null,
            'codigo_sap'           => null,
            'cliente_id'           => null, 
            'nombre_cliente_externo'=> $data['nombre_cliente_externo'] ?? null,
            'punto_salida'         => $data['punto_salida'] ?? null,
            'punto_llegada'        => $data['punto_llegada'] ?? null,
        ];
    }
}