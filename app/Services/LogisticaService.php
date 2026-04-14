<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\PedidoRepository;
use App\Models\Vehiculo;
use App\Models\GascoCupoMensual;
use App\Models\Cliente;
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
            // Aseguramos que 'items' exista (pueden venir de pedidos o carga manual)
            $items = $data['items'] ?? [];
            if (empty($items)) throw new Exception("No hay destinos o clientes agregados a la carga.");

            $totalLitros = collect($items)->sum('litros');

            // 1. VALIDACIÓN DE CAPACIDAD
            if ($data['es_transporte_propio']) {
                $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
                // Si es un chuto, la capacidad la da la cisterna acoplada
                $capacidadReal = ($vehiculo->carga_max > 0) ? $vehiculo->carga_max : 0;
                
                if (isset($data['cisterna_id'])) {
                    $cisterna = Vehiculo::find($data['cisterna_id']);
                    $capacidadReal = $cisterna ? $cisterna->carga_max : $capacidadReal;
                }

                if ($totalLitros > $capacidadReal) {
                    throw new Exception("Capacidad excedida. Carga: {$totalLitros}L / Capacidad: {$capacidadReal}L.");
                }
            }

            // 2. CREAR CABECERA DE VIAJE (Tabla 'viajes')
            $viaje = $this->viajeRepo->createViaje([
                'tipo_combustible_id'  => $data['tipo_combustible_id'],
                'fecha_salida'         => $data['fecha_programada'],
                'destino_ciudad'       => $data['destino_ciudad'] ?? 'VARIOS',
                'status'               => 'PROGRAMADO',
                'litros_totales'       => $totalLitros,
                'vehiculo_id'          => $data['es_transporte_propio'] ? $data['vehiculo_id'] : null,
                'cisterna_id'          => $data['es_transporte_propio'] ? ($data['cisterna_id'] ?? null) : null,
                'chofer_id'            => $data['es_transporte_propio'] ? $data['chofer_id'] : null,
                'ayudante_id'          => $data['es_transporte_propio'] ? ($data['ayudante_id'] ?? null) : null,
                'es_transporte_externo'=> !$data['es_transporte_propio'],
                'vehiculo_externo'     => !$data['es_transporte_propio'] ? ($data['vehiculo_externo'] ?? null) : null,
            ]);

            // 3. REGISTRAR DETALLES (Tabla 'despachos_viajes')
            foreach ($data['items'] as $item) {
            $cliente = Cliente::lockForUpdate()->findOrFail($item['cliente_id']);
            
            // Lógica para Diesel (No MGO)
            if ($data['tipo_combustible_id'] != 2) { 
                
                // 1. Validar contra el DISPONIBLE (Saldo real)
                if ($item['litros'] > $cliente->disponible) {
                    throw new Exception("Saldo insuficiente para {$cliente->nombre}. Disponible: {$cliente->disponible}L");
                }

                // 2. Restar del disponible del cliente
                $cliente->decrement('disponible', $item['litros']);

                // 3. Actualizar el consumo en la tabla GASCO del mes actual
                $cupoMensual = GascoCupoMensual::where('cliente_id', $cliente->id)
                    ->where('mes', now()->month)
                    ->where('anio', now()->year)
                    ->first();

                if ($cupoMensual) {
                    $cupoMensual->increment('litros_consumidos', $item['litros']);
                }
            }
                

                // REGISTRAR EN DESPACHOS_VIAJES
                $this->viajeRepo->createDetalle([
                    'viaje_id'            => $viaje->id,
                    'cliente_id'          => $item['cliente_id'],
                    'pedido_id'           => $item['pedido_id'] ?? null,
                    'litros'              => $item['litros'],
                    'muelle_atraque'      => $item['muelle'] ?? null,
                    'direccion_despacho'  => $item['direccion'] ?? null,
                    'buque_nombre_manual' => $item['buque_nombre'] ?? null,
                    'imo'                 => $item['imo'] ?? null,
                    'bandera'             => $item['bandera'] ?? null,
                ]);

                // Si viene de un pedido (Diesel), lo marcamos 'en_proceso'
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
        // 'nivel_actual' es el nombre que solemos usar en tus depósitos
        DB::table('depositos')
            ->where('tipo_combustible_id', $tipoId)
            ->decrement('nivel_actual', $cantidad);
            
        DB::table('movimientos_combustible')->insert([
            'tipo_combustible_id' => $tipoId,
            'tipo' => 'salida',
            'cantidad' => $cantidad,
            'referencia' => "PLANIFICACIÓN VIAJE #$viajeId",
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}