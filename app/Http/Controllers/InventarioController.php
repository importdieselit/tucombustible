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
use App\Models\InventarioDespacho;
use App\Models\InventarioCompra;    
use App\Models\InventarioAsociado;
use App\Models\Ubicacion;
use App\Models\InventarioStock;
use App\Models\AlmacenEstructuraGrid;
use App\Models\Ventas;
use App\Models\VentasDetalle;
use App\Models\Orden;
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

        return view('inventario.index', compact('totalItems', 'totalCantidad', 'valorTotal'));
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
        $query->with(['almacen','ubicacion','modelosAsociados','equivalentes']);
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
        $almacen = $item->almacen;
        $estructuras = collect();
        $ubicacionPrincipal = null;
        $ubicacion_texto = 'No asignada';

        if ($almacen) {
            // Obtenemos todo el plano del almacén
            $estructuras = AlmacenEstructuraGrid::where('almacen_id', $almacen->id)
                ->get()
                ->keyBy(function($est) {
                    return $est->coord_y . '-' . $est->coord_x;
                });
                
            // Obtenemos la ubicación donde hay stock de este ítem
            $stockPrincipal = $item->stocks->first(); 
            if ($stockPrincipal && $stockPrincipal->ubicacion) {
                $ubicacionPrincipal = $stockPrincipal->ubicacion;
                $ubicacion_texto = "{$ubicacionPrincipal->codigo_ubicacion} | Pasillo {$ubicacionPrincipal->pasillo}, Estante {$ubicacionPrincipal->estante}, Nivel {$ubicacionPrincipal->nivel}, Casilla {$ubicacionPrincipal->posicion}";
            }
        }
        
        return view('inventario.show', compact(
            'item', 'movimientos', 'graficaFechas', 'graficaStock', 'equivalentes', 'vehiculos',
            'almacen', 'estructuras', 'ubicacionPrincipal', 'ubicacion_texto'
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
