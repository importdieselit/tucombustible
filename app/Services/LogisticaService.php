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
            
            // CORRECCIÓN 1: Fletes (3) y Compras (4) NO usan la tabla de destinos
            if (in_array($tipoPlanificacion, [1, 2]) && empty($items)) {
                throw new Exception("No hay destinos o clientes agregados a la carga.");
            }

            // CORRECCIÓN 2: De dónde salen los litros dependiendo de lo que planifiquemos
            $totalLitros = 0;
            if ($tipoPlanificacion == 4) {
                $totalLitros = $data['cantidad_litros'] ?? 0;
            } elseif ($tipoPlanificacion == 3) {
                $totalLitros = $data['litros'] ?? 0; // En fletes, viaja directo del input "Volumen (L)"
            } else {
                $totalLitros = collect($items)->sum('litros');
            }

            // VALIDACIÓN DE CAPACIDAD (Solo si hay litros y es transporte propio)
            $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';
            if ($esPropio && $totalLitros > 0) {
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

            // PREPARAR DATO DE CISTERNA (Para el campo varchar 'cisterna' en DB)
            $cisternaValor = null;
            if ($esPropio) {
                if (!empty($data['cisterna_id'])) {
                    $c = Vehiculo::find($data['cisterna_id']);
                    $cisternaValor = $c ? $c->placa : null;
                }
            } else {
                $cisternaValor = $data['externo_cisterna_placa'] ?? null;
            }

            // CREAR CABECERA DE VIAJE
            $viaje = $this->viajeRepo->createViaje([
                'tipo_planificacion'   => $tipoPlanificacion,
                'sede_id'              => $data['sede_id'] ?? null,
                'tipo'                 => $data['tipo_combustible_id'] ?? null, 
                'fecha_salida'         => $data['fecha_programada'],
                'destino_ciudad'       => $data['destino_ciudad'] ?? 'VARIOS',
                'status'               => 'PROGRAMADO',
                'litros'               => $totalLitros, 
                
                // Mapeo Transporte Propio
                'vehiculo_id'          => $esPropio ? $data['vehiculo_id'] : null,
                'cisterna'             => $cisternaValor, // Guardamos la PLACA
                'chofer_id'            => $esPropio ? $data['chofer_id'] : null,
                'ayudante_id'          => $esPropio ? ($data['ayudante_id'] ?? null) : null,
                
                // Mapeo Transporte Externo
                'es_transporte_externo'=> !$esPropio,
                'vehiculo_externo'     => !$esPropio ? ($data['externo_vehiculo_placa'] ?? null) : null,
                'cisterna_externo'     => !$esPropio ? ($data['externo_cisterna_placa'] ?? null) : null,
                'chofer_externo'       => !$esPropio ? ($data['externo_chofer_nombre'] ?? null) : null,
                'ayudante_externo'     => !$esPropio ? ($data['externo_ayudante_nombre'] ?? null) : null,
                
                'tipo_remolque'        => $data['tipo_remolque'] ?? null,
                'codigo_sap'           => $tipoPlanificacion == 4 ? ($data['codigo_sap'] ?? null) : null,
                // Guardar cliente_id o manual_nombre según la elección del switch
                'cliente_id'           => $data['cliente_id'] ?? null, 
                'nombre_cliente_externo'=> $data['nombre_cliente_externo'] ?? null,
                'punto_salida'         => $data['punto_salida'] ?? null,
                'punto_llegada'        => $data['punto_llegada'] ?? null,
            ]);

            // REGISTRAR DETALLES SOLO SI ES DIESEL (1) O MGO (2)
            if (in_array($tipoPlanificacion, [1, 2])) { 
                foreach ($items as $item) {
                    // Descuento de cupo solo si es Diesel (1)
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
                    
                    $this->viajeRepo->createDetalle([
                        'viaje_id'            => $viaje->id,
                        'cliente_id'          => $item['cliente_id'] ?? null,
                        'pedido_id'           => $item['pedido_id'] ?? null,
                        'litros'              => $item['litros'] ?? 0,
                        'muelle_atraque'      => $item['muelle_id'] ?? null,
                        'buque_nombre_manual' => $item['buque_nombre'] ?? null,
                        'imo'                 => $item['buque_imo'] ?? null,
                        'bandera'             => $item['buque_bandera'] ?? null,
                        'observacion'         => $item['observaciones'] ?? null,
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

            // Inventario global solo para Diesel y MGO
            if (in_array($tipoPlanificacion, [1, 2]) && !empty($data['tipo_combustible_id'])) {
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