<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\PedidoRepository;
use App\Models\Vehiculo;
use App\Models\GascoCupoMensual;
use App\Models\Cliente;
use App\Models\Viaje;
use App\Models\Pedido;
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

    /**
     * Guarda una nueva planificación (Crea el viaje desde cero)
     */
    public function procesarPlanificacion(array $data)
    {
        return DB::transaction(function () use ($data) {
            $tipoPlanificacion = $data['tipo_planificacion']; 
            $items = $data['items'] ?? [];
            
            // 1. Cálculos y Validaciones
            $totalLitros = $this->calcularTotalLitros($data, $items);
            $this->validarCapacidadYRequisitos($data, $totalLitros, $items);

            // 2. Preparar datos de cabecera y Crear el Viaje
            $datosViaje = $this->mapearDatosCabecera($data, $totalLitros);
            $viaje = $this->viajeRepo->createViaje($datosViaje);

            // 3. Registrar Detalles e impactos (Saldos/Pedidos)
            $this->registrarDetallesConImpacto($viaje, $items);

            // 4. Lógica de Compra (Tipo 4)
            if ($tipoPlanificacion == 4) {
                $this->registrarCompraCombustible($viaje, $data, $totalLitros);
            }

            // 5. Inventario global (Solo Diesel y MGO)
            if (in_array($tipoPlanificacion, [1, 2]) && !empty($data['tipo_combustible_id'])) {
                $this->afectarInventarioGlobal($data['tipo_combustible_id'], $totalLitros, $viaje->id);
            } 

            return $viaje;
        });
    }

    /**
     * Actualiza una planificación existente (Revierte impactos previos y aplica nuevos)
     */
    public function actualizarPlanificacion($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $viaje = Viaje::with('detalles')->findOrFail($id);
            $itemsNuevos = $data['items'] ?? [];

            // 1. REVERSIÓN DE IMPACTOS PREVIOS
            if (in_array($viaje->tipo_planificacion, [1, 2])) {
                foreach ($viaje->detalles as $detalle) {
                    // Devolver saldo al cliente si fue despacho directo
                    if ($viaje->tipo_planificacion == 1 && is_null($detalle->pedido_id) && $detalle->cliente_id) {
                        Cliente::where('id', $detalle->cliente_id)->increment('disponible', $detalle->litros);
                        
                        // Revertir cupo mensual
                        GascoCupoMensual::where('cliente_id', $detalle->cliente_id)
                            ->where('mes', $viaje->fecha_salida->month)
                            ->where('anio', $viaje->fecha_salida->year)
                            ->decrement('litros_consumidos', $detalle->litros);
                    }
                    
                    // Si tenía pedido, devolverlo a 'pendiente'
                    if ($detalle->pedido_id) {
                        $this->pedidoRepo->update($detalle->pedido_id, ['estado' => 'pendiente']);
                    }
                }

                // Devolver inventario global
                $this->revertirInventarioGlobal($viaje->tipo, $viaje->litros, $viaje->id);
            }

            // 2. NUEVOS CÁLCULOS Y VALIDACIONES
            $totalLitrosNuevos = $this->calcularTotalLitros($data, $itemsNuevos);
            $this->validarCapacidadYRequisitos($data, $totalLitrosNuevos, $itemsNuevos);

            // 3. ELIMINAR DETALLES ANTIGUOS Y ACTUALIZAR CABECERA
            $viaje->detalles()->delete();
            $datosActualizados = $this->mapearDatosCabecera($data, $totalLitrosNuevos);
            $viaje->update($datosActualizados);

            // 4. APLICAR NUEVOS IMPACTOS
            $this->registrarDetallesConImpacto($viaje, $itemsNuevos);

            // 5. Afectar inventario con la nueva cantidad
            if (in_array($viaje->tipo_planificacion, [1, 2]) && !empty($data['tipo_combustible_id'])) {
                $this->afectarInventarioGlobal($data['tipo_combustible_id'], $totalLitrosNuevos, $viaje->id);
            }

            return $viaje;
        });
    }

    // --- MÉTODOS PRIVADOS DE APOYO (REUTILIZABLES) ---

    private function calcularTotalLitros(array $data, array $items): float
    {
        $tipo = $data['tipo_planificacion'];
        if ($tipo == 4) return $data['cantidad_litros'] ?? 0;
        if ($tipo == 3) return $data['litros'] ?? 0;
        return collect($items)->sum('litros');
    }

    private function validarCapacidadYRequisitos(array $data, float $totalLitros, array $items)
    {
        if (in_array($data['tipo_planificacion'], [1, 2]) && empty($items)) {
            throw new Exception("No hay destinos o clientes agregados a la carga.");
        }

        $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';
        if ($esPropio && $totalLitros > 0) {
            $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
            $capacidadReal = $vehiculo->carga_max > 0 ? $vehiculo->carga_max : 0;
            
            if (!empty($data['cisterna_id'])) {
                $cisterna = Vehiculo::find($data['cisterna_id']);
                $capacidadReal = $cisterna ? $cisterna->carga_max : $capacidadReal;
            }

            if ($totalLitros > $capacidadReal) {
                throw new Exception("Capacidad excedida. Carga: {$totalLitros}L / Capacidad: {$capacidadReal}L.");
            }
        }
    }

    private function mapearDatosCabecera(array $data, float $totalLitros): array
    {
        $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';
        $cisternaValor = null;

        if ($esPropio) {
            if (!empty($data['cisterna_id'])) {
                $c = Vehiculo::find($data['cisterna_id']);
                $cisternaValor = $c ? $c->placa : null;
            }
        } else {
            $cisternaValor = $data['externo_cisterna_placa'] ?? null;
        }

        return [
            'tipo_planificacion'   => $data['tipo_planificacion'],
            'sede_id'              => $data['sede_id'] ?? null,
            'tipo'                 => $data['tipo_combustible_id'] ?? null, 
            'fecha_salida'         => $data['fecha_programada'],
            'destino_ciudad'       => $data['destino_ciudad'] ?? 'VARIOS',
            'status'               => 'PROGRAMADO',
            'litros'               => $totalLitros, 
            'vehiculo_id'          => $esPropio ? $data['vehiculo_id'] : null,
            'observacion'        => $data['observaciones'] ?? null,
            'cisterna'             => $cisternaValor,
            'chofer_id'            => $esPropio ? $data['chofer_id'] : null,
            'ayudante_id'          => $esPropio ? ($data['ayudante_id'] ?? null) : null,
            'es_transporte_externo'=> !$esPropio,
            'vehiculo_externo'     => !$esPropio ? ($data['externo_vehiculo_placa'] ?? null) : null,
            'cisterna_externo'     => !$esPropio ? ($data['externo_cisterna_placa'] ?? null) : null,
            'chofer_externo'       => !$esPropio ? ($data['externo_chofer_nombre'] ?? null) : null,
            'ayudante_externo'     => !$esPropio ? ($data['externo_ayudante_nombre'] ?? null) : null,
            'tipo_remolque'        => $data['tipo_remolque'] ?? null,
            'codigo_sap'           => $data['tipo_planificacion'] == 4 ? ($data['codigo_sap'] ?? null) : null,
            'cliente_id'           => $data['cliente_id'] ?? null, 
            'nombre_cliente_externo'=> $data['nombre_cliente_externo'] ?? null,
            'punto_salida'         => $data['punto_salida'] ?? null,
            'punto_llegada'        => $data['punto_llegada'] ?? null,
        ];
    }

    private function registrarDetallesConImpacto($viaje, array $items)
    {
        if (!in_array($viaje->tipo_planificacion, [1, 2])) return;

        foreach ($items as $item) {
            $pedidoId = (isset($item['pedido_id']) && $item['pedido_id'] !== '' && $item['pedido_id'] !== 'null') 
                        ? $item['pedido_id'] : null;
            
            // Gestión de Saldo y Cupo (Solo si NO hay pedido y es Tipo 1)
            if ($viaje->tipo_planificacion == 1 && !empty($item['cliente_id']) && is_null($pedidoId)) { 
                $cliente = Cliente::lockForUpdate()->findOrFail($item['cliente_id']);
                if ($item['litros'] > $cliente->disponible) {
                    throw new Exception("Saldo insuficiente para {$cliente->nombre}.");
                }
                $cliente->decrement('disponible', $item['litros']);

                GascoCupoMensual::where('cliente_id', $cliente->id)
                    ->where('mes', now()->month)
                    ->where('anio', now()->year)
                    ->increment('litros_consumidos', $item['litros']);
            }
            
            $this->viajeRepo->createDetalle([
                'viaje_id'            => $viaje->id,
                'cliente_id'          => $item['cliente_id'] ?? null,
                'pedido_id'           => $pedidoId,
                'litros'              => $item['litros'] ?? 0,
                'muelle_atraque'      => $item['muelle_id'] ?? null,
                'buque_id'            => (isset($item['buque_id']) && is_numeric($item['buque_id'])) ? $item['buque_id'] : null,
                'buque_nombre_manual' => $item['buque_nombre'] ?? null,
                'imo'                 => $item['buque_imo'] ?? null,
                'bandera'             => $item['buque_bandera'] ?? null,
                'observacion'         => $item['observaciones'] ?? null,
            ]);

            if ($pedidoId) {
                $this->pedidoRepo->update($pedidoId, [
                    'estado' => 'en_proceso',
                    'fecha_aprobacion' => now()
                ]);
            }
        }
    }

    private function registrarCompraCombustible($viaje, array $data, float $totalLitros)
    {
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

    private function afectarInventarioGlobal($tipoId, $cantidad, $viajeId)
    {
        $nombreProducto = ($tipoId == 1) ? 'DIESEL' : (($tipoId == 2) ? 'MGO' : null);
        if ($nombreProducto) {
            $deposito = DB::table('depositos')->where('producto', $nombreProducto)->first();
            if ($deposito) {
                DB::table('depositos')->where('id', $deposito->id)->decrement('nivel_actual_litros', $cantidad);
                DB::table('movimientos_combustible')->insert([
                    'tipo_combustible_id' => $tipoId,
                    'tipo_movimiento'     => 'salida',
                    'deposito_id'         => $deposito->id,
                    'viaje_id'            => $viajeId,
                    'cantidad_litros'     => $cantidad,
                    'observaciones'       => "PLANIFICACIÓN VIAJE #$viajeId",
                    'created_at'          => now(),
                    'updated_at'          => now()
                ]);
            }
        }
    }

    private function revertirInventarioGlobal($tipoId, $cantidad, $viajeId) 
    {
        $nombreProducto = ($tipoId == 1) ? 'DIESEL' : (($tipoId == 2) ? 'MGO' : null);
        if ($nombreProducto) {
            DB::table('depositos')->where('producto', $nombreProducto)->increment('nivel_actual_litros', $cantidad);
            DB::table('movimientos_combustible')->where('viaje_id', $viajeId)->delete();
        }
    }
}