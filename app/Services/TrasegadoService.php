<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\TransaccionCombustibleRepository; // Tu repositorio real del Ledger
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
        $this->transaccionRepo = $transaccionRepo; // Inyectado directo para no tocar el otro servicio
    }

    /**
     * Registra un nuevo trasegado y gestiona su logística e inventario.
     */
    /**
     * Registra un nuevo trasegado y gestiona su logística e inventario.
     */
    public function procesarTrasegado(array $data)
    {
        if (!empty($data['vehiculo_externo'])) $data['vehiculo_externo'] = strtoupper($data['vehiculo_externo']);
        if (!empty($data['cisterna_externo'])) $data['cisterna_externo'] = strtoupper($data['cisterna_externo']);
        if (!empty($data['chofer_externo']))   $data['chofer_externo']   = strtoupper($data['chofer_externo']);
        if (!empty($data['ayudante_externo'])) $data['ayudante_externo'] = strtoupper($data['ayudante_externo']);

        return DB::transaction(function () use ($data) {
            $tipoTrasegado = $data['tipo_trasegado']; 
            $totalLitros   = (float) ($data['cantidad_litros'] ?? 0);
            $userId        = $data['user_id'] ?? (auth()->id() ?? 1);

            if ($totalLitros <= 0) {
                throw new Exception("La cantidad de litros debe ser mayor a cero.");
            }

            // --- FASE: TRASEGADO INTERNO (Validaciones e Impacto Físico) ---
            if ($tipoTrasegado === 'interno') {
                // 1. Obtener la data en tiempo real de ambos tanques involucrados
                $tanqueOrigen = DB::table('depositos')->where('id', $data['deposito_origen_id'])->first();
                $tanqueDestino = DB::table('depositos')->where('id', $data['deposito_destino_id'])->first();

                if (!$tanqueOrigen || !$tanqueDestino) {
                    throw new Exception("Uno o ambos tanques de depósito no existen en el sistema.");
                }

                // 2. Regla de Oro: Misma sede
                if ($tanqueOrigen->id_sede !== $tanqueDestino->id_sede) {
                    throw new Exception("Trasegado inválido. Para operaciones internas, ambos tanques deben pertenecer a la misma sede.");
                }

                // 3. Regla de Oro: Mismo combustible
                if ($tanqueOrigen->tipo_combustible_id !== $tanqueDestino->tipo_combustible_id) {
                    throw new Exception("Operación rechazada. Los tanques seleccionados no poseen el mismo tipo de combustible.");
                }

                // 4. Validación de Stock disponible en Origen
                if ($tanqueOrigen->nivel_actual_litros < $totalLitros) {
                    throw new Exception("Stock insuficiente en el tanque de origen '{$tanqueOrigen->serial}'. Disponible: {$tanqueOrigen->nivel_actual_litros}L.");
                }

                // 5. Validación de capacidad disponible en Destino
                $espacioDisponible = (float)$tanqueDestino->capacidad_litros - (float)$tanqueDestino->nivel_actual_litros;
                if ($totalLitros > $espacioDisponible) {
                    throw new Exception("Capacidad excedida en destino '{$tanqueDestino->serial}'. Espacio libre restante: {$espacioDisponible}L.");
                }

                // 6. Validación de bolsa prepagada (Opcional, de acuerdo al campo de tu tabla)
                if ($data['bolsa_destino_tipo'] === 'prepagado' && (int)$tanqueDestino->llena_cupo_prepagado !== 1) {
                    throw new Exception("El tanque de destino no está configurado ni habilitado para admitir cupos prepagados.");
                }

                // 7. Descontar e Incrementar los niveles físicos reales de los tanques
                DB::table('depositos')->where('id', $tanqueOrigen->id)->decrement('nivel_actual_litros', $totalLitros);
                DB::table('depositos')->where('id', $tanqueDestino->id)->increment('nivel_actual_litros', $totalLitros);
            }

            // 1. Verificar si requiere planificación logística de flota (Aplica a inter-sede y externo)
            $requiereViaje = ($tipoTrasegado === 'inter_sede') || ($tipoTrasegado === 'externo' && !empty($data['vehiculo_id']));

            if ($requiereViaje) {
                $this->validarCapacidadYRequisitosTrasegado($data, $totalLitros);
            }

            // 2. Crear el Viaje de Planificación si aplica
            $viajeId = null;
            if ($requiereViaje) {
                $datosViaje = $this->mapearDatosCabeceraViaje($data, $totalLitros);
                $viaje = $this->viajeRepo->createViaje($datosViaje);
                $viajeId = $viaje->id;
            }

            // 3. Registrar en tu tabla 'trasegados'
            $trasegadoId = DB::table('trasegados')->insertGetId([
                'tipo_trasegado'       => $tipoTrasegado,
                'sede_origen_id'       => $data['sede_origen_id'],
                'deposito_origen_id'   => $data['deposito_origen_id'],
                'bolsa_origen_tipo'    => $data['bolsa_origen_tipo'], // 'general' o 'prepagado'
                'sede_destino_id'      => $data['sede_destino_id'],
                'deposito_destino_id'  => $data['deposito_destino_id'],
                'bolsa_destino_tipo'   => $data['bolsa_destino_tipo'], // 'general' o 'prepagado'
                'aliado_comercial_id'  => $data['aliado_comercial_id'] ?? null,
                'tipo_combustible_id'  => $data['tipo_combustible_id'],
                'cantidad_litros'      => $totalLitros,
                'user_id'              => $userId,
                'status'               => 'completado',
                'observaciones'        => $data['observaciones'] ?? null,
                'viaje_id'             => $viajeId,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 4. IMPACTO EN EL LEDGER (Doble Asiento: Sale de Origen, Entra en Destino)
            // Origen: Resta combustible
            $this->transaccionRepo->registrar([
                'sede_id'             => $data['sede_origen_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => $data['bolsa_origen_tipo'],
                'tipo_movimiento'     => 'trasegado_salida',
                'cantidad_litros'     => -abs($totalLitros), // (-) Resta
                'user_id'             => $userId,
                'viaje_id'            => $viajeId,
                'observaciones'       => "Salida por Trasegado Interno #{$trasegadoId}."
            ]);

            // Destino: Suma combustible
            $this->transaccionRepo->registrar([
                'sede_id'             => $data['sede_destino_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => $data['bolsa_destino_tipo'],
                'tipo_movimiento'     => 'trasegado_entrada',
                'cantidad_litros'     => abs($totalLitros), // (+) Suma
                'user_id'             => $userId,
                'viaje_id'            => $viajeId,
                'observaciones'       => "Ingreso por Trasegado Interno #{$trasegadoId}."
            ]);

            return $trasegadoId;
        });
    }

    /**
     * Actualiza un trasegado, revirtiendo los asientos del Ledger y la logística previa.
     */
    public function actualizarTrasegado($id, array $data)
    {
        if (!empty($data['vehiculo_externo'])) $data['vehiculo_externo'] = strtoupper($data['vehiculo_externo']);
        if (!empty($data['cisterna_externo'])) $data['cisterna_externo'] = strtoupper($data['cisterna_externo']);
        if (!empty($data['chofer_externo']))   $data['chofer_externo']   = strtoupper($data['chofer_externo']);
        if (!empty($data['ayudante_externo'])) $data['ayudante_externo'] = strtoupper($data['ayudante_externo']);

        return DB::transaction(function () use ($id, $data) {
            $trasegado = DB::table('trasegados')->where('id', $id)->first();
            if (!$trasegado) {
                throw new Exception("El registro de trasegado no existe.");
            }

            $userId = $data['user_id'] ?? (auth()->id() ?? 1);

            // 1. REVERSIÓN EN EL LEDGER (Asientos inversos para neutralizar)
            // Revertir Salida de Origen (Sumamos lo que se restó)
            $this->transaccionRepo->registrar([
                'text'                => "REVERSIÓN",
                'sede_id'             => $trasegado->sede_origen_id,
                'tipo_combustible_id' => $trasegado->tipo_combustible_id,
                'bolsa_tipo'          => $trasegado->bolsa_origen_tipo,
                'tipo_movimiento'     => 'reversion_trasegado_salida',
                'cantidad_litros'     => abs($trasegado->cantidad_litros), // (+) Contrarresta el negativo
                'user_id'             => $userId,
                'viaje_id'            => $trasegado->viaje_id,
                'observaciones'       => "Reversión por modificación del Trasegado #{$id}."
            ]);

            // Revertir Ingreso de Destino (Restamos lo que se sumó)
            $this->transaccionRepo->registrar([
                'sede_id'             => $trasegado->sede_destino_id,
                'tipo_combustible_id' => $trasegado->tipo_combustible_id,
                'bolsa_tipo'          => $trasegado->bolsa_destino_tipo,
                'tipo_movimiento'     => 'reversion_trasegado_entrada',
                'cantidad_litros'     => -abs($trasegado->cantidad_litros), // (-) Contrarresta el positivo
                'user_id'             => $userId,
                'viaje_id'            => $trasegado->viaje_id,
                'observaciones'       => "Reversión por modificación del Trasegado #{$id}."
            ]);

            // 2. PROCESAR NUEVOS DATOS
            $tipoTrasegado = $data['tipo_trasegado'];
            $totalLitrosNuevos = (float) ($data['cantidad_litros'] ?? 0);
            
            $requiereViaje = ($tipoTrasegado === 'inter_sede') || ($tipoTrasegado === 'externo' && !empty($data['vehiculo_id']));

            if ($requiereViaje) {
                $this->validarCapacidadYRequisitosTrasegado($data, $totalLitrosNuevos);
            }

            // 3. GESTIÓN DE PLANIFICACIÓN LOGÍSTICA (Manteniendo ID del Viaje)
            $viajeId = $trasegado->viaje_id;

            if ($requiereViaje) {
                $datosViaje = $this->mapearDatosCabeceraViaje($data, $totalLitrosNuevos);
                
                if ($viajeId) {
                    // Si ya existía el viaje, lo actualizamos en vez de borrarlo
                    Viaje::where('id', $viajeId)->update($datosViaje);
                } else {
                    // Si antes no requería viaje pero ahora sí, lo creamos de cero
                    $viaje = $this->viajeRepo->createViaje($datosViaje);
                    $viajeId = $viaje->id;
                }
            } else {
                // Si el nuevo tipo ya no requiere transporte pero antes sí tenía uno asociado, se remueve
                if ($viajeId) {
                    Viaje::where('id', $viajeId)->delete();
                    $viajeId = null;
                }
            }

            // 4. Actualizar registro principal de trasegados
            DB::table('trasegados')->where('id', $id)->update([
                'tipo_trasegado'       => $tipoTrasegado,
                'sede_origen_id'       => $data['sede_origen_id'],
                'deposito_origen_id'   => $data['deposito_origen_id'],
                'bolsa_origen_tipo'    => $data['bolsa_origen_tipo'],
                'sede_destino_id'      => $data['sede_destino_id'],
                'deposito_destino_id'  => $data['deposito_destino_id'],
                'bolsa_destino_tipo'   => $data['bolsa_destino_tipo'],
                'aliado_comercial_id'  => $data['aliado_comercial_id'] ?? null,
                'tipo_combustible_id'  => $data['tipo_combustible_id'],
                'cantidad_litros'      => $totalLitrosNuevos,
                'viaje_id'             => $viajeId,
                'observaciones'        => $data['observaciones'] ?? null,
                'updated_at'           => now(),
            ]);

            // 5. Registrar los nuevos movimientos finales en el Ledger
            $this->transaccionRepo->registrar([
                'sede_id'             => $data['sede_origen_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => $data['bolsa_origen_tipo'],
                'tipo_movimiento'     => 'trasegado_salida',
                'cantidad_litros'     => -abs($totalLitrosNuevos),
                'user_id'             => $userId,
                'viaje_id'            => $viajeId,
                'observaciones'       => "Nueva salida por modificación del Trasegado #{$id}."
            ]);

            $this->transaccionRepo->registrar([
                'sede_id'             => $data['sede_destino_id'],
                'tipo_combustible_id' => $data['tipo_combustible_id'],
                'bolsa_tipo'          => $data['bolsa_destino_tipo'],
                'tipo_movimiento'     => 'trasegado_entrada',
                'cantidad_litros'     => abs($totalLitrosNuevos),
                'user_id'             => $userId,
                'viaje_id'            => $viajeId,
                'observaciones'       => "Nuevo ingreso por modificación del Trasegado #{$id}."
            ]);

            return $id;
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