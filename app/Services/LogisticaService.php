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
            $totalLitros = collect($data['items'])->sum('litros');

            // 1. VALIDACIÓN DE CAPACIDAD (Solo si el transporte es propio)
            if ($data['es_transporte_propio']) {
                $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
                $capacidadReal = ($vehiculo->carga_max > 0) 
                    ? $vehiculo->carga_max 
                    : Vehiculo::findOrFail($data['cisterna_id'])->carga_max;

                if ($totalLitros > $capacidadReal) {
                    throw new Exception("Capacidad excedida en unidad propia. Max: {$capacidadReal}L.");
                }
            }

            // 2. CREAR CABECERA DE VIAJE
            $viaje = $this->viajeRepo->createViaje([
                'tipo_combustible_id'  => $data['tipo_combustible_id'],
                'fecha_salida'         => $data['fecha_programada'],
                'destino_ciudad'       => $data['destino_ciudad'] ?? 'VARIOS',
                'status'               => 'PROGRAMADO',
                'litros_totales'       => $totalLitros,
                // Transporte Propio
                'vehiculo_id'          => $data['es_transporte_propio'] ? $data['vehiculo_id'] : null,
                'cisterna_id'          => $data['es_transporte_propio'] ? ($data['cisterna_id'] ?? null) : null,
                'chofer_id'            => $data['es_transporte_propio'] ? $data['chofer_id'] : null,
                // Transporte Externo
                'es_transporte_externo'=> !$data['es_transporte_propio'],
                'vehiculo_externo'     => !$data['es_transporte_propio'] ? $data['vehiculo_externo'] : null,
                'chofer_externo'       => !$data['es_transporte_propio'] ? $data['chofer_externo'] : null,
                'cisterna_externo'     => !$data['es_transporte_propio'] ? $data['cisterna_externo'] : null,
            ]);

            // 3. REGISTRAR DESPACHOS (CLIENTES O BUQUES)
            foreach ($data['items'] as $item) {
                $this->viajeRepo->createDetalle([
                    'viaje_id'            => $viaje->id,
                    'cliente_id'          => $item['cliente_id'],
                    'pedido_id'           => $item['pedido_id'] ?? null,
                    'litros'              => $item['litros'],
                    // Campos específicos MGO (vienen nulos si es Diesel)
                    'muelle_atraque'      => $item['muelle'] ?? null,
                    'direccion_despacho'  => $item['direccion'] ?? null,
                    'buque_id'            => $item['buque_id'] ?? null,
                    'buque_nombre_manual' => $item['buque_nombre'] ?? null,
                    'imo'                 => $item['imo'] ?? null,
                    'bandera'             => $item['bandera'] ?? null,
                ]);

                // Actualizar pedido si existe (Flujo Diesel)
                if (isset($item['pedido_id'])) {
                    $this->pedidoRepo->update($item['pedido_id'], ['estado' => 'en_proceso']);
                }
            }

            // 4. AFECTAR INVENTARIO
            $this->afectarInventarioGlobal($data['tipo_combustible_id'], $totalLitros, $viaje->id);

            return $viaje;
        });
    }

    private function afectarInventarioGlobal($tipoId, $cantidad, $viajeId)
    {
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