<?php

namespace App\Services;

use App\Repositories\ViajeRepository;
use App\Repositories\PedidoRepository;
use App\Models\Vehiculo;
use App\Models\GascoCupoMensual;
use App\Models\Cliente;
use App\Models\Viaje;
use App\Models\Buques;
use App\Models\Pedido;
use Carbon\Carbon;
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
        if (!empty($data['vehiculo_externo'])) $data['vehiculo_externo'] = strtoupper($data['vehiculo_externo']);
        if (!empty($data['cisterna_externo'])) $data['cisterna_externo'] = strtoupper($data['cisterna_externo']);
        if (!empty($data['chofer_externo']))   $data['chofer_externo']   = strtoupper($data['chofer_externo']);
        if (!empty($data['ayudante_externo'])) $data['ayudante_externo'] = strtoupper($data['ayudante_externo']);

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
        if (!empty($data['vehiculo_externo'])) $data['vehiculo_externo'] = strtoupper($data['vehiculo_externo']);
        if (!empty($data['cisterna_externo'])) $data['cisterna_externo'] = strtoupper($data['cisterna_externo']);
        if (!empty($data['chofer_externo']))   $data['chofer_externo']   = strtoupper($data['chofer_externo']);
        if (!empty($data['ayudante_externo'])) $data['ayudante_externo'] = strtoupper($data['ayudante_externo']);

        return DB::transaction(function () use ($id, $data) {
            $viaje = Viaje::with('detalles')->findOrFail($id);
            $itemsNuevos = $data['items'] ?? [];

            // 1. REVERSIÓN DE IMPACTOS PREVIOS
            if (in_array($viaje->tipo_planificacion, [1, 2])) {
                foreach ($viaje->detalles as $detalle) {
                    // Devolver saldo al cliente si fue despacho directo
                    if ($viaje->tipo_planificacion == 2 && is_null($detalle->pedido_id) && $detalle->cliente_id) {
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

            // Actualizar los datos de la compra si es Tipo 4
            if ($viaje->tipo_planificacion == 4) {
                DB::table('compras_combustible')->where('viaje_id', $viaje->id)->update([
                    'planta_proveedor_id' => $data['planta_proveedor_id'],
                    'cantidad_litros'     => $totalLitrosNuevos,
                    'planta_destino_id'   => $data['sede_id'],
                    'fecha'               => $data['fecha_programada'],
                    'tipo'                => $data['tipo_combustible_id'],
                    'sap'                 => $data['codigo_sap'] ?? null,
                    // Se asume que mantiene 'EN_TRANSITO' o el estatus que tenga actualmente
                ]);
            }

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
        if (!empty($data['fecha_programada'])) {
            // Parseamos la fecha y extraemos solo la hora en formato 24h (HH:mm)
            $horaSalida = Carbon::parse($data['fecha_programada'])->format('H:i');
            
            if ($horaSalida < '05:00' || $horaSalida > '15:00') {
                // Al usar session()->flash, el mensaje se enviará a la vista pero la ejecución CONTINÚA
                session()->flash('warning', "Se recomienda que la hora de salida de las planificaciones esté comprendida entre las 5:00 A.M. y las 3:00 P.M.");
            }
        }

        // Validación de destinos (intacta)
        if (in_array($data['tipo_planificacion'], [1, 2]) && empty($items)) {
            throw new Exception("No hay destinos o clientes agregados a la carga.");
        }

        $esPropio = ($data['es_transporte_propio'] ?? '1') == '1';
        
        if ($esPropio && $totalLitros > 0) {
            $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
            
            // Capacidad inicial asumiendo que es un camión rígido (Tipo 1)
            $capacidadReal = $vehiculo->carga_max > 0 ? $vehiculo->carga_max : 0;
            
            // 2. Buscamos el ID de la cisterna (Cubrimos ambos nombres por seguridad)
            $idCisterna = $data['cisterna'] ?? $data['cisterna_id'] ?? null;

            // 3. Validar si es Chuto (Tipo 3) y no mandaron cisterna
            if ($vehiculo->tipo == '3' && empty($idCisterna)) {
                throw new Exception("El vehículo seleccionado (Tipo 3) requiere una cisterna acoplada.");
            }
            
            // 4. Si hay cisterna, reemplazamos la capacidad por la de la cisterna
            if (!empty($idCisterna)) {
                $cisterna = Vehiculo::find($idCisterna);
                if ($cisterna) {
                    // Priorizamos el campo 'vol' (volumen real) y si está vacío usamos 'carga_max'
                    $capacidadReal = $cisterna->vol > 0 ? $cisterna->vol : $cisterna->carga_max;
                }
            }

            // 5. Verificamos el exceso de carga (intacto)
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
            // Corregido: Se busca 'cisterna' que es la llave real enviada por la Vista
            $idCisterna = $data['cisterna'] ?? $data['cisterna_id'] ?? null;
            if (!empty($idCisterna)) {
                $c = Vehiculo::find($idCisterna);
                $cisternaValor = $c ? $c->id : null;
            }
        }

        // 🔄 CAPTURA SEGURO DE DESTINOS MÚLTIPLES
        // Si es un array (que es lo que manda la nueva vista), lo unimos con comas.
        $destinoFinal = 'VARIOS';
        if (!empty($data['destino_ciudad'])) {
            $destinoFinal = is_array($data['destino_ciudad']) 
                ? implode(', ', $data['destino_ciudad']) 
                : $data['destino_ciudad'];
        }

        return [
            'tipo_planificacion'   => $data['tipo_planificacion'],
            'producto_flete'       => $data['tipo_planificacion'] == 3 ? ($data['producto_flete'] ?? null) : null,
            'sede_id'              => $data['sede_id'] ?? null,
            'tipo'                 => $data['tipo_combustible_id'] ?? null, 
            'fecha_salida'         => Carbon::parse($data['fecha_programada']),
            'destino_ciudad'       => $destinoFinal,
            'status'               => 'PROGRAMADO',
            'litros'               => $totalLitros, 
            'vehiculo_id'          => $esPropio ? $data['vehiculo_id'] : null,
            'observacion'          => $data['observaciones'] ?? null,
            'cisterna'             => $cisternaValor,
            'chofer_id'            => $esPropio ? $data['chofer_id'] : null,
            'ayudante_id'          => $esPropio ? ($data['ayudante_id'] ?? null) : null,
            'ayudante'          => $esPropio ? ($data['ayudante_id'] ?? null) : null,
            
            // Flags de Transporte Externo
            'es_transporte_externo'=> !$esPropio,
            
            // Corregido: Mapeo directo con los nombres reales de los inputs de la Vista
            'vehiculo_externo'     => !$esPropio ? ($data['vehiculo_externo'] ?? null) : null,
            'cisterna_externo'     => !$esPropio ? ($data['cisterna_externo'] ?? null) : null,
            'chofer_externo'       => !$esPropio ? ($data['chofer_externo'] ?? null) : null,
            'ayudante_externo'     => !$esPropio ? ($data['ayudante_externo'] ?? null) : null,
            
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
            if ($viaje->tipo_planificacion == 2 && !empty($item['cliente_id']) && is_null($pedidoId)) { 
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

            // --- NUEVA LÓGICA DE AUTO-REGISTRO COMPATIBLE ---
            $buqueId = (isset($item['buque_id']) && is_numeric($item['buque_id'])) ? $item['buque_id'] : null;
            $buqueNombreManual = $item['buque_nombre'] ?? null;
            $imo = $item['buque_imo'] ?? null;
            $bandera = $item['buque_bandera'] ?? null;

            // Si es una planificación MGO y se intentó escribir/seleccionar un buque
            if ($viaje->tipo_planificacion == 1 && !empty($item['buque_nombre'])) {
                $nombreFormateado = trim(strtoupper($item['buque_nombre']));

                // Validamos si ya existe un buque registrado con ese nombre exacto en mayúsculas
                $buqueExistente = Buques::where('nombre', $nombreFormateado)->first();

                if ($buqueExistente) {
                    // Si ya existía, usamos su ID y sus datos reales para blindar la consistencia
                    $buqueId = $buqueExistente->id;
                    $imo = $buqueExistente->imo;
                    $bandera = $buqueExistente->bandera;
                    $buqueNombreManual = null; // Al estar registrado, no hace falta marcarlo como manual puro
                } elseif ($item['buque_id'] === 'manual') {
                    // Si de verdad es nuevo y seleccionaron "Agregar Manualmente", lo creamos al vuelo casado con el cliente_id
                    $nuevoBuque = Buques::create([
                        'nombre'     => $nombreFormateado,
                        'cliente_id' => $item['cliente_id'],
                        'bandera'    => trim(strtoupper($item['buque_bandera'] ?? 'S/R')),
                        'imo'        => $item['buque_imo'] ?? null,
                    ]);
                    $buqueId = $nuevoBuque->id;
                    $buqueNombreManual = $nombreFormateado;
                }
            }
            
            // Ejecución exacta de tu repositorio conservando tus mapeos
            $this->viajeRepo->createDetalle([
                'viaje_id'            => $viaje->id,
                'cliente_id'          => $item['cliente_id'] ?? null,
                'pedido_id'           => $pedidoId,
                'litros'              => $item['litros'] ?? 0,
                'muelle_atraque'      => $item['muelle_id'] ?? null,
                'buque_id'            => $buqueId, // ID Real (existente o creado al vuelo)
                'buque_nombre_manual' => $buqueNombreManual,
                'imo'                 => $imo,
                'bandera'             => $bandera,
                'observacion'         => $item['observaciones'] ?? null,
            ]);

            if (!empty($item['pedido_id'])) {
                $pedido = Pedido::find($item['pedido_id']);
                if ($pedido) {
                    $pedido->update([
                        'estado' => Pedido::STATUS_APROBADO, 
                        'fecha_aprobacion' => now()
                    ]);
                }
            }
        }
    }

    private function registrarCompraCombustible($viaje, array $data, float $totalLitros)
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

    private function afectarInventarioGlobal($tipoId, $cantidad, $viajeId)
    {
        $nombreProducto = ($tipoId == 2) ? 'DIESEL' : (($tipoId == 1) ? 'MGO' : null);
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
        $nombreProducto = ($tipoId == 2) ? 'DIESEL' : (($tipoId == 1) ? 'MGO' : null);
        if ($nombreProducto) {
            DB::table('depositos')->where('producto', $nombreProducto)->increment('nivel_actual_litros', $cantidad);
            DB::table('movimientos_combustible')->where('viaje_id', $viajeId)->delete();
        }
    }
}