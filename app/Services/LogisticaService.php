<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\PedidoRepository;
use App\Services\LogisticaInventarioService;
use App\Services\CombustibleService; // <--- Importamos el servicio
use App\Models\Vehiculo;
use App\Models\GascoCupoMensual;
use App\Models\Cliente;
use App\Models\Viaje;
use App\Models\Buques;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class LogisticaService
{
    protected $viajeRepo;
    protected $pedidoRepo;
    protected $inventarioService;
    protected $combustibleService; // <--- Declaramos la propiedad

    public function __construct(
        ViajeRepository $viajeRepo,
        PedidoRepository $pedidoRepo,
        LogisticaInventarioService $inventarioService,
        CombustibleService $combustibleService // <--- Lo inyectamos aquí
    ) {
        $this->viajeRepo = $viajeRepo;
        $this->pedidoRepo = $pedidoRepo;
        $this->inventarioService = $inventarioService;
        $this->combustibleService = $combustibleService;
    }

    public function procesarPlanificacion(array $data)
    {
        $this->normalizarStringsExternos($data);

        return DB::transaction(function () use ($data) {
            $tipoPlanificacion = (int) $data['tipo_planificacion']; 
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
            if ($tipoPlanificacion === 4) {
                $this->registrarCompraCombustible($viaje, $data, $totalLitros);
            }

            // 5. Inventario global (Ledger)
            if (!empty($data['tipo_combustible_id'])) {
                $this->inventarioService->registrarCompromisoPlanificacion($viaje);
            } 

            return $viaje;
        });
    }

    /**
     * Actualiza una planificación existente (Revierte impactos previos y aplica nuevos)
     */
    public function actualizarPlanificacion($id, array $data)
    {
        $this->normalizarStringsExternos($data);

        return DB::transaction(function () use ($id, $data) {
            $viaje = Viaje::with('detalles')->findOrFail($id);
            $itemsNuevos = $data['items'] ?? [];

            // 1. REVERSIÓN DE IMPACTOS EN CLIENTES Y PEDIDOS
            $this->revertirImpactosDetalles($viaje);

            // 2. REVERSIÓN DE IMPACTO EN TABLA DE COMPRAS (Si era Tipo 4)
            if ((int) $viaje->tipo_planificacion === 4) {
                DB::table('compras_combustible')->where('viaje_id', $viaje->id)->delete();
            }

            // 3. REVERSIÓN EN EL LEDGER DE INVENTARIO (Aplica independientemente del tipo)
            if (!empty($viaje->tipo) || !empty($viaje->tipo_combustible_id)) {
                $this->inventarioService->revertirCompromisoPlanificacion($viaje);
            }

            // 4. NUEVOS CÁLCULOS Y VALIDACIONES
            $totalLitrosNuevos = $this->calcularTotalLitros($data, $itemsNuevos);
            $this->validarCapacidadYRequisitos($data, $totalLitrosNuevos, $itemsNuevos);

            // 5. ELIMINAR DETALLES ANTIGUOS Y ACTUALIZAR CABECERA
            $viaje->detalles()->delete();
            $datosActualizados = $this->mapearDatosCabecera($data, $totalLitrosNuevos);
            $viaje->update($datosActualizados);

            // 6. APLICAR NUEVOS IMPACTOS
            $this->registrarDetallesConImpacto($viaje, $itemsNuevos);

            // 7. SI ES TIPO 4, RE-CREAR COMPRA
            if ((int) $data['tipo_planificacion'] === 4) {
                $this->registrarCompraCombustible($viaje, $data, $totalLitrosNuevos);
            }

            // 8. ASENTAR NUEVO COMPROMISO EN EL LEDGER
            if (!empty($data['tipo_combustible_id'])) {
                $viaje->refresh();
                $this->inventarioService->registrarCompromisoPlanificacion($viaje);
            }

            return $viaje;
        });
    }

    // --- MÉTODOS PRIVADOS DE APOYO ---

    private function normalizarStringsExternos(array &$data): void
    {
        if (!empty($data['vehiculo_externo'])) $data['vehiculo_externo'] = strtoupper($data['vehiculo_externo']);
        if (!empty($data['cisterna_externo'])) $data['cisterna_externo'] = strtoupper($data['cisterna_externo']);
        if (!empty($data['chofer_externo']))   $data['chofer_externo']   = strtoupper($data['chofer_externo']);
        if (!empty($data['ayudante_externo'])) $data['ayudante_externo'] = strtoupper($data['ayudante_externo']);
    }

    private function revertirImpactosDetalles(Viaje $viaje): void
    {
        if (!in_array((int) $viaje->tipo_planificacion, [1, 2])) return;

        $fechaBase = $viaje->fecha_salida ? Carbon::parse($viaje->fecha_salida) : now();

        foreach ($viaje->detalles as $detalle) {
            
            if (is_null($detalle->pedido_id) && $detalle->cliente_id) {
                $tipoCombustibleId = ((int) $viaje->tipo_planificacion === 1) ? 2 : 1;
                
                // Usamos los litros que guardamos al crear el detalle
                $litrosDevolverSaldo = $detalle->litros_descontados_saldo ?? 0;
                $litrosDevolverCupo  = $detalle->litros_descontados_cupo ?? 0;

                // 1. Revertir impacto en Saldo Pendiente
                if ($litrosDevolverSaldo > 0) {
                    DB::table('saldos_pendientes_clientes')
                        ->where('cliente_id', $detalle->cliente_id)
                        ->where('tipo_combustible_id', $tipoCombustibleId)
                        ->decrement('consumido', $litrosDevolverSaldo);
                }

                // 2. Revertir impacto en Cupo GASCO (Solo Diésel)
                if ($litrosDevolverCupo > 0 && (int) $viaje->tipo_planificacion === 1) {
                    Cliente::where('id', $detalle->cliente_id)
                        ->increment('disponible', $litrosDevolverCupo);
                    
                    GascoCupoMensual::where('cliente_id', $detalle->cliente_id)
                        ->where('mes', $fechaBase->month)
                        ->where('anio', $fechaBase->year)
                        ->decrement('litros_consumidos', $litrosDevolverCupo);
                }
            }
            
            // Si tenía pedido asociado, devolverlo a pendiente
            if ($detalle->pedido_id) {
                $this->pedidoRepo->update($detalle->pedido_id, ['estado' => 'pendiente']);
            }
        }
    }

    private function calcularTotalLitros(array $data, array $items): float
    {
        $tipo = (int) $data['tipo_planificacion'];
        if ($tipo === 4) return (float) ($data['cantidad_litros'] ?? 0);
        if ($tipo === 3) return (float) ($data['litros'] ?? 0);
        return (float) collect($items)->sum('litros');
    }

    private function validarCapacidadYRequisitos(array $data, float $totalLitros, array $items)
    {
        $tipo = (int) $data['tipo_planificacion'];

        if (in_array($tipo, [1, 2]) && empty($items)) {
            throw new Exception("No hay destinos o clientes agregados a la carga.");
        }

        $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';
        
        if ($esPropio && $totalLitros > 0) {
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

    private function mapearDatosCabecera(array $data, float $totalLitros): array
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

        $destinoFinal = 'VARIOS';
        if (!empty($data['destino_ciudad'])) {
            $destinoFinal = is_array($data['destino_ciudad']) 
                ? implode(', ', $data['destino_ciudad']) 
                : $data['destino_ciudad'];
        }

        return [
            'tipo_planificacion'     => $data['tipo_planificacion'],
            'producto_flete'        => $data['tipo_planificacion'] == 3 ? ($data['producto_flete'] ?? null) : null,
            'sede_id'               => $data['sede_id'] ?? null,
            'tipo'                  => $data['tipo_combustible_id'] ?? null, 
            'fecha_salida'          => $data['fecha_programada'],
            'destino_ciudad'        => $destinoFinal,
            'status'                => 'PROGRAMADO',
            'litros'                => $totalLitros, 
            'vehiculo_id'           => $esPropio ? $data['vehiculo_id'] : null,
            'observacion'           => $data['observaciones'] ?? null,
            'cisterna'              => $cisternaValor,
            'chofer_id'             => $esPropio ? $data['chofer_id'] : null,
            'ayudante_id'           => $esPropio ? ($data['ayudante_id'] ?? null) : null,
            
            'es_transporte_externo' => !$esPropio,
            
            'vehiculo_externo'      => !$esPropio ? ($data['vehiculo_externo'] ?? null) : null,
            'cisterna_externo'      => !$esPropio ? ($data['cisterna_externo'] ?? null) : null,
            'chofer_externo'        => !$esPropio ? ($data['chofer_externo'] ?? null) : null,
            'ayudante_externo'      => !$esPropio ? ($data['ayudante_externo'] ?? null) : null,
            
            'tipo_remolque'         => $data['tipo_remolque'] ?? null,
            'codigo_sap'            => $data['tipo_planificacion'] == 4 ? ($data['codigo_sap'] ?? null) : null,
            'cliente_id'            => $data['cliente_id'] ?? null, 
            'nombre_cliente_externo' => $data['nombre_cliente_externo'] ?? null,
            'punto_salida'          => $data['punto_salida'] ?? null,
            'punto_llegada'         => $data['punto_llegada'] ?? null,
        ];
    }

    private function registrarDetallesConImpacto(Viaje $viaje, array $items)
    {
        if (!in_array((int) $viaje->tipo_planificacion, [1, 2])) return;

        foreach ($items as $item) {
            $pedidoId = (isset($item['pedido_id']) && $item['pedido_id'] !== '' && $item['pedido_id'] !== 'null') 
                        ? $item['pedido_id'] : null;
            
            $consumidoSaldo = 0;
            $consumidoCupo = 0;

            // Gestión de Saldo y Cupo vía CombustibleService (Cascada idéntica a Llenado Prepagado)
            if (!empty($item['cliente_id']) && is_null($pedidoId)) { 
                // 1 para Diésel = tipo_combustible 2 | 2 para MGO = tipo_combustible 1
                $tipoCombustibleId = ((int) $viaje->tipo_planificacion === 1) ? 2 : 1; 
                
                $litrosRequeridos = (float) $item['litros'];

                // Delegamos el descuento en cascada al servicio centralizado
                $resumenCobro = $this->combustibleService->procesarDescuentoSaldosCliente(
                    $item['cliente_id'],
                    $tipoCombustibleId,
                    $litrosRequeridos
                );

                // Mapeamos los litros descontados de acuerdo al array/objeto que retorna el servicio
                $consumidoSaldo = is_array($resumenCobro) ? ($resumenCobro['consumido_saldo'] ?? 0) : ($resumenCobro->consumido_saldo ?? 0);
                $consumidoCupo  = is_array($resumenCobro) ? ($resumenCobro['consumido_cupo'] ?? 0) : ($resumenCobro->consumido_cupo ?? 0);
            }

            // Lógica de Buques (MGO / Tipo 2)
            $buqueId = (isset($item['buque_id']) && is_numeric($item['buque_id'])) ? $item['buque_id'] : null;
            $buqueNombreManual = $item['buque_nombre'] ?? null;
            $imo = $item['buque_imo'] ?? null;
            $bandera = $item['buque_bandera'] ?? null;

            if ((int) $viaje->tipo_planificacion === 2 && !empty($item['buque_nombre'])) {
                $nombreFormateado = trim(strtoupper($item['buque_nombre']));
                $buqueExistente = Buques::where('nombre', $nombreFormateado)->first();

                if ($buqueExistente) {
                    $buqueId = $buqueExistente->id;
                    $imo = $buqueExistente->imo;
                    $bandera = $buqueExistente->bandera;
                    $buqueNombreManual = null;
                } elseif (($item['buque_id'] ?? null) === 'manual') {
                    $nuevoBuque = Buques::create([
                        'nombre'     => $nombreFormateado,
                        'cliente_id' => $item['cliente_id'] ?? null,
                        'bandera'    => trim(strtoupper($item['buque_bandera'] ?? 'S/R')),
                        'imo'        => $item['buque_imo'] ?? null,
                    ]);
                    $buqueId = $nuevoBuque->id;
                    $buqueNombreManual = $nombreFormateado;
                }
            }
            
            $this->viajeRepo->createDetalle([
                'viaje_id'                 => $viaje->id,
                'cliente_id'               => $item['cliente_id'] ?? null,
                'pedido_id'                => $pedidoId,
                'litros'                   => $item['litros'] ?? 0,
                'litros_descontados_saldo' => $consumidoSaldo, // Vital para las reversiones
                'litros_descontados_cupo'  => $consumidoCupo,  // Vital para las reversiones
                'muelle_atraque'           => $item['muelle_id'] ?? null,
                'buque_id'                 => $buqueId,
                'buque_nombre_manual'      => $buqueNombreManual,
                'imo'                      => $imo,
                'bandera'                  => $bandera,
                'observacion'              => $item['observaciones'] ?? null,
            ]);

            if (!empty($pedidoId)) {
                $pedido = Pedido::find($pedidoId);
                if ($pedido) {
                    $pedido->update([
                        'estado'           => 'aprobado', 
                        'fecha_aprobacion' => now()
                    ]);
                }
            }
        }
    }

    private function registrarCompraCombustible(Viaje $viaje, array $data, float $totalLitros)
    {
        DB::table('compras_combustible')->insert([
            'viaje_id'          => $viaje->id,
            'planta_proveedor_id' => $data['planta_proveedor_id'],
            'cantidad_litros'   => $totalLitros,
            'planta_destino_id' => $data['sede_id'],
            'fecha'             => $data['fecha_programada'],
            'tipo'              => $data['tipo_combustible_id'],
            'estatus'           => 'EN_TRANSITO',
            'sap'               => $data['codigo_sap'] ?? null,
        ]);
    }
}