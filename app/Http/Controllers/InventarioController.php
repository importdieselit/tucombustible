<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Almacen;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\InventarioCompra;    
use App\Models\InventarioAsociado;
use App\Models\Ubicacion;
use App\Models\InventarioStock;
use App\Models\AlmacenEstructuraGrid;
use App\Models\SuministroCompra;
use App\Models\SuministroCompraDetalle;
use App\Models\Ventas;
use App\Models\VentasDetalle;
use App\Models\Orden;
use App\Models\InventarioDespacho;
use App\Models\MovimientoInventario;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class InventarioController extends BaseController
{
    /**
     * Muestra el dashboard principal del inventario.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $totalItems = Inventario::count();
        $totalCantidad = Inventario::sum('existencia');
        $valorTotal = Inventario::sum(DB::raw('existencia * costo'));
         // 1. Métricas Principales (KPIs)
        
        $itemsBajoStock = Inventario::whereColumn('existencia', '<=', 'existencia_minima')
            ->where('estatus', 1)->where('venta',0)
            ->count();

        // Asumimos que los despachos son movimientos de tipo 'SALIDA' con estatus 'PENDIENTE'
        $despachosPendientes = InventarioDespacho::where('estatus', 2)
            ->count();

        $comprasPendientes = SuministroCompra::where('estatus', 2)
            ->count();
        
        $despachosPendientes+=$comprasPendientes;


        $movimientosRecientes = InventarioDespacho::where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        // 2. Alertas de Reposición (Stock Crítico)
        $stockCritico = Inventario::whereColumn('existencia', '<=', 'existencia_minima')
            ->select('descripcion', 'existencia as cantidad', 'existencia_minima')
            ->orderBy(DB::raw('existencia / existencia_minima'), 'asc') // Los más urgentes primero
            ->limit(5)
            ->get();

        // 3. Data para Gráfica de Entradas vs Salidas (Últimos 6 meses)
        $meses = [];
        $entradasData = [];
        $salidasData = [];

        for ($i = 5; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $meses[] = strtoupper($mes->translatedFormat('M'));
            
            $entradasData[] = SuministroCompraDetalle::whereMonth('created_at', $mes->month)
                ->whereYear('created_at', $mes->year)
                ->sum('cantidad_aprobada');

            $salidasData[] = InventarioDespacho::whereMonth('created_at', $mes->month)
                ->whereYear('created_at', $mes->year)
                ->sum('cantidad_despachada');
            $salidasCompras=SuministroCompraDetalle::where('estatus', 1)->whereMonth('created_at', $mes->month)
                ->whereYear('created_at', $mes->year)
                ->sum('cantidad_aprobada');
            $salidasData[] += $salidasCompras;
        }

        // 4. Inversión por Categoría (Doughnut Chart)
        // 4.1. Calculamos el valor global primero para los porcentajes
        $valorTotal = Inventario::selectRaw('SUM(existencia * costo) as total')->value('total') ?? 0;

        // 4.2. Agrupamos por el campo 'grupo' y sumamos la inversión de cada uno
        $categoriasData = Inventario::select('grupo')
            ->selectRaw('SUM(existencia * costo) as subtotal')
           // ->where('venta',0)
            ->groupBy('grupo')
            ->get()
            ->map(function ($item, $index) use ($valorTotal) {
                return [
                    'nombre'     => strtoupper($item->grupo ?? 'SIN GRUPO'),
                    'valor'      => $item->subtotal,
                    'porcentaje' => $valorTotal > 0 ? ($item->subtotal / $valorTotal) * 100 : 0,
                    'color'      => $this->generarColorCorporativo($index)
                ];
            });
            

        return view('inventario.index', [
            'valorTotal' => $valorTotal,
            'itemsBajoStock' => $itemsBajoStock,
            'despachosPendientes' => $despachosPendientes,
            'movimientosRecientes' => $movimientosRecientes,
            'stockCritico' => $stockCritico,
            'mesesMovimientos' => $meses,
            'entradasData' => $entradasData,
            'salidasData' => $salidasData,
            'categorias' => $categoriasData,
            'categoriasNombres' => $categoriasData->pluck('nombre'),
            'categoriasValores' => $categoriasData->pluck('valor')
        ]);
    }


    /**
     * Muestra el formulario para crear un nuevo item.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $item = new Inventario();
        $almacenes = Almacen::all();
        // Nota: Los datos de Marca, Modelo, Condicion, etc. deben ser cargados aquí
        return view('inventario.form', compact('item', 'almacenes'));
    }

   public function applyBusinessFilters(Builder $query)
    {
        $query->with(['almacen','ubicaciones','modelosAsociados','equivalentes']);
        // 1. Usamos el helper global request() en lugar de inyectar la clase en los argumentos
        if (request()->filled('id_almacen')) {
            $query->where('id_almacen', request('id_almacen'));
        }

        if (request()->filled('codigo')) {
            $query->where('codigo', 'like', '%' . request('codigo') . '%');
        }

        if (request()->filled('descripcion')) {
            $query->where('descripcion', 'like', '%' . request('descripcion') . '%');
        }

        // 2. RETORNAMOS el mismo query modificado para que el BaseController 
        // pueda seguir aplicándole los filtros de seguridad de 'id_cliente'
        return $query;
    }

    /**
     * Almacena un nuevo item en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //'id_usuario' => 'required|exists:users,id',
            'id_almacen' => 'required|exists:almacenes,id',
            'codigo' => 'required|string|max:50|unique:inventario',
            'descripcion' => 'required|string|max:200'
            ]);
            

        if ($validator->fails()) {
            return redirect()->route('inventario.create')
                ->withErrors($validator)
                ->withInput();
        }

        // Se asigna el usuario autenticado (asumiendo que hay un sistema de autenticación)
        $request->merge(['id_usuario' => Auth::id()]);
        Inventario::create($request->all());

        return redirect()->route('inventario.list')
            ->with('success', 'Item de inventario creado exitosamente.');
    }

    /**
     * Muestra un item específico.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // 1. Cargar el ítem con sus relaciones de stock y ubicación
        $item = Inventario::with([
            'almacen', 
            'modelosAsociados', 
            'equivalentes',
            'stocks.ubicacion.estructuraGrid' // Importante para el mapa
        ])->findOrFail($id);
        
        // --- CÁLCULOS DE ROTACIÓN Y GRÁFICA (Como lo teníamos antes) ---
        $salidasUltimoMes = InventarioDespacho::where('inventario_id', $id)
            ->where('estatus', '!=', 'Anulado')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('cantidad_despachada');
            
        $promedioDiarioDespacho = $salidasUltimoMes / 30;
        $item->tasa_rotacion = $item->existencia > 0 ? number_format($salidasUltimoMes / $item->existencia, 1) : '0.0';
        $item->promedio_duracion = $promedioDiarioDespacho > 0 ? round($item->existencia / $promedioDiarioDespacho) : '∞';

        $despachos = InventarioDespacho::with(['usuarioDespacha', 'ordenTrabajo'])
            ->where('inventario_id', $id)->where('estatus', '!=', 'Anulado')
            ->orderBy('created_at', 'desc')->limit(30)->get();

        $movimientos = $despachos->map(function($mov) {
            return (object)[
                'fecha' => $mov->created_at->format('d/m/Y H:i'),
                'tipo' => 'Salida',
                'cantidad' => -abs($mov->cantidad_despachada),
                'documento' => 'OT-' . str_pad($mov->orden_trabajo_id, 5, '0', STR_PAD_LEFT),
                'usuario' => $mov->usuarioDespacha->name ?? 'Operador'
            ];
        });
            
        // Gráfica de últimos 15 días
        $stockSimulado = $item->existencia;
        $stockDiario = [];
        for ($i = 0; $i <= 14; $i++) {
            $fechaCarbon = Carbon::now('America/Caracas')->subDays($i);
            $salidasDelDia = InventarioDespacho::where('inventario_id', $id)->where('estatus', '!=', 'Anulado')
                ->whereDate('created_at', $fechaCarbon->format('Y-m-d'))->sum('cantidad_despachada');
            $stockDiario[$fechaCarbon->format('d-M')] = $stockSimulado;
            $stockSimulado += $salidasDelDia; 
        }
        $graficaFechas = array_reverse(array_keys($stockDiario));
        $graficaStock = array_reverse(array_values($stockDiario));

        $equivalentes = $item->equivalentes; 
        $vehiculos = $item->modelosAsociados->map(function($asociado) {
            return (object)[
                'placa' => $asociado->vehiculo->placa ?? $asociado->referencia ?? 'N/A',
                'modelo' => $asociado->vehiculo->modelo ?? 'N/A',
                'departamento' => $asociado->vehiculo->departamento ?? 'General'
            ];
        });

        // --- NUEVO: LÓGICA DE MAPEO Y UBICACIÓN ---
        $ubicaciones = $item->ubicaciones; // Cargamos la colección completa de ubicaciones
        $ubicacionesIds = $ubicaciones->pluck('id')->toArray(); // Array plano de IDs: [12, 15, 23]

        $ubicacionPrincipal = $ubicaciones->first(); 
        $almacen = null;
        $gridActivo = null; 
        $estructuras = collect();
        $cacheUbicacionesEstante = collect();
        $ubicacion_texto = 'No Asignada';
        
        if ($ubicacionPrincipal) {
            $gridActivo = $ubicacionPrincipal->estructuraGrid; 

            if ($gridActivo) {
                $almacen = $gridActivo->almacen;
                
                // Aprovechamos el accessor dinámico por comas que creamos en el paso anterior
                $ubicacion_texto = $item->ubicaciones_texto; 

                // CROQUIS 1: Mapeo de toda la planta del almacén
                $estructuras = \App\Models\AlmacenEstructuraGrid::where('almacen_id', $almacen->id)
                    ->get()
                    ->keyBy(function($est) {
                        return $est->coord_y . '-' . $est->coord_x;
                    });

                // CROQUIS 2: Mapeo de la elevación (Todos los slots de ESTE estante)
                $cacheUbicacionesEstante = \App\Models\Ubicacion::where('estructura_grid_id', $gridActivo->id)
                    ->with(['inventarioItems','inventarioStock']) 
                    ->get()
                    ->groupBy(function($ubicacion) {
                        return $ubicacion->nivel . '-' . $ubicacion->posicion;
                    });
            }
        }
        
        return view('inventario.show', compact(
            'item', 'movimientos', 'graficaFechas', 'graficaStock', 'equivalentes', 'vehiculos',
            'almacen', 'estructuras', 'ubicacionPrincipal', 'ubicacion_texto','gridActivo', 
            'cacheUbicacionesEstante','ubicacionesIds'
        ));
    }
    /**
     * Muestra el formulario para editar un item existente.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $item = Inventario::find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }
        $almacenes = Almacen::all();
        // Nota: Los datos de Marca, Modelo, Condicion, etc. deben ser cargados aquí
        return view('inventario.form', compact('item', 'almacenes'));
    }

     private function generarColorCorporativo($id)
    {
        $colores = ['#002855', '#ff6600', '#6c757d', '#adb5bd', '#004085'];
        return $colores[$id % count($colores)];
    }

    /**
     * Actualiza un item existente en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $item = Inventario::find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }

        $validator = Validator::make($request->all(), [
            'id_usuario' => 'sometimes|required|exists:users,id',

            'id_almacen' => 'sometimes|required|exists:almacenes,id',
            'codigo' => 'sometimes|required|string|max:50|unique:inventario,codigo,' . $id,
            'descripcion' => 'sometimes|required|string|max:200',
            'existencia' => 'sometimes|required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return redirect()->route('inventario.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $item->update($request->all());

        return redirect()->route('inventario.list')
            ->with('success', 'Item de inventario actualizado exitosamente.');
    }

    /**
     * Elimina un item de la base de datos.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $item = Inventario::find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }

        $item->delete();

        return redirect()->route('inventario.list')
            ->with('success', 'Item de inventario eliminado exitosamente.');
    }

    public function getDetallesArticulo($id)
    {
        $articulo = Inventario::findOrFail($id);

        // 1. Stock global en el maestro que no se ha amarrado a ningún slot físico aún
        $stockGeneral = $articulo->existencia ?? 0;

        // 2. Slots donde ya existe este artículo (Relación pivote o de inventario físico)
        // Cambia los nombres de relaciones según tus modelos estructurados
        $slotsPoseidos = DB::table('articulo_slot as as')
            ->join('slots_almacen as s', 'as.slot_id', '=', 's.id')
            ->where('as.articulo_id', $id)
            ->select('as.slot_id', 's.codigo_posicion', 'as.cantidad')
            ->get();

        // Suma de lo que está repartido en los racks
        $stockEnSlots = $slotsPoseidos->sum('cantidad'); 

        // 3. Catálogo completo de ubicaciones para pintar los selectores de destino
        $todosLosSlots = DB::table('slots_almacen')->select('id', 'codigo_posicion')->get();

        return response()->json([
            'success' => true,
            'articulo' => $articulo,
            'stock_general' => $stockGeneral,
            'stock_total' => $stockGeneral + $stockEnSlots,
            'slots_poseidos' => $slotsPoseidos,
            'todos_los_slots' => $todosLosSlots
        ]);
    }

    public function createEntry($id){
       
        $item= Inventario::find($id);
        $locations = InventarioStock::where('inventario_id',$id)->get();
        dd($item);  
        return view('inventario.entries', compact(['id', 'item','locations']));
    }
    
    public function entry(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:inventario,id',
            'slot_id'     => 'required|exists:inventario_stock,id',
            'cantidad'    => 'required|numeric|gt:0',
            'compra_id'   => 'nullable|exists:compras,id',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Obtener o crear el registro de stock actual en ese slot
            $stockActual = InventarioStock::firstOrCreate(
                ['inventario_id' => $request->item_id, 'ubicacion_id' => $request->slot_id],
                ['cantidad_actual' => 0]
            );

            if($stockActual->candidad_asignada ==0 || is_null($stockActual->cantidad_asignada) ){
                $stockActual->cantidad_asignada = $request->cantidad;
            }
            $stockAnterior = $stockActual->cantidad_actual;
            $stockNuevo = $stockAnterior + $request->cantidad;

            // 2. Actualizar stock en el slot
            $stockActual->cantidad_actual = $stockNuevo;
            $stockActual->save();

            // 3. Registrar en el Kardex (Movimientos)
            MovimientoInventario::create([
                'articulo_id'    => $request->articulo_id,
                'slot_id'        => $request->slot_id,
                'tipo'           => 'ENTRADA',
                'cantidad'       => $request->cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo'    => $stockNuevo,
                'compra_id'      => $request->compra_id,
                'motivo'         => 'Entrada por orden de compra #' . $request->compra_id,
                'user_id'        => Auth::id() ?? 1 // Fallback para pruebas
            ]);

        return response()->json(['success' => true, 'message' => 'Entrada registrada exitosamente.']);
    
        });
    }

    public function adjustment()
    {
    }

    public function ventaCreate()
    {
        $items = Inventario::where('venta',true)->get();
        $clientes   = Cliente::all();
        return view('inventario.venta', compact('items', 'clientes'));
    }

    public function ventaStore(Request $request)
    {
        // 1. Validación de entrada
        $request->validate([
            'id_cliente' => 'required',
            'items' => 'required|array|min:1',
        ]);

        try {
              $idCliente = $request->id_cliente;

                // 2. Lógica de registro rápido de cliente ("NUEVO" u "OTROS")
                if ($idCliente === 'NUEVO' || $idCliente === 'OTROS') {
                    $cliente = Cliente::create([
                        'rif' => $request->nuevo_rif,
                        'nombre' => $request->nuevo_nombre,
                        'correo' => $request->nuevo_correo,
                        'telefono' => $request->nuevo_telefono,
                    ]);
                    $idCliente = $cliente->id;
                }

                // 3. Crear la cabecera de la venta
                $venta = Ventas::create([
                    'nro_venta' => 'V-' . strtoupper(uniqid()), // Genera un correlativo único
                    'nro_profit' => $request->nro_profit,
                    'id_cliente' => $idCliente,
                    'fecha' => now(),
                    'total_venta' => 0, // Se actualizará al final
                    'observaciones' => $request->observaciones,
                ]);
                Log::info('venta: '.$venta);
                $totalGeneral = 0;
                // 4. Procesar los ítems del array items[X]
                foreach ($request->items as $item) {
                    // Saltar si por alguna razón el id_inventario viene vacío
                    if (empty($item['id_inventario'])) continue;

                    $cantidad = floatval($item['cantidad']);
                    $precio = floatval($item['precio_unitario']);
                    $subtotal = $cantidad * $precio;

                    // Crear detalle
                    $ventadet=VentasDetalle::create([
                        'id_venta' => $venta->id,
                        'id_inventario' => $item['id_inventario'],
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                    ]);
                    Log::info('Venta Det '.$ventadet);
                    // 5. Descontar del inventario real
                    $producto = Inventario::findOrFail($item['id_inventario']);
                    $producto->existencia -= $cantidad;
                    $producto->save();

                    $totalGeneral += $subtotal;
                }

                // 6. Actualizar el total final en la cabecera
                $venta->update(['total_venta' => $totalGeneral]);

                return redirect()->route('ventas.list')
                    ->with('success', "Venta #{$venta->nro_venta} procesada correctamente.");

        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return back()->with('error', 'Error al procesar la venta: ' . $e->getMessage())->withInput();
        }
    }

    public function ventaList(){
            $ventas = Ventas::with(['cliente', 'detalles', 'detalles.inventario'])
            ->orderBy('fecha', 'desc')
            ->get();

        return view('inventario.ventalist', compact('ventas'));
    }

    public function ventaShow($id)
    {
        $venta = Ventas::with(['cliente', 'detalles.inventario'])->findOrFail($id);
        return view('inventario.ventashow', compact('venta'));
    }


    public function requests()
    {
        // Se obtienen todas las solicitudes de insumos con las relaciones necesarias
        $solicitudes = InventarioDespacho::with('inventario', 'orden')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('inventario.requests', compact('solicitudes'));
    }

    /**
     * Aprueba una solicitud de insumo.
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        try {
            $solicitud = InventarioDespacho::findOrFail($id);
            if ($solicitud->estatus != 2) {
                Session::flash('error', 'Esta solicitud ya ha sido procesada.');
                return redirect()->back();
            }

            $solicitud->update(['estatus' => 3]);
            Session::flash('success', 'Solicitud aprobada correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al aprobar la solicitud: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Rechaza una solicitud de insumo.
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject($id)
    {
        try {
            $solicitud = InventarioDespacho::findOrFail($id);
            if ($solicitud->estatus == 1 || $solicitud->estatus == 5) {
                Session::flash('error', 'Esta solicitud ya ha sido despachada o rechazada.');
                return redirect()->back();
            }

            $solicitud->update(['estatus' => 5]);
            Session::flash('success', 'Solicitud rechazada correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al rechazar la solicitud: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Despacha un insumo y actualiza el inventario.
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dispatch($id)
    {
        $userId = Auth::id();
        DB::beginTransaction();
        try {
            $solicitud = InventarioDespacho::findOrFail($id);

            // Verificar que la solicitud esté aprobada
            if ($solicitud->estatus != 3) {
                Session::flash('error', 'Solo se pueden despachar insumos que han sido previamente aprobados.');
                return redirect()->back();
            }

            $inventario = Inventario::findOrFail($solicitud->id_inventario);

            // Verificar si hay suficiente stock
            if ($inventario->existencia < $solicitud->cantidad) {
                Session::flash('error', 'No hay suficiente stock para despachar este insumo.');
                $solicitud->update(['estatus' => 4]);
                return redirect()->back();
            }

            // Descontar del inventario y marcar como despachado
            $inventario->existencia -= $solicitud->cantidad;
            $inventario->save();

            $solicitud->update(['estatus' => 1]);
            
            if($inventario->existencia < $inventario->existencia_minima){
                // Aquí podrías agregar lógica para generar una alerta o notificación
                Session::flash('warning', 'El inventario ha caído por debajo del nivel mínimo.');
                $this->createAlert([
                    'id_usuario' => $userId, // ID del usuario responsable de la orden.
                    'id_rel' => $inventario->id, // ID de la item.
                    'observacion' => 'El inventario del insumo '.$inventario->codigo.'-' .$inventario->descripcion.' ha caído por debajo del nivel mínimo.',
                    'accion' => route('inventario.show', $inventario->id) , // Ruta para ver la orden.
                    'dias' => 0,
                ]);
            }
            DB::commit();
            Session::flash('success', 'Insumo despachado y inventario actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Session::flash('error', 'Error al despachar el insumo: ' . $e->getMessage());
        }

        return redirect()->back();
    }

}
