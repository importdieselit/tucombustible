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
            $tipoPlanificacion = $data['tipo_planificacion']; // 1:Diesel, 2:MGO, 3:Flete, 4:Compra
            
            // Aseguramos que 'items' exista (excepto en Compras, que la lógica de detalle es distinta)
            $items = $data['items'] ?? [];
            if ($tipoPlanificacion != 4 && empty($items)) {
                throw new Exception("No hay destinos o clientes agregados a la carga.");
            }

            // Para compras, los litros totales vienen del form general, no de los items
            $totalLitros = ($tipoPlanificacion == 4) ? ($data['cantidad_litros'] ?? 0) : collect($items)->sum('litros');

            // 1. VALIDACIÓN DE CAPACIDAD (Aplica a transporte propio)
            if (isset($data['es_transporte_propio']) && $data['es_transporte_propio']) {
                $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
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
            // Se añaden los campos nuevos de las migraciones sin tocar los viejos
            $viaje = $this->viajeRepo->createViaje([
                'tipo_planificacion'   => $tipoPlanificacion,
                'sede_id'              => $data['sede_id'] ?? null,
                'tipo_combustible_id'  => $data['tipo_combustible_id'] ?? null,
                'fecha_salida'         => $data['fecha_programada'],
                'destino_ciudad'       => $data['destino_ciudad'] ?? 'VARIOS',
                'status'               => 'PROGRAMADO',
                'litros_totales'       => $totalLitros, // Mantenemos tu campo original
                'litros'               => $totalLitros, // Campo redundante original
                'vehiculo_id'          => $data['es_transporte_propio'] ? $data['vehiculo_id'] : null,
                'cisterna_id'          => $data['es_transporte_propio'] ? ($data['cisterna_id'] ?? null) : null,
                'chofer_id'            => $data['es_transporte_propio'] ? $data['chofer_id'] : null,
                'ayudante_id'          => $data['es_transporte_propio'] ? ($data['ayudante_id'] ?? null) : null,
                'es_transporte_externo'=> !$data['es_transporte_propio'],
                'vehiculo_externo'     => !$data['es_transporte_propio'] ? ($data['vehiculo_externo'] ?? null) : null,
                // Campos específicos
                'tipo_remolque'        => $tipoPlanificacion == 3 ? ($data['tipo_remolque'] ?? null) : null,
                'codigo_sap'           => $tipoPlanificacion == 4 ? ($data['codigo_sap'] ?? null) : null,
                'nombre_cliente_externo'=> $data['nombre_cliente_externo'] ?? null,
            ]);

            // 3. REGISTRAR DETALLES 
            if (in_array($tipoPlanificacion, [1, 2, 3])) { // Despachos_viajes
                foreach ($items as $item) {
                    
                    // LÓGICA EXCLUSIVA PARA DIESEL (1) - Intacta
                    if ($tipoPlanificacion == 1 && isset($item['cliente_id'])) { 
                        $cliente = Cliente::lockForUpdate()->findOrFail($item['cliente_id']);
                        
                        // Validar contra el DISPONIBLE (Saldo real)
                        if ($item['litros'] > $cliente->disponible) {
                            throw new Exception("Saldo insuficiente para {$cliente->nombre}. Disponible: {$cliente->disponible}L");
                        }

                        // Restar del disponible del cliente
                        $cliente->decrement('disponible', $item['litros']);

                        // Actualizar consumo GASCO
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
                        'cliente_id'          => $item['cliente_id'] ?? null,
                        'pedido_id'           => $item['pedido_id'] ?? null,
                        'litros'              => $item['litros'],
                        'muelle_atraque'      => $item['muelle'] ?? null,
                        'direccion_despacho'  => $item['direccion'] ?? null,
                        'buque_nombre_manual' => $item['buque_nombre'] ?? null,
                        'imo'                 => $item['imo'] ?? null,
                        'bandera'             => $item['bandera'] ?? null,
                        'observacion'         => $item['observacion'] ?? null,
                    ]);

                    // Actualizar Pedido si existe
                    if (isset($item['pedido_id'])) {
                        $this->pedidoRepo->update($item['pedido_id'], ['estado' => 'en_proceso']);
                    }
                }
            } elseif ($tipoPlanificacion == 4) {
                // LÓGICA DE COMPRAS
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

            // 4. AFECTAR INVENTARIO 
            // Solo descontamos inventario global si es venta propia (Diesel o MGO)
            if (in_array($tipoPlanificacion, [1, 2])) {
                $this->afectarInventarioGlobal($data['tipo_combustible_id'], $totalLitros, $viaje->id);
            } 
            // Nota: Las compras (4) sumarán al inventario al momento de recibirlas (otro módulo), no al planificarlas.

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