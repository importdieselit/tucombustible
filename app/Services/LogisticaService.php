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
            $tipoPlanificacion = $data['tipo_planificacion']; 
            
            $items = $data['items'] ?? [];
            if ($tipoPlanificacion != 4 && empty($items)) {
                throw new Exception("No hay destinos o clientes agregados a la carga.");
            }

            $totalLitros = ($tipoPlanificacion == 4) ? ($data['cantidad_litros'] ?? 0) : collect($items)->sum('litros');

            if (isset($data['es_transporte_propio']) && $data['es_transporte_propio'] == '1') {
                $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
                $capacidadReal = ($vehiculo->carga_max > 0) ? $vehiculo->carga_max : 0;
                
                if (!empty($data['cisterna_id'])) {
                    $cisterna = Vehiculo::find($data['cisterna_id']);
                    $capacidadReal = $cisterna ? $cisterna->carga_max : $capacidadReal;
                }

                if ($totalLitros > $capacidadReal) {
                    throw new Exception("Capacidad excedida. Carga: {$totalLitros}L / Capacidad: {$capacidadReal}L.");
                }
            }

            // 2. CREAR CABECERA DE VIAJE
            $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';

            $viaje = $this->viajeRepo->createViaje([
                'tipo_planificacion'   => $tipoPlanificacion,
                'sede_id'              => $data['sede_id'] ?? null,
                'tipo'                 => $data['tipo_combustible_id'] ?? null, // Corregido: la columna en DB es 'tipo'
                'fecha_salida'         => $data['fecha_programada'],
                'destino_ciudad'       => $data['destino_ciudad'] ?? 'VARIOS',
                'status'               => 'PROGRAMADO',
                'litros'               => $totalLitros, 
                
                // Mapeo Transporte Propio
                'vehiculo_id'          => $esPropio ? $data['vehiculo_id'] : null,
                'cisterna'             => $esPropio ? ($data['cisterna_id'] ?? null) : null, // Corregido a 'cisterna'
                'chofer_id'            => $esPropio ? $data['chofer_id'] : null,
                'ayudante_id'          => $esPropio ? ($data['ayudante_id'] ?? null) : null,
                
                // Mapeo Transporte Externo (Coincidiendo con los names del formulario)
                'es_transporte_externo'=> !$esPropio,
                'vehiculo_externo'     => !$esPropio ? ($data['externo_vehiculo_placa'] ?? null) : null,
                'cisterna_externo'     => !$esPropio ? ($data['externo_cisterna_placa'] ?? null) : null,
                'chofer_externo'       => !$esPropio ? ($data['externo_chofer_nombre'] ?? null) : null,
                'ayudante_externo'     => !$esPropio ? ($data['externo_ayudante_nombre'] ?? null) : null,
                
                'tipo_remolque'        => $tipoPlanificacion == 3 ? ($data['tipo_remolque'] ?? null) : null,
                'codigo_sap'           => $tipoPlanificacion == 4 ? ($data['codigo_sap'] ?? null) : null,
                'nombre_cliente_externo'=> $data['nombre_cliente_externo'] ?? null,
            ]);

            // 3. REGISTRAR DETALLES 
            if (in_array($tipoPlanificacion, [1, 2, 3])) { 
                foreach ($items as $item) {
                    
                    if ($tipoPlanificacion == 1 && !empty($item['cliente_id'])) { 
                        $cliente = Cliente::lockForUpdate()->findOrFail($item['cliente_id']);
                        
                        if ($item['litros'] > $cliente->disponible) {
                            throw new Exception("Saldo insuficiente para {$cliente->nombre}. Disponible: {$cliente->disponible}L");
                        }

                        $cliente->decrement('disponible', $item['litros']);

                        $cupoMensual = GascoCupoMensual::where('cliente_id', $cliente->id)
                            ->where('mes', now()->month)
                            ->where('anio', now()->year)
                            ->first();

                        if ($cupoMensual) {
                            $cupoMensual->increment('litros_consumidos', $item['litros']);
                        }
                    }
                    
                    // REGISTRAR EN DESPACHOS_VIAJES (Corregido mapeo de modal)
                    $this->viajeRepo->createDetalle([
                        'viaje_id'            => $viaje->id,
                        'cliente_id'          => $item['cliente_id'] ?? null,
                        'pedido_id'           => $item['pedido_id'] ?? null,
                        'litros'              => $item['litros'],
                        'muelle_atraque'      => $item['muelle'] ?? null,
                        'direccion_despacho'  => $item['direccion'] ?? null,
                        'buque_nombre_manual' => $item['buque_nombre'] ?? null,
                        'imo'                 => $item['buque_imo'] ?? null, // Nombre exacto del input
                        'bandera'             => $item['buque_bandera'] ?? null, // Nombre exacto del input
                        'observacion'         => $item['observaciones'] ?? null, // Nombre exacto del input
                    ]);

                    if (!empty($item['pedido_id'])) {
                        $this->pedidoRepo->update($item['pedido_id'], ['estado' => 'en_proceso']);
                    }
                }
            } elseif ($tipoPlanificacion == 4) {
                DB::table('compras_combustible')->insert([
                    'viaje_id'          => $viaje->id,
                    'proveedor_id'      => $data['proveedor_id'],
                    'cantidad_litros'   => $totalLitros,
                    'planta_destino_id' => $data['sede_id'],
                    'fecha'             => $data['fecha_programada'],
                    'tipo'              => $data['tipo_combustible_id'],
                    'estatus'           => 'EN_TRANSITO',
                    'sap'               => $data['codigo_sap'] ?? null,
                ]);
            }

            if (in_array($tipoPlanificacion, [1, 2])) {
                $this->afectarInventarioGlobal($data['tipo_combustible_id'], $totalLitros, $viaje->id);
            } 

            return $viaje;
        });
    }

    private function afectarInventarioGlobal($tipoId, $cantidad, $viajeId)
    {
        // 1. Identificar el nombre del producto para la tabla depositos
        $nombreProducto = ($tipoId == 1) ? 'DIESEL' : (($tipoId == 2) ? 'MGO' : null);

        if ($nombreProducto) {
            // Buscamos el depósito para obtener su ID y descontar
            $deposito = DB::table('depositos')
                ->where('producto', $nombreProducto)
                ->first();

            if ($deposito) {
                // Descontamos del depósito
                DB::table('depositos')
                    ->where('id', $deposito->id)
                    ->decrement('nivel_actual_litros', $cantidad);

                // 2. Registrar el movimiento con las columnas correctas del DDL
                DB::table('movimientos_combustible')->insert([
                    'tipo_combustible_id' => $tipoId,
                    'tipo_movimiento'     => 'salida', // Corregido: antes era 'tipo'
                    'deposito_id'         => $deposito->id, // Obligatorio en el DDL
                    'viaje_id'            => $viajeId,
                    'cantidad_litros'     => $cantidad, // Corregido: antes era 'cantidad'
                    'observaciones'       => "PLANIFICACIÓN VIAJE #$viajeId",
                    'created_at'          => now(),
                    'updated_at'          => now()
                ]);
            }
        }
    }
}