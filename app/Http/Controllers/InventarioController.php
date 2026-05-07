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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use App\Models\InventarioSuministro;
use App\Models\SuministroCompra;
use App\Models\SuministroCompraDetalle;
use App\Models\Ventas;
use App\Models\Proveedor;
use App\Models\VentasDetalle;


class InventarioController extends BaseController
{
    /**
     * Muestra el dashboard principal del inventario.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. Métricas Principales (KPIs)
        
        $itemsBajoStock = Inventario::whereColumn('existencia', '<=', 'existencia_minima')
            ->where('estatus', 1)->where('venta',0)
            ->count();

        // Asumimos que los despachos son movimientos de tipo 'SALIDA' con estatus 'PENDIENTE'
        $despachosPendientes = InventarioSuministro::where('estatus', 2)
            ->count();

        $comprasPendientes = SuministroCompra::where('estatus', 2)
            ->count();
        
        $despachosPendientes+=$comprasPendientes;


        $movimientosRecientes = InventarioSuministro::where('created_at', '>=', Carbon::now()->subDay())
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

            $salidasData[] = InventarioSuministro::whereMonth('created_at', $mes->month)
                ->whereYear('created_at', $mes->year)
                ->sum('cantidad');
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
     * Mantiene la consistencia visual con la paleta Navy/Orange
     */
    private function generarColorCorporativo($id)
    {
        $colores = ['#002855', '#ff6600', '#6c757d', '#adb5bd', '#004085'];
        return $colores[$id % count($colores)];
    }

    public function buscar(Request $request)
        {
            $term = $request->get('q');
            
            // Buscamos por código de parte o descripción
            $resultados = Inventario::where('codigo', 'LIKE', "%$term%")
                ->orWhere('descripcion', 'LIKE', "%$term%")
                ->limit(10)
                ->get(['id', 'codigo', 'descripcion', 'marca', 'costo_div']);

            return response()->json($resultados);
        }


    /**
     * Muestra el formulario para crear un nuevo item.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $item = new Inventario();
        $almacenes = Almacen::all();
        $proveedores = Proveedor::all();
        // Nota: Los datos de Marca, Modelo, Condicion, etc. deben ser cargados aquí
        return view('inventario.form', compact('item', 'almacenes','proveedores'));
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
        $item = Inventario::with(['almacen', 'usuario'])->find($id);
        if (!$item) {
            abort(404, 'Item de inventario no encontrado.');
        }
        return view('inventario.show', compact('item'));
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
    
    public function entry()
    {
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
        $solicitudes = InventarioSuministro::with('inventario', 'orden')
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
            $solicitud = InventarioSuministro::findOrFail($id);
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
            $solicitud = InventarioSuministro::findOrFail($id);
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
            $solicitud = InventarioSuministro::findOrFail($id);

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
