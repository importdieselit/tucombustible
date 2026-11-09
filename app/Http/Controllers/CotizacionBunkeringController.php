<?php

namespace App\Http\Controllers;

use App\Models\CotizacionBunkering;
use App\Models\CotizacionBunkeringDetalle;
use App\Models\CentroSuministro;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CotizacionBunkeringController extends Controller
{
    public function index()
    {
        $cotizaciones = CotizacionBunkering::with('detalles')->orderBy('created_at', 'desc')->paginate(15);
        $productos = Producto::where('ctz', true)->orderBy('nombre')->get();
        return view('cotizaciones_bunkering.index', compact('cotizaciones', 'productos'));
    }

    public function create()
    {
        $centrosSuministro = CentroSuministro::all();
        $productos = Producto::where('ctz', true)->orderBy('nombre')->get();
        return view('cotizaciones_bunkering.create', compact('productos', 'centrosSuministro'));
    }

    public function store(Request $request)
    {
        $validated =$request->validate([
            'nombre_buque'         => 'required|string|max:255',
            'bandera_buque'        => 'nullable|string|max:100',
            'imo_buque'            => 'nullable|string|max:50',
            'pagador'              => 'nullable|string|max:255',
            'selector_costos'      => 'required|integer|in:1,2,3,4,5',
            'punto_entrega'        => 'required|string|max:255',
            'centro_suministro'     => 'nullable|string|max:255',
            'vendedor'             => 'nullable|string|max:255',
            'metodo_despacho'      => 'nullable|string|max:100',
            'agencia_naviera'      => 'nullable|string|max:255',
            'fecha_entrega'        => 'nullable|date',
            'observaciones'        => 'nullable|string',
            'selector_costos_personalizado' => 'nullable|numeric|min:0|max:100',
            // Matriz dinámica de productos
            'productos'            => 'required|array|min:1',
            'productos.*.tipo'     => 'required|string',
            'productos.*.obs'      => 'nullable|string|max:255',
            'productos.*.tm'       => 'required|numeric|min:0.001',
            'productos.*.precio'   => 'required|numeric|min:0',
            'productos.*.del_tm'   => 'nullable|numeric|min:0',
            'productos.*.del_comp' => 'nullable|numeric|min:0',
        ]);
        log::info('Datos validados para la cotización: ', $validated);

        DB::beginTransaction();
        try {
            log::info('Iniciando transacción para guardar la cotización.');
            $numeroPedido = CotizacionBunkering::generarNumeroPedido();
            log::info('Número de pedido generado: ' . $numeroPedido);
            $selectorCostos = (int)$validated['selector_costos'];
            // Costo 1: +30%, Costo 2: +20%, Costo 3: +10%, Costo 4: 0%, Costo 5: Personalizado
            $pctSelector = match ($selectorCostos) {
                1 => 30.00,
                2 => 20.00,
                3 => 10.00,
                4 => 0.00,
                5 => isset($validated['selector_costos_personalizado']) ? (float)$validated['selector_costos_personalizado'] : 0.00,
            };
            log::info('Porcentaje de recargo aplicado según selector de costos: ' . $pctSelector . '%');
            $pctTotalAjuste = $pctSelector;

            $subtotalGeneral = 0;
            $deliveryGeneral = 0;
            $totalGeneral = 0;
            $detallesParaGuardar = [];

            foreach ($validated['productos'] as $prod) {$tipo = $prod['tipo'];$tm = (float) $prod['tm'];$precio = (float) $prod['precio'];$delTm = isset($prod['del_tm']) ? (float)$prod['del_tm'] : null;
                $delComp = isset($prod['del_comp']) ? (float)$prod['del_comp'] : null;

                // Litros (Densidad @15°C = 0.8500)
                $litros = ($tm * 1000) / 0.8500;  // Subtotal Producto
                $subtotalProd = $tm * $precio;

                // Delivery Fee Base individual
                $deliveryFinal = CotizacionBunkeringDetalle::calcularDeliveryRenglon($tipo,$tm, $delTm,$delComp);
                
                // Recargos (Selector + Extra + Monto Fijo)
                $recargoMonto = ($subtotalProd * ($pctTotalAjuste / 100));
                
                $totalRenglon = $subtotalProd+ $recargoMonto +$deliveryFinal;

                $subtotalGeneral +=$subtotalProd;
                $deliveryGeneral +=$deliveryFinal;
                $totalGeneral +=$totalRenglon;

                $detallesParaGuardar[] = [
                    'tipo_producto'            => $tipo,
                    'toneladas_metricas'       => $tm,
                    'volumen_litros'           => $litros,
                    'precio_unitario_tm'       => $precio,
                    'subtotal_producto'        => $subtotalProd,
                    'costo_delivery_base'      => $deliveryFinal,
                    'recargo_nivel_monto'      => $recargoMonto,
                    'costo_delivery'           => $deliveryFinal,
                    'delivery_costo_tm'        => $delTm,
                    'delivery_tarifa_completa' => $delComp,
                    'total_renglon'            => $totalRenglon,
                ];

                log::info('Detalle del producto procesado: ', end($detallesParaGuardar));
            }
            log::info('Totales calculados: Subtotal General = ' . $subtotalGeneral . ', Delivery General = ' . $deliveryGeneral . ', Total General = ' . $totalGeneral);
            $cotizacion = CotizacionBunkering::create([
                'numero_pedido'         => $numeroPedido,
                'nombre_buque'          => $validated['nombre_buque'],
                'bandera_buque'         => $validated['bandera_buque'] ?? null,
                'imo_buque'             => $validated['imo_buque'] ?? null,
                'pagador'               => $validated['pagador'] ?? null,
                'punto_entrega'         => $validated['punto_entrega'],
                'vendedor'              => $validated['vendedor'] ?? null,
                'metodo_despacho'       => $validated['metodo_despacho'] ?? null,
                'centro_suministro'     => $validated['centro_suministro'] ?? null,
                'agencia_naviera'       => $validated['agencia_naviera'] ?? null,
                'fecha_nominacion'      => date('Y-m-d H:i:s'),
                'fecha_entrega'         => $validated['fecha_entrega'] ?? null,
                'observaciones'         => $validated['observaciones'] ?? null,
                
                'costo_delivery'        => $deliveryGeneral,
                'total_general'         => $totalGeneral,
            ]);
            Log::info('Cotización creada con ID: ' . $cotizacion->id . ' y N° de Pedido: ' . $numeroPedido);
            foreach ($detallesParaGuardar as$det) {
                log::info('Guardando detalle del producto: ', $det);
                $cotizacion->detalles()->create($det);
            }

            DB::commit();
            log::info('Transacción completada exitosamente para la cotización con N° de Pedido: ' . $numeroPedido);
            return redirect()->route('cotizaciones-bunkering.show', $cotizacion->id)
                ->with('success', 'Cotización generada exitosamente con N° de Pedido: ' . $numeroPedido);

        } catch (\Exception $e) {
            Log::error('Error al guardar la cotización: ' . $e->getMessage());
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al guardar la cotización: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $cotizacion = CotizacionBunkering::with('detalles')->findOrFail($id);
        return view('cotizaciones_bunkering.show', compact('cotizacion'));
    }
    
    public function edit($id)
    {
        $cotizacion = CotizacionBunkering::with('detalles')->findOrFail($id);
        $centrosSuministro = CentroSuministro::all();
        $productos = Producto::where('ctz', true)->orderBy('nombre')->get();
        return view('cotizaciones_bunkering.create', compact('cotizacion', 'productos', 'centrosSuministro'));
    }

    public function update(Request $request, $id)
    {
        $cotizacion = CotizacionBunkering::with('detalles')->findOrFail($id);

        $validated = $request->validate([
            'nombre_buque'         => 'required|string|max:255',
            'bandera_buque'        => 'nullable|string|max:100',
            'imo_buque'            => 'nullable|string|max:50',
            'pagador'              => 'nullable|string|max:255',
            'selector_costos'      => 'required|integer|in:1,2,3,4,5',
            'punto_entrega'        => 'required|string|max:255',
            'centro_suministro'     => 'nullable|string|max:255',
            'vendedor'             => 'nullable|string|max:255',
            'metodo_despacho'      => 'nullable|string|max:100',
            'agencia_naviera'      => 'nullable|string|max:255',
            'fecha_entrega'        => 'nullable|date',
            'observaciones'        => 'nullable|string',
            // Matriz dinámica de productos
            'productos'            => 'required|array|min:1',
            'productos.*.tipo'     => 'required|string',
            'productos.*.obs'      => 'nullable|string|max:255',
            'productos.*.tm'       => 'required|numeric|min:0.001',
            'productos.*.precio'   => 'required|numeric|min:0',
            'productos.*.del_tm'   => 'nullable|numeric|min:0',
            'productos.*.del_comp' => 'nullable|numeric|min:0',
        ]);

    }

}