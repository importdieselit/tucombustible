<?php

namespace App\Services;

use App\Repositories\VehiculoPrecargadoRepository;
use App\Repositories\TransaccionCombustibleRepository;
use App\Models\Deposito;
use App\Models\Vehiculo;
use App\Models\VehiculoPrecargado;
use Illuminate\Support\Facades\DB;
use Exception;

class VehiculoPrecargadoService
{
    protected $precargaRepo;
    protected $ledgerRepo;

    public function __construct(
        VehiculoPrecargadoRepository $precargaRepo,
        TransaccionCombustibleRepository $ledgerRepo
    ) {
        $this->precargaRepo = $precargaRepo;
        $this->ledgerRepo = $ledgerRepo;
    }

    public function registrarPrecarga(array $data): VehiculoPrecargado
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['id_usuario'] ?? auth()->id() ?? 1;
            $estaPrecintado = !empty($data['esta_precintado']);
            $cantidadLitros = (float) $data['cantidad_litros'];

            $vehiculo = Vehiculo::findOrFail($data['id_vehiculo']);
            $cargaMax = (float) ($vehiculo->carga_max ?? 0);

            if ($cantidadLitros > $cargaMax) {
                if ($cargaMax === 0.0) {
                    throw new Exception("El vehículo {$vehiculo->placa} no tiene registrada una capacidad máxima de carga en el sistema (0,00 Lts).");
                }

                $capacidadFormateada = number_format($cargaMax, 2, ',', '.');
                throw new Exception("La cantidad ingresada ({$cantidadLitros} Lts) excede la capacidad máxima del vehículo {$vehiculo->placa} ({$capacidadFormateada} Lts).");
            }

            if (!$estaPrecintado) {
                if (empty($data['id_deposito'])) {
                    throw new Exception("Debe seleccionar un depósito origen para precargas no precintadas.");
                }

                $deposito = Deposito::findOrFail($data['id_deposito']);

                if ((float) $deposito->nivel_actual_litros < $cantidadLitros) {
                    throw new Exception("El depósito seleccionado no cuenta con suficiente saldo de combustible ({$deposito->nivel_actual_litros} Lts disponibles).");
                }

                // 1. Descuento del inventario físico en el depósito
                $deposito->decrement('nivel_actual_litros', $cantidadLitros);

                // 2. Asentar salida física en el Ledger como 'precarga'
                $this->ledgerRepo->registrar([
                    'sede_id'             => $data['id_sede'],
                    'deposito_id'         => $data['id_deposito'],
                    'tipo_combustible_id' => $data['id_tipo_combustible'],
                    'bolsa_tipo'          => 'general',
                    'tipo_movimiento'     => 'precarga',
                    'cantidad_litros'     => -$cantidadLitros, // Negativo (-) porque sale del tanque físico
                    'user_id'             => $userId,
                    'observaciones'       => "Salida física por Precarga de Vehículo (Placa: {$vehiculo->placa}, ID Vehículo: {$data['id_vehiculo']})",
                ]);

            } else {
                $data['id_deposito'] = null;

                // Asentar ingreso por precarga externa precintada
                $this->ledgerRepo->registrar([
                    'sede_id'             => $data['id_sede'],
                    'deposito_id'         => null,
                    'tipo_combustible_id' => $data['id_tipo_combustible'],
                    'bolsa_tipo'          => 'general',
                    'tipo_movimiento'     => 'precarga',
                    'cantidad_litros'     => $cantidadLitros,
                    'user_id'             => $userId,
                    'observaciones'       => "Ingreso por Precarga Externa Precintada (Placa: {$vehiculo->placa}, ID Vehículo: {$data['id_vehiculo']})",
                ]);
            }

            // 3. Finalizar automáticamente cualquier precarga previa activa para este vehículo
            VehiculoPrecargado::where('id_vehiculo', $data['id_vehiculo'])
                ->where('estatus', 0)
                ->update(['estatus' => 1]);

            // 4. Crear el nuevo registro en vehiculos_precargados
            $datosRegistro = [
                'id_vehiculo'         => $data['id_vehiculo'],
                'id_sede'             => $data['id_sede'],
                'id_deposito'         => $data['id_deposito'],
                'id_tipo_combustible' => $data['id_tipo_combustible'],
                'id_usuario'          => $userId,
                'cantidad_litros'     => $cantidadLitros,
                'esta_precintado'     => $estaPrecintado,
                'observaciones'       => $data['observaciones'] ?? null,
                'fecha_hora_carga'    => now(),
                'estatus'             => 0, // 0 = Cargada/Activa
            ];

            return $this->precargaRepo->crear($datosRegistro);
        });
    }

    public function obtenerActivas(?int $idSede = null)
    {
        return $this->precargaRepo->obtenerActivas($idSede);
    }

    public function obtenerHistorico(?int $idSede = null, int $perPage = 20)
    {
        return $this->precargaRepo->obtenerHistorico($idSede, $perPage);
    }

    public function finalizarPrecarga(int $id): bool
    {
        return $this->precargaRepo->cambiarEstatus($id, 1);
    }
}