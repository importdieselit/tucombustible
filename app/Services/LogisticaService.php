<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\PedidoRepository;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;
use Exception;

class LogisticaService
{
    protected $viajeRepo;
    protected $pedidoRepo;

    public function __construct(ViajeRepository $viajeRepo, PedidoRepository $pedidoRepo)
    {
        $this->viajeRepo = $viajeRepo;
        $this->pedidoRepo = $pedidoRepo;
    }

    public function procesarPlanificacion(array $data)
    {
        return DB::transaction(function () use ($data) {
            $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
            $totalLitros = collect($data['clientes'])->sum('litros');

            // 1. VALIDACIÓN DE CAPACIDAD
            $capacidadReal = ($vehiculo->carga_max > 0) 
                ? $vehiculo->carga_max 
                : Vehiculo::findOrFail($data['cisterna_id'])->carga_max;

            if ($totalLitros > $capacidadReal) {
                throw new Exception("Capacidad excedida. Max: {$capacidadReal}L, Solicitado: {$totalLitros}L.");
            }

            // 2. CREAR CABECERA DE VIAJE
            $viaje = $this->viajeRepo->createViaje([
                'chofer_id'            => $data['chofer_id'],
                'ayudante'             => $data['ayudante_id'] ? 1 : 0,
                'vehiculo_id'          => $vehiculo->id,
                'cisterna_id'          => $data['cisterna_id'] ?? null,
                'tipo_combustible_id'  => $data['tipo_combustible_id'], // 1=Diesel, 2=MGO
                'litros'               => $totalLitros,
                'status'               => 'PROGRAMADO',
                'destino_ciudad'       => $data['destino_ciudad'] ?? 'VARIOS',
                'fecha_salida'         => $data['fecha_programada'],
            ]);

            // 3. REGISTRAR DESTINOS Y ACTUALIZAR PEDIDOS
            foreach ($data['clientes'] as $c) {
                $this->viajeRepo->createDetalle([
                    'viaje_id'   => $viaje->id,
                    'pedido_id'  => $c['pedido_id'] ?? null,
                    'cliente_id' => $c['cliente_id'],
                    'litros'     => $c['litros'],
                ]);

                if (isset($c['pedido_id'])) {
                    $this->pedidoRepo->update($c['pedido_id'], ['estado' => 'en_proceso']);
                }
            }

            // 4. MOVIMIENTO GLOBAL DE INVENTARIO
            $this->afectarInventarioGlobal($data['tipo_combustible_id'], $totalLitros, $viaje->id);

            return $viaje;
        });
    }

    private function afectarInventarioGlobal($tipoId, $cantidad, $viajeId)
    {
        // Aquí insertas en movimientos_combustible y descuentas del tanque principal
        // Diesel (1) o MGO (2)
        DB::table('depositos')
            ->where('tipo_combustible_id', $tipoId)
            ->decrement('nivel_actual', $cantidad);
            
        DB::table('movimientos_combustible')->insert([
            'tipo_combustible_id' => $tipoId,
            'tipo' => 'salida',
            'cantidad' => $cantidad,
            'referencia' => "VIAJE #$viajeId",
            'created_at' => now()
        ]);
    }
}