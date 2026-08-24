<?php

namespace App\Services;

use App\Repositories\AbastecimientoTanqueRepository;
use App\Repositories\TransaccionCombustibleRepository;
use App\Models\Vehiculo;
use App\Models\Deposito;
use App\Models\VehiculoPrecargado;
use App\Models\AbastecimientoTanque;
use App\Models\CompraCombustible;
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
            $tipoOrigen = $data['tipo_origen']; // 'precarga' o 'compra'
            $idSede = $data['id_sede'];

            // 1. Obtener tanques de la sede excluyendo los de llena_cupo_prepagado = true (1)
            $tanques = Deposito::where('id_sede', $idSede)
                ->where('llena_cupo_prepagado', 0)
                ->get();

            if ($tanques->isEmpty()) {
                throw new Exception("No hay tanques habilitados para recibir abastecimiento en esta sede.");
            }

            // 2. Validar capacidad total disponible en los tanques aptos
            $espacioTotal = $tanques->sum(function ($tanque) {
                return max(0, (float)$tanque->capacidad_litros - (float)$tanque->nivel_actual_litros);
            });

            if ($cantidadLitros > $espacioTotal) {
                throw new Exception("No hay capacidad en los Tanques para recibir este Abastecimiento");
            }

            $idVehiculo = null;
            $idPrecargaOrigen = null;
            $idCompraCombustible = null;
            $obsOrigen = "";

            // 3. Procesar según Tipo de Origen
            if ($tipoOrigen === 'precarga') {
                $precarga = VehiculoPrecargado::findOrFail($data['id_precarga_origen']);
                
                if ((int)$precarga->estatus !== 0) {
                    throw new Exception("La precarga seleccionada ya no se encuentra activa.");
                }

                $litrosPrecargados = (float) $precarga->cantidad_litros;
                if ($cantidadLitros > $litrosPrecargados) {
                    $precargaFormateada = number_format($litrosPrecargados, 2, ',', '.');
                    throw new Exception("No puede trasegar {$cantidadLitros} Lts. La precarga sólo posee {$precargaFormateada} Lts disponibles.");
                }

                $idPrecargaOrigen = $precarga->id;
                $idVehiculo = $precarga->id_vehiculo;
                $obsOrigen = "Vehículo Precargado (ID Precarga: {$precarga->id})";

                // Marcar la precarga actual como despachada
                $precarga->update(['estatus' => 1]);

                // Si es un trasegado parcial, se genera una nueva precarga activa con el remanente
                $sobrantePrecarga = $litrosPrecargados - $cantidadLitros;
                if ($sobrantePrecarga > 0) {
                    VehiculoPrecargado::create([
                        'id_vehiculo'         => $precarga->id_vehiculo,
                        'id_sede'             => $idSede,
                        'id_deposito'         => $precarga->id_deposito,
                        'id_tipo_combustible' => $precarga->id_tipo_combustible,
                        'id_usuario'          => $userId,
                        'esta_precintado'     => $precarga->esta_precintado,
                        'cantidad_litros'     => $sobrantePrecarga,
                        'fecha_hora_carga'    => now(),
                        'estatus'             => 0,
                    ]);
                }

            } elseif ($tipoOrigen === 'compra') {
                $compra = CompraCombustible::findOrFail($data['id_compra_combustible']);
                $idCompraCombustible = $compra->id;
                $idVehiculo = $compra->vehiculo_id ?? null;
                $obsOrigen = "Compra de Combustible (ID Compra: {$compra->id}, SAP: {$compra->sap})";
            }

            // 4. Algoritmo de distribución equitativa del combustible entre los tanques aptos
            $litrosRestantes = $cantidadLitros;

            while ($litrosRestantes > 0) {
                $tanquesConEspacio = $tanques->filter(function ($tanque) {
                    return ((float)$tanque->capacidad_litros - (float)$tanque->nivel_actual_litros) > 0.0001;
                });

                if ($tanquesConEspacio->isEmpty()) {
                    break;
                }

                $numTanques = $tanquesConEspacio->count();
                $cuotaPorTanque = $litrosRestantes / $numTanques;

                foreach ($tanquesConEspacio as $tanque) {
                    $espacioLibre = (float)$tanque->capacidad_litros - (float)$tanque->nivel_actual_litros;
                    $aAsignar = min($cuotaPorTanque, $espacioLibre);

                    if ($aAsignar > 0) {
                        // Incrementar nivel actual del tanque manteniendo su respectivo tipo_combustible_id
                        $tanque->increment('nivel_actual_litros', $aAsignar);
                        $litrosRestantes -= $aAsignar;

                        // Registrar en el Ledger por cada tanque afectado
                        $this->ledgerRepo->registrar([
                            'sede_id'             => $idSede,
                            'deposito_id'         => $tanque->id,
                            'tipo_combustible_id' => $tanque->tipo_combustible_id,
                            'bolsa_tipo'          => 'general',
                            'tipo_movimiento'     => 'abastecimiento',
                            'cantidad_litros'     => $aAsignar,
                            'user_id'             => $userId,
                            'observaciones'       => "Abastecimiento general desde {$obsOrigen} hacia Depósito {$tanque->serial}",
                        ]);
                    }
                }

                if (round($litrosRestantes, 2) <= 0) {
                    $litrosRestantes = 0;
                    break;
                }
            }

            // 5. Crear el registro principal en abastecimientos_tanques (id_deposito queda null)
            $primerTanque = $tanques->first();
            $datosRegistro = [
                'id_sede'               => $idSede,
                'id_vehiculo'           => $idVehiculo,
                'id_deposito'           => null,
                'id_tipo_combustible'   => $primerTanque->tipo_combustible_id,
                'id_usuario'            => $userId,
                'id_precarga_origen'    => $idPrecargaOrigen,
                'id_compra_combustible' => $idCompraCombustible,
                'cantidad_litros'       => $cantidadLitros,
                'fecha_hora'            => now(),
                'observaciones'         => $data['observaciones'] ?? null,
            ];

            return $this->abastecimientoRepo->crear($datosRegistro);
        });
    }

    public function obtenerHistorico(?int $idSede = null, int $perPage = 20)
    {
        return $this->abastecimientoRepo->obtenerTodos($idSede, $perPage);
    }
}