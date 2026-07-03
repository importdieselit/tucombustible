<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Almacen;
use App\Models\AlmacenEstructuraGrid;
use App\Models\Ubicacion;
use App\Models\Inventario;
use App\Models\InventarioStock;
use Illuminate\Support\Facades\DB;

class AlmacenLayoutController extends Controller
{
    public function disenar($id)
    {
        $almacen = Almacen::findOrFail($id);
        $estructuras = AlmacenEstructuraGrid::where('almacen_id', $id)
            ->get()
            ->keyBy(function($item) {
                return $item->coord_y . '-' . $item->coord_x;
            });

        return view('almacen.almacen_croquis', compact('almacen', 'estructuras'));
    }

public function guardarEstructuraDrag(Request $request)
{
    $request->validate([
        'almacen_id'       => 'required|exists:almacenes,id',
        'start_x'          => 'required|integer',
        'start_y'          => 'required|integer',
        'tipo_estructura'  => 'required|in:ESTANTE,GRANEL_LUBRICANTE,PISO_PALLET,PASILLO,TAMBORES_PIRAMIDE',
        'codigo_bloque'    => 'required|string|max:20',
        'cantidad_niveles' => 'required|integer|min:1',
        'largo_secciones'  => 'required|integer|min:1',
        'orientacion'      => 'required|in:H,V',
        'config_layout'    => 'nullable|array',
    ]);

    $almacen = Almacen::findOrFail($request->almacen_id);
    $largo = intval($request->largo_secciones);
    $startX = intval($request->start_x);
    $startY = intval($request->start_y);
    $codigoBloque = strtoupper($request->codigo_bloque);

    // --- 1. INTERVENCIÓN: HERRAMIENTA BORRADOR (PASILLO) ---
    if ($request->tipo_estructura === 'PASILLO') {
        $celdaObjetivo = AlmacenEstructuraGrid::where('almacen_id', $request->almacen_id)
            ->where('coord_x', $startX)
            ->where('coord_y', $startY)
            ->first();

        if ($celdaObjetivo) {
            $codigoABorrar = $celdaObjetivo->codigo_bloque;

            // VALIDACIÓN DE SEGURIDAD: Evitar Constraint 1451
            $ocupado = InventarioStock::whereHas('ubicacion', function ($query) use ($request, $codigoABorrar) {
                $query->select('id')
                      ->from('ubicaciones')
                      ->where('almacen_id', $request->almacen_id)
                      ->where('estante', $codigoABorrar);
            })->exists();

            if ($ocupado) {            
                return response()->json(['success' => false, 'error' => 'No puedes borrar este bloque. Contiene inventario físico.'], 422);
            }

            // Celdas a limpiar en la vista gráfica
            $celdasBorradas = AlmacenEstructuraGrid::where('almacen_id', $request->almacen_id)
                ->where('codigo_bloque', $codigoABorrar)
                ->get(['coord_x as x', 'coord_y as y']);

            DB::transaction(function () use ($request, $codigoABorrar) {
                Ubicacion::where('almacen_id', $request->almacen_id)->where('estante', $codigoABorrar)->delete();
                AlmacenEstructuraGrid::where('almacen_id', $request->almacen_id)->where('codigo_bloque', $codigoABorrar)->delete();
            });

            return response()->json([
                'success'         => true,
                'codigo'          => $codigoABorrar,
                'tipo'            => 'PASILLO',
                'celdas'          => [], 
                'celdas_borradas' => $celdasBorradas
            ]);
        }
        return response()->json(['success' => true, 'codigo'  => '', 'tipo' => 'PASILLO', 'celdas' => [], 'celdas_borradas' => []]);
    }

    // --- 2. Calcular celdas del nuevo destino ---
    $celdasAAfectar = [];
    for ($i = 0; $i < $largo; $i++) {
        $currentX = ($request->orientacion === 'H') ? ($startX + $i) : $startX;
        $currentY = ($request->orientacion === 'V') ? ($startY + $i) : $startY;

        if ($currentX > $almacen->total_columnas_grid || $currentY > $almacen->total_filas_grid) {
            return response()->json(['success' => false, 'error' => 'El estante se sale de los límites del almacén.'], 422);
        }
        $celdasAAfectar[] = ['x' => $currentX, 'y' => $currentY, 'posicion_idx' => ($i + 1)];
    }

    // --- 3. VALIDAR COLISIONES ---
    $colisionOtros = AlmacenEstructuraGrid::where('almacen_id', $request->almacen_id)
        ->where('codigo_bloque', '!=', $codigoBloque)
        ->where(function ($query) use ($celdasAAfectar) {
            foreach ($celdasAAfectar as $celda) {
                $query->orWhere(function ($q) use ($celda) {
                    $q->where('coord_x', $celda['x'])->where('coord_y', $celda['y']);
                });
            }
        })->exists();

    if ($colisionOtros) {
        return response()->json(['success' => false, 'error' => 'Operación cancelada: Una o más celdas destino están ocupadas.'], 422);
    }

    // --- 4. PREPARAR MATRIZ ASIMÉTRICA ---
    $configLayout = $request->config_layout;
    if (empty($configLayout)) {
        // Si no envía layout (arrastre simple), generamos uno simétrico por defecto
        $configLayout = [];
        for ($n = 1; $n <= $request->cantidad_niveles; $n++) {
            $configLayout[$n] = range(1, $largo);
        }
    }

    try {
        DB::transaction(function () use ($request, $celdasAAfectar, $codigoBloque, $configLayout) {

            /*
            |--------------------------------------------------------------------------
            | 1. SINCRONIZACIÓN DEL GRID VISUAL (Capa Física 2D)
            |--------------------------------------------------------------------------
            */
            $estructurasActuales = AlmacenEstructuraGrid::where('almacen_id', $request->almacen_id)
                ->where('codigo_bloque', $codigoBloque)
                ->orderBy('id')
                ->get();

            $nuevosGridIds = [];

            foreach ($celdasAAfectar as $index => $celda) {
                if (isset($estructurasActuales[$index])) {
                    // Actualizar grid existente (arrastre)
                    $estructura = $estructurasActuales[$index];
                    $estructura->update([
                        'coord_x'          => $celda['x'],
                        'coord_y'          => $celda['y'],
                        'cantidad_niveles' => count($configLayout),
                        'config_layout'    => $configLayout
                    ]);
                    $nuevosGridIds[$celda['posicion_idx']] = $estructura->id;
                } else {
                    // Crear grid nuevo (el estante se hizo más largo)
                    $estructura = AlmacenEstructuraGrid::create([
                        'almacen_id'         => $request->almacen_id,
                        'coord_x'            => $celda['x'],
                        'coord_y'            => $celda['y'],
                        'tipo_estructura'    => $request->tipo_estructura,
                        'codigo_bloque'      => $codigoBloque,
                        'cantidad_niveles'   => count($configLayout),
                        'cantidad_secciones' => 1,
                        'config_layout'      => $configLayout
                    ]);
                    $nuevosGridIds[$celda['posicion_idx']] = $estructura->id;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 2. SINCRONIZACIÓN DE UBICACIONES (Capa Lógica/Inventario)
            |--------------------------------------------------------------------------
            */
            // Extraer ubicaciones actuales y agruparlas por su "coordenada lógica"
            $ubicacionesActuales = Ubicacion::where('almacen_id', $request->almacen_id)
                ->where('estante', $codigoBloque)
                ->get()
                ->keyBy(function($item) {
                    return $item->nivel . '-' . $item->posicion;
                });

            $ubicacionesAprobadas = []; // Registro de celdas que se mantienen vivas

            foreach ($configLayout as $nivel => $posiciones) {
                foreach ($posiciones as $posIdx) {
                    $posIdx = (string)$posIdx;
                    $key = "{$nivel}-{$posIdx}";
                    $ubicacionesAprobadas[] = $key;

                    $gridId = $nuevosGridIds[$posIdx] ?? null;
                    if (!$gridId) continue;

                    $celdaGrid = collect($celdasAAfectar)->firstWhere('posicion_idx', $posIdx);
                    $pasilloStr = sprintf("P%02d", $celdaGrid['x']);

                    if ($ubicacionesActuales->has($key)) {
                        // A. CASO: COMBINACIÓN MANTENIDA / MOVIMIENTO (Solo actualiza punteros)
                        $ubicacionesActuales->get($key)->update([
                            'estructura_grid_id' => $gridId,
                            'pasillo'            => $pasilloStr
                        ]);
                    } else {
                        // B. CASO: SUBDIVISIÓN / EXPANSIÓN (Crea nueva ubicación física)
                        if ($request->tipo_estructura === 'ESTANTE') {
                            Ubicacion::create([
                                'almacen_id'         => $request->almacen_id,
                                'estructura_grid_id' => $gridId,
                                'codigo_ubicacion'   => sprintf("ALM%d-%s-N%d-P%s", $request->almacen_id, $codigoBloque, $nivel, $posIdx),
                                'pasillo'            => $pasilloStr,
                                'estante'            => $codigoBloque,
                                'nivel'              => (string)$nivel,
                                'posicion'           => $posIdx,
                                'tipo'               => 'ESTANDAR',
                                'esta_bloqueada'     => false
                            ]);
                        } else if ($request->tipo_estructura === 'GRANEL_LUBRICANTE') {
                            Ubicacion::create([
                                'almacen_id'         => $request->almacen_id,
                                'estructura_grid_id' => $gridId,
                                'codigo_ubicacion'   => "ALM{$request->almacen_id}-{$codigoBloque}-G{$posIdx}",
                                'pasillo'            => $pasilloStr,
                                'estante'            => $codigoBloque,
                                'nivel'              => '1',
                                'posicion'           => $posIdx,
                                'tipo'               => 'ZONA_GRANEL',
                                'esta_bloqueada'     => false
                            ]);
                        } else if ($request->tipo_estructura === 'PISO_PALLET') {
                            Ubicacion::create([
                                'almacen_id'         => $request->almacen_id,
                                'estructura_grid_id' => $gridId,
                                'codigo_ubicacion'   => "ALM{$request->almacen_id}-{$codigoBloque}-PISO-{$posIdx}",
                                'pasillo'            => $pasilloStr,
                                'estante'            => $codigoBloque,
                                'nivel'              => '1',
                                'posicion'           => $posIdx,
                                'tipo'               => 'PISO_PALLET',
                                'esta_bloqueada'     => false
                            ]);
                        } else if ($request->tipo_estructura === 'TAMBORES_PIRAMIDE') {
                            $codigoUbicacion = "ALM" . $request->almacen_id . "-" . strtoupper($request->codigo_bloque) . "-TAMBORES";

                            Ubicacion::create([
                                'almacen_id'         => $request->almacen_id,
                                'estructura_grid_id' => $estructura->id,
                                'codigo_ubicacion'   => $codigoUbicacion,
                                'pasillo'            => sprintf("P%02d", $request->coord_x),
                                'estante'            => strtoupper($request->codigo_bloque),
                                'nivel'              => '1',
                                'posicion'           => '1',
                                'tipo'               => 'PISO_PALLET', // Lógicamente se comporta igual que un pallet de piso
                                'esta_bloqueada'     => false
                            ]);
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3. LIMPIEZA DE RESIDUOS (Encogimiento o Fusión de celdas)
            |--------------------------------------------------------------------------
            */
            // A. Limpiar Ubicaciones que ya no existen en la nueva matriz $configLayout
            foreach ($ubicacionesActuales as $key => $ubi) {
                if (!in_array($key, $ubicacionesAprobadas)) {
                    // BLINDAJE: Evitar el constraint violation 1451
                    $tieneStock = InventarioStock::where('ubicacion_id', $ubi->id)->exists();
                    if ($tieneStock) {
                        throw new \Exception("Restricción: La posición Nivel {$ubi->nivel} - Sección {$ubi->posicion} no puede combinarse ni eliminarse porque actualmente almacena inventario físico.");
                    }
                    $ubi->delete();
                }
            }

            // B. Limpiar Grids visuales sobrantes si el estante se hizo más corto
            if (count($estructurasActuales) > count($celdasAAfectar)) {
                for ($i = count($celdasAAfectar); $i < count($estructurasActuales); $i++) {
                    $estructurasActuales[$i]->delete();
                }
            }
        });

        return response()->json([
            'success'         => true,
            'codigo'          => $codigoBloque,
            'tipo'            => $request->tipo_estructura,
            'celdas'          => $celdasAAfectar,
            'celdas_borradas' => [] 
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
   public function inspeccionarEstante(Request $request)
{
    $request->validate([
        'almacen_id' => 'required|integer',
        'coord_x'    => 'required|integer',
        'coord_y'    => 'required|integer',
    ]);

    $estructura = AlmacenEstructuraGrid::where('almacen_id', $request->almacen_id)
        ->where('coord_x', $request->coord_x)
        ->where('coord_y', $request->coord_y)
        ->first();

    if (!$estructura || $estructura->tipo_estructura === 'PASILLO') {
        return response()->json(['success' => false, 'error' => 'Posición vacía o pasillo.']);
    }
   

    $codigoBloque = $estructura->codigo_bloque;

    $ubicaciones = Ubicacion::where('almacen_id', $request->almacen_id)
        ->where('estante', $codigoBloque)
        ->with(['inventarioStock.item'])
        ->get();

    $niveles = $ubicaciones->pluck('nivel')->unique()->map(fn($n) => (int)$n)->sortDesc()->values()->all();
    $posiciones = $ubicaciones->pluck('posicion')->unique()->map(fn($p) => (int)$p)->sort()->values()->all();

    // 4. Estructurar matriz indexada por "nivel-posicion" agrupando subdivisiones
    $matrizData = [];
    foreach ($ubicaciones as $ubi) {
        $llaveMatriz = "{$ubi->nivel}-{$ubi->posicion}";

        // Inicializamos el contenedor de la coordenada si no existe
        if (!isset($matrizData[$llaveMatriz])) {
            $matrizData[$llaveMatriz] = [
                'colspan' => $ubi->colspan ?? 1,
                'slots'   => [] // Aquí vivirá 1 slot normal, o N slots si está subdividido
            ];
        }

        $stock = $ubi->inventarioStock; 
        $totalItems = $stock ? $stock->cantidad_actual : 0;
        
        $detallesInventario = null;
        if ($stock) {
            $detallesInventario = [
                'producto' => $stock->item->descripcion ?? 'Item sin nombre',
                'sku'      => $stock->item->codigo ?? 'S/S',
                'cantidad' => $stock->cantidad_actual,
                'capacidad_asignada' => $stock->capacidad_asignada,
                'lote'     => $stock->lote ?? 'N/A'
            ];
        }

        // Insertamos el sub-slot en el contenedor
        $matrizData[$llaveMatriz]['slots'][] = [
            'id'              => $ubi->id,
            'codigo_completo' => $ubi->codigo_ubicacion,
            'subposicion'     => $ubi->subposicion ?? '',
            'ocupado'         => $totalItems > 0,
            'total_articulos' => $totalItems,
            'inventario'      => $detallesInventario
        ];
    }

    return response()->json([
        'success'    => true,
        'estante'    => $codigoBloque,
        'tipo'       => $estructura->tipo_estructura,
        'niveles'    => $niveles,
        'posiciones' => $posiciones,
        'matriz'     => $matrizData
    ]);
}


    public function guardarEstructura(Request $request)
    {
        $request->validate([
            'almacen_id'       => 'required|exists:almacenes,id',
            'coord_x'          => 'required|integer',
            'coord_y'          => 'required|integer',
            'tipo_estructura'  => 'required|in:ESTANTE,GRANEL_LUBRICANTE,PISO_PALLET,PASILLO,TAMBORES_PIRAMIDE',
            'codigo_bloque'    => 'required|string|max:20',
            'cantidad_niveles' => 'required_if:tipo_estructura,ESTANTE|integer|min:1',
            'cantidad_secciones'=> 'required_if:tipo_estructura,ESTANTE|integer|min:1',
        ]);

        try {
            $resultado = DB::transaction(function () use ($request) {
                
                // 1. Registrar o actualizar la celda en el croquis gráfico
                $estructura = AlmacenEstructuraGrid::updateOrCreate(
                    [
                        'almacen_id' => $request->almacen_id,
                        'coord_x'    => $request->coord_x,
                        'coord_y'    => $request->coord_y
                    ],
                    [
                        'tipo_estructura'   => $request->tipo_estructura,
                        'codigo_bloque'     => strtoupper($request->codigo_bloque),
                        'cantidad_niveles'  => $request->tipo_estructura === 'ESTANTE' ? $request->cantidad_niveles : 1,
                        'cantidad_secciones'=> $request->tipo_estructura === 'ESTANTE' ? $request->cantidad_secciones : 1,
                    ]
                );

                // Limpiar registros físicos antiguos vinculados a esta celda del mapa
                Ubicacion::where('estructura_grid_id', $estructura->id)->delete();

                // Si se selecciona PASILLO, removemos la estructura del croquis físico
                if ($request->tipo_estructura === 'PASILLO') {
                    $estructura->delete();
                    return null;
                }

                // 2. Mapear e insertar en tu tabla original de 'ubicaciones'
                if ($request->tipo_estructura === 'ESTANTE') {
                    for ($n = 1; $n <= $request->cantidad_niveles; $n++) {
                        for ($s = 1; $s <= $request->cantidad_secciones; $s++) {
                            
                            // Formato de código adaptado a tu estándar (Ej: ALM1-EST01-N1-P2)
                            $codigoUbicacion = sprintf("ALM%d-%s-N%d-P%d", 
                                $request->almacen_id, 
                                strtoupper($request->codigo_bloque), 
                                $n, 
                                $s
                            );

                            Ubicacion::create([
                                'almacen_id'         => $request->almacen_id,
                                'estructura_grid_id' => $estructura->id,
                                'codigo_ubicacion'   => $codigoUbicacion, // Tu campo string unique
                                'pasillo'            => sprintf("P%02d", $request->coord_x), // Deducción automática por columna
                                'estante'            => strtoupper($request->codigo_bloque),
                                'nivel'              => (string)$n,
                                'posicion'           => (string)$s,
                                'tipo'               => 'ESTANDAR', // Tu enum existente
                                'esta_bloqueada'     => false
                            ]);
                        }
                    }
                } else if ($request->tipo_estructura === 'GRANEL_LUBRICANTE') {
                    $codigoUbicacion = "ALM" . $request->almacen_id . "-" . strtoupper($request->codigo_bloque) . "-GRANEL";

                    Ubicacion::create([
                        'almacen_id'         => $request->almacen_id,
                        'estructura_grid_id' => $estructura->id,
                        'codigo_ubicacion'   => $codigoUbicacion,
                        'pasillo'            => sprintf("P%02d", $request->coord_x),
                        'estante'            => strtoupper($request->codigo_bloque),
                        'nivel'              => '1',
                        'posicion'           => '1',
                        'tipo'               => 'ZONA_GRANEL', // Tu enum existente
                        'esta_bloqueada'     => false
                    ]);
                }else if ($request->tipo_estructura === 'PISO_PALLET') {
                    $codigoUbicacion = "ALM" . $request->almacen_id . "-" . strtoupper($request->codigo_bloque) . "-PISO";

                    Ubicacion::create([
                        'almacen_id'         => $request->almacen_id,
                        'estructura_grid_id' => $estructura->id,
                        'codigo_ubicacion'   => $codigoUbicacion,
                        'pasillo'            => sprintf("P%02d", $request->coord_x),
                        'estante'            => strtoupper($request->codigo_bloque),
                        'nivel'              => '1',
                        'posicion'           => '1',
                        'tipo'               => 'PISO_PALLET', // Asegúrate de tener este tipo en tu Enum
                        'esta_bloqueada'     => false
                    ]);
                } else if ($request->tipo_estructura === 'TAMBORES_PIRAMIDE') {
                    $codigoUbicacion = "ALM" . $request->almacen_id . "-" . strtoupper($request->codigo_bloque) . "-TAMBORES";

                    Ubicacion::create([
                        'almacen_id'         => $request->almacen_id,
                        'estructura_grid_id' => $estructura->id,
                        'codigo_ubicacion'   => $codigoUbicacion,
                        'pasillo'            => sprintf("P%02d", $request->coord_x),
                        'estante'            => strtoupper($request->codigo_bloque),
                        'nivel'              => '1',
                        'posicion'           => '1',
                        'tipo'               => 'PISO_PALLET', // Lógicamente se comporta igual que un pallet de piso
                        'esta_bloqueada'     => false
                    ]);
                }

                return $estructura;
            });

            return response()->json([
                'success' => true, 
                'message' => 'Estructura mapeada con éxito en ubicaciones.',
                'html_clase' => $resultado ? $this->getClaseColor($resultado->tipo_estructura) : 'bg-white text-muted',
                'texto' => $resultado ? $resultado->codigo_bloque : '+ Vacío'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getClaseColor($tipo) {
        return match($tipo) {
            'ESTANTE'           => 'bg-primary text-white',
            'GRANEL_LUBRICANTE' => 'bg-warning text-dark',
            'PISO_PALLET'       => 'bg-info text-white', // Nuevo estilo 2D
            'TAMBORES_PIRAMIDE' => 'bg-secondary text-white',
            default             => 'bg-white text-muted'
        };
    }

    public function redimensionarGrid(Request $request)
    {
        $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'filas'      => 'required|integer|min:1|max:100', // Límite por sanidad de pantalla
            'columnas'   => 'required|integer|min:1|max:100',
        ]);

        $almacen = Almacen::findOrFail($request->almacen_id);
        $nuevasFilas = (int)$request->filas;
        $nuevasColumnas = (int)$request->columnas;

        // BLINDAJE GEOMÉTRICO: 
        // Buscar si hay bloques mapeados cuyas coordenadas quedarían por fuera del nuevo tamaño
        $estructurasFuera = AlmacenEstructuraGrid::where('almacen_id', $almacen->id)
            ->where(function ($query) use ($nuevasFilas, $nuevasColumnas) {
                $query->where('coord_x', '>', $nuevasColumnas)
                    ->orWhere('coord_y', '>', $nuevasFilas);
            })->count();

        // Si detectamos al menos 1 celda ocupada en el área que se va a "cortar"
        if ($estructurasFuera > 0) {
            return response()->json([
                'success' => false,
                'error'   => "Operación Abortada: Hay {$estructurasFuera} celda(s) ocupada(s) que quedarían fuera del plano. Por favor, reubica o borra los estantes de esas áreas antes de reducir el tamaño."
            ], 422);
        }

        // Si pasó la validación (está ampliando, o está achicando pero el área recortada está vacía)
        $almacen->total_filas_grid = $nuevasFilas;
        $almacen->total_columnas_grid = $nuevasColumnas;
        $almacen->save();

        return response()->json([
            'success' => true,
            'message' => 'Dimensiones de planta actualizadas.'
        ]);
    }

   public function vista3D($id)
{
    $almacen = Almacen::findOrFail($id);
    
    $estructuras = AlmacenEstructuraGrid::where('almacen_id', $id)->get();
    
    $ubicaciones = Ubicacion::where('almacen_id', $id)
        ->with(['inventarioStock.item'])
        ->get()
        ->groupBy('estructura_grid_id');

    $mapa3d = $estructuras->map(function($e) use ($ubicaciones) {
        $ubis = $ubicaciones->get($e->id) ?? collect();
        
        $inventarioEstado = [];

        foreach ($ubis as $ubi) {
            $cantidadTotal = $ubi->inventarioStock->first()->cantidad_actual ?? null;
            $capacidad = $ubi->inventarioStock->first()->capacidad_asignada ?? null;
            $primerArticulo = $ubi->inventarioStock->first();
            $estaOcupado = $cantidadTotal > 0;

            $inventarioEstado[] = [
                'codigo_completo' => $ubi->codigo_ubicacion, // IMPORTANTE: Permite ordenar las subdivisiones
                'nivel'    => intval($ubi->nivel),
                'posicion' => intval($ubi->posicion),
                'ocupado'  => $estaOcupado,
                'sku'      => $estaOcupado ? $primerArticulo->item->codigo : '',
                'producto' => $estaOcupado ? $primerArticulo->item->descripcion : '',
                'stock'    => $estaOcupado ? $cantidadTotal : 0,
                'capacidad' => $estaOcupado ? $capacidad : 0
            ];
        }

        return [
            'x'          => intval($e->coord_x),
            'z'          => intval($e->coord_y), 
            'tipo'       => $e->tipo_estructura,
            'codigo'     => $e->codigo_bloque,
            'niveles'    => intval($e->cantidad_niveles),
            'inventario' => $inventarioEstado
        ];
    });

    return view('almacen.almacen_3d', compact('almacen', 'mapa3d'));
}

    public function buscarItemsAAsignar(Request $request)
    {
        $term = $request->termino;
        // Buscamos ítems en el maestro que tengan stock disponible
        $items = Inventario::where('existencia', '>', 0)
            ->where(function($q) use ($term) {
                $q->where('codigo', 'LIKE', "%{$term}%")
                  ->orWhere('descripcion', 'LIKE', "%{$term}%");
            })
            ->take(20)
            ->get();

        return response()->json(['success' => true, 'items' => $items]);
    }

    public function asignarItemUbicacion(Request $request)
    {
        $request->validate([
            'ubicacion_id' => 'required|exists:ubicaciones,id',
            'inventario_id' => 'required|exists:inventario,id',
            'cantidad' => 'required|numeric|min:0.1'
        ]);

        DB::beginTransaction();
        try {
            $ubicacion = Ubicacion::findOrFail($request->ubicacion_id);
            $itemMaestro = Inventario::findOrFail($request->inventario_id);

            if ($itemMaestro->existencia < $request->cantidad) {
                throw new \Exception("Stock insuficiente en el maestro. Disponible: {$itemMaestro->existencia}");
            }

            // Descontar del maestro global
            $itemMaestro->existencia -= $request->cantidad;
            $itemMaestro->save();

            // Buscar si el ítem ya existe en ese slot para sumar, o crear un nuevo registro de existencia
            $existenciaEnSlot = InventarioStock:: // Ajusta este nombre según tu tabla pivote de ubicacion-inventario
                where('ubicacion_id', $ubicacion->id)
                ->where('inventario_id', $itemMaestro->id)
                ->first();

            if ($existenciaEnSlot) {
                $existenciaEnSlot->cantidad_actual += $request->cantidad;
                $existenciaEnSlot->save();
                $cantidadActual = $existenciaEnSlot->cantidad_actual;
                $capacidad = $existenciaEnSlot->capacidad_asignada;
            } else {
                // NUEVO REGISTRO: Aquí capturamos el "Techo"
                $nuevo = InventarioStock::create([
                    'ubicacion_id' => $ubicacion->id,
                    'inventario_id' => $itemMaestro->id,
                    'cantidad_actual' => $request->cantidad,
                    'capacidad_asignada' => $request->cantidad, // <-- ESTE ES EL NUEVO CAMPO
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $cantidadActual = $nuevo->cantidad_actual;
                $capacidad = $nuevo->capacidad_asignada;
            }

            // Si capacidad es 100 y tienes 50, uso es 50%.
            $porcentajeUso = ($capacidad > 0) ? ($cantidadActual / $capacidad) * 100 : 0;
            
            $alerta = null;
            if ($porcentajeUso >= 90) $alerta = 'CRITICO';
            elseif ($porcentajeUso >= 75) $alerta = 'ADVERTENCIA';

            DB::commit();
            return response()->json([
                'success' => true, 
                'porcentaje' => round($porcentajeUso, 1),
                'alerta' => $alerta
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function reubicarItemUbicacion(Request $request)
    {
        // Mueve todo el contenido de una ubicación específica a otra
        DB::beginTransaction();
        try {
            $ubicacionOrigen = Ubicacion::findOrFail($request->origen_id);
            $ubicacionDestino = Ubicacion::findOrFail($request->destino_id);

            // Trasladar todos los registros de existencias
            InventarioStock::where('ubicacion_id', $ubicacionOrigen->id)
                ->update(['ubicacion_id' => $ubicacionDestino->id]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function vaciarItemUbicacion(Request $request)
    {
        DB::beginTransaction();
        try {
            $ubicacion = Ubicacion::findOrFail($request->ubicacion_id);
            
            $existencias = InventarioStock::where('ubicacion_id', $ubicacion->id)->get()->first();
       
                $item = Inventario::findOrFail($existencias->inventario_id);
                $item->existencia += $existencias->cantidad_actual;
                $item->save();
      

            // Limpiar el slot
            InventarioStock::where('ubicacion_id', $ubicacion->id)->delete();

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function actualizarPosicion3D(Request $request)
    {
        $request->validate([
            'estructura_id' => 'required|exists:almacen_estructuras_grid,id',
            'coord_x'       => 'required|integer|min:0',
            'coord_y'       => 'required|integer|min:0',
            'rotacion'      => 'required|numeric'
        ]);

        $estructura = AlmacenEstructuraGrid::findOrFail($request->estructura_id);
        
        // Actualizar coordenadas y el ángulo de rotación Y en radianes
        $estructura->update([
            'coord_x' => $request->coord_x,
            'coord_y' => $request->coord_y,
            'rotacion_radianes' => $request->rotacion 
        ]);

        return response()->json(['success' => true, 'message' => 'Estructura reposicionada con éxito.']);
    }
    
    public function subdividirSlot(Request $request)
{
    return DB::transaction(function () use ($request) {
        $ubicacionOriginal = Ubicacion::findOrFail($request->ubicacion_id);
        $cantidad = (int) $request->cantidad_divisiones;

        // Si ya hay stock, NO borres el original. 
        // Solo crea las nuevas y marca el original como A.
        $letras = ['A', 'B', 'C', 'D', 'E'];
        
        // Renombrar original a A si no lo es
        if (strpos($ubicacionOriginal->codigo_ubicacion, '-A') === false) {
             $ubicacionOriginal->update(['codigo_ubicacion' => $ubicacionOriginal->codigo_ubicacion . '-A']);
        }

        for ($i = 1; $i < $cantidad; $i++) {
            Ubicacion::create([
                'almacen_id' => $ubicacionOriginal->almacen_id,
                'estructura_grid_id' => $ubicacionOriginal->estructura_grid_id,
                'codigo_ubicacion' => str_replace('-A', '-' . $letras[$i], $ubicacionOriginal->codigo_ubicacion),
                'pasillo' => $ubicacionOriginal->pasillo,
                'estante' => $ubicacionOriginal->estante,
                'nivel' => $ubicacionOriginal->nivel,
                'posicion' => $ubicacionOriginal->posicion,
                'tipo' => $ubicacionOriginal->tipo,
                'capacidad_maxima_kg' => $ubicacionOriginal->capacidad_maxima_kg / $cantidad
            ]);
        }
        return response()->json(['success' => true]);
    });
}

   public function combinarSlots(Request $request)
{
    return DB::transaction(function () use ($request) {
        $ubicacionBase = Ubicacion::findOrFail($request->ubicacion_id);

        // 1. Identificar todo el bloque de sub-celdas
        $queryBloque = Ubicacion::where('estructura_grid_id', $ubicacionBase->estructura_grid_id)
            ->where('nivel', $ubicacionBase->nivel)
            ->where('posicion', $ubicacionBase->posicion);

        $slots = $queryBloque->get();
        
        // 2. MIGRACIÓN: Mover el stock de todas las sub-celdas a la "base"
        // Si hay stock en slots que vamos a borrar, lo movemos al slot que sobrevive
        InventarioStock::whereIn('ubicacion_id', $slots->pluck('id'))
            ->where('ubicacion_id', '!=', $ubicacionBase->id)
            ->update(['ubicacion_id' => $ubicacionBase->id]);

        // 3. Borrar el resto (ahora es seguro porque ya no hay stock asociado)
        $queryBloque->where('id', '!=', $ubicacionBase->id)->delete();
        
        // 4. Limpiar nombre (opcional)
        $ubicacionBase->update(['codigo_ubicacion' => str_replace(['-A','-B','-C','-D','-E'], '', $ubicacionBase->codigo_ubicacion)]);

        return response()->json(['success' => true]);
    });
}
    
}