<?php

namespace App\Services;

use App\Repositories\AbastecimientoTanqueRepository;
use App\Repositories\TransaccionCombustibleRepository;
use App\Models\Vehiculo;
use App\Models\Deposito;
use App\Models\VehiculoPrecargado;
use App\Models\AbastecimientoTanque;
use Illuminate\Support\Facades\DB;
use Exception;

class AbastecimientoTanqueService
{
    protected $abastecimientoRepo;
    protected $ledgerRepo;

    public function __construct(
        AbastecimientoTanqueRepository $abastecimientoRepo,
        TransaccionCombustibleRepository $ledgerRepo
    ) {
        $this->abastecimientoRepo = $abastecimientoRepo;
        $this->ledgerRepo = $ledgerRepo;
    }

    /**
     * Procesa y registra el trasegado de combustible desde un vehículo hacia un depósito.
     */
    public function registrarAbastecimiento(array $data): AbastecimientoTanque
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['id_usuario'] ?? auth()->id() ?? 1;
            $cantidadLitros = (float) $data['cantidad_litros'];

            // 1. Obtener Vehículo y validar Capacidad Máxima del Vehículo
            $vehiculo = Vehiculo::findOrFail($data['id_vehiculo']);
            $cargaMaxVehiculo = (float) ($vehiculo->carga_max ?? 0);

            if ($cantidadLitros > $cargaMaxVehiculo) {
                if ($cargaMaxVehiculo === 0.0) {
                    throw new Exception("El vehículo {$vehiculo->placa} no tiene registrada una capacidad máxima de carga (0,00 Lts).");
                }
                $maxFormateado = number_format($cargaMaxVehiculo, 2, ',', '.');
                throw new Exception("La cantidad a trasegar ({$cantidadLitros} Lts) supera la capacidad máxima del vehículo {$vehiculo->placa} ({$maxFormateado} Lts).");
            }

            // 2. Obtener Depósito y validar Capacidad y Espacio Disponible
            $deposito = Deposito::findOrFail($data['id_deposito']);
            $capacidadTanque = (float) $deposito->capacidad_litros;
            $nivelActualTanque = (float) $deposito->nivel_actual_litros;
            $espacioDisponible = $capacidadTanque - $nivelActualTanque;

            if ($cantidadLitros > $espacioDisponible) {
                $disponibleFormateado = number_format($espacioDisponible, 2, ',', '.');
                throw new Exception("La cantidad ingresada ({$cantidadLitros} Lts) supera el espacio disponible en el depósito {$deposito->serial} ({$disponibleFormateado} Lts disponibles).");
            }

            // 3. Validar Compatibilidad de Tipo de Combustible
            if (!is_null($deposito->tipo_combustible_id) && (int)$deposito->tipo_combustible_id !== (int)$data['id_tipo_combustible']) {
                throw new Exception("El tipo de combustible seleccionado no coincide con el producto asignado al depósito {$deposito->serial}.");
            }

            // 4. Verificar si el vehículo posee una Precarga activa (estatus = 0)
            $precargaActiva = VehiculoPrecargado::where('id_vehiculo', $vehiculo->id)
                ->where('estatus', 0)
                ->first();

            $idPrecargaOrigen = null;

            if ($precargaActiva) {
                $litrosPrecargados = (float) $precargaActiva->cantidad_litros;

                if ($cantidadLitros > $litrosPrecargados) {
                    $precargaFormateada = number_format($litrosPrecargados, 2, ',', '.');
                    throw new Exception("No puede trasegar {$cantidadLitros} Lts. El vehículo sólo tiene {$precargaFormateada} Lts disponibles en su precarga activa.");
                }

                $idPrecargaOrigen = $precargaActiva->id;

                // Marcar precarga anterior como finalizada/despachada (estatus = 1)
                $precargaActiva->update(['estatus' => 1]);

                // Si es trasegado parcial, se genera una nueva precarga con el saldo restante
                $sobrantePrecarga = $litrosPrecargados - $cantidadLitros;
                if ($sobrantePrecarga > 0) {
                    VehiculoPrecargado::create([
                        'id_vehiculo'         => $vehiculo->id,
                        'id_sede'             => $data['id_sede'],
                        'id_deposito'         => $precargaActiva->id_deposito,
                        'id_tipo_combustible' => $data['id_tipo_combustible'],
                        'id_usuario'          => $userId,
                        'esta_precintado'     => $precargaActiva->esta_precintado,
                        'cantidad_litros'     => $sobrantePrecarga,
                        'fecha_hora_carga'    => now(),
                        'estatus'             => 0, // Nueva precarga activa con el resto
                    ]);
                }
            }

            // 5. Incrementar nivel actual del Depósito
            $deposito->increment('nivel_actual_litros', $cantidadLitros);

            // 6. Asentar entrada física en el Ledger como Abastecimiento
            $this->ledgerRepo->registrar([
                'sede_id'             => $data['id_sede'],
                'deposito_id'         => $deposito->id,
                'tipo_combustible_id' => $data['id_tipo_combustible'],
                'bolsa_tipo'          => 'general',
                'tipo_movimiento'     => 'abastecimiento',
                'cantidad_litros'     => $cantidadLitros, // Positivo (+) entra al depósito
                'user_id'             => $userId,
                'observaciones'       => "Ingreso físico por Abastecimiento de Tanque desde Vehículo (Placa: {$vehiculo->placa}, Depósito ID: {$deposito->id})",
            ]);

            // 7. Crear registro en la tabla de abastecimientos
            $datosRegistro = [
                'id_sede'             => $data['id_sede'],
                'id_vehiculo'         => $vehiculo->id,
                'id_deposito'         => $deposito->id,
                'id_tipo_combustible' => $data['id_tipo_combustible'],
                'id_usuario'          => $userId,
                'id_precarga_origen'  => $idPrecargaOrigen,
                'cantidad_litros'     => $cantidadLitros,
                'fecha_hora'          => now(),
                'observaciones'       => $data['observaciones'] ?? null,
            ];

            return $this->abastecimientoRepo->crear($datosRegistro);
        });
    }

    public function obtenerHistorico(?int $idSede = null, int $perPage = 20)
    {
        return $this->abastecimientoRepo->obtenerTodos($idSede, $perPage);
    }
}