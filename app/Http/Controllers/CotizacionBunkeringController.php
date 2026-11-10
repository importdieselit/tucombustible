<?php

namespace App\Http\Controllers;

use App\Models\CotizacionBunkering;
use App\Models\CotizacionBunkeringDetalle;
use App\Models\CentroSuministro;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Parametro;

class CotizacionBunkeringController extends Controller
{
    public function index()
    {
        $cotizaciones = CotizacionBunkering::with('detalles')->orderBy('created_at', 'desc')->paginate(15);
        $productos = Producto::where('ctz', true)->orderBy('proveedor')->get();
        return view('cotizaciones_bunkering.index', compact('cotizaciones', 'productos'));
    }

    public function create()
    {
        $centrosSuministro = CentroSuministro::all();
        $productos = Producto::where('ctz', true)->orderBy('proveedor')->get();
        $paramDelivery = Parametro::where('clave', 'DELIVERY_BUNKERING_TARIFAS')->first();
        $metodosDespacho = Parametro::where('clave', 'DELIVERY_METODOS_DESPACHO')->first();
        $paramMatriz = \App\Models\Parametro::where('clave', 'ENLACE_CENTROS_METODOS')->first();
        $matrizCentros = $paramMatriz ? $paramMatriz->contenido : [];
        $configDelivery = $paramDelivery ? $paramDelivery->contenido : [
            'umbral_minimo_mt' => 890,
            'tarifa_plana_minima' => 8000,
            'tarifa_por_mt' => 9,
        ];
        $configDelivery['metodos_despacho'] = $metodosDespacho ? $metodosDespacho->contenido : [
            ['codigo' => 'TTS', 'etiqueta' => 'TRUCK TO SHIP (TTS)'],
            ['codigo' => 'STS', 'etiqueta' => 'SHIP TO SHIP (STS)'],
            ['codigo' => 'ALONGSITE', 'etiqueta' => 'ALONGSITE']
        ];
        return view('cotizaciones_bunkering.create', compact('productos', 'centrosSuministro', 'configDelivery', 'matrizCentros'));
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_buque'         => 'required|string|max:255',
            'bandera_buque'        => 'nullable|string|max:100',
            'imo_buque'            => 'nullable|string|max:50',
            'pagador'              => 'nullable|string|max:255',
            'selector_costos'      => 'required|integer|in:1,2,3,4,5',
            'punto_entrega'        => 'required|string|max:255',
            'centro_suministro'    => 'nullable|string|max:255',
            'vendedor'             => 'nullable|string|max:255',
            'metodo_despacho'      => 'nullable|string|max:100',
            'agencia_naviera'      => 'nullable|string|max:255',
            'fecha_entrega'        => 'nullable|date',
            'observaciones'        => 'nullable|string',
            'selector_costos_personalizado' => 'nullable|numeric|min:0|max:100',
            
            // Matriz dinámica de productos
            'productos'            => 'required|array|min:1',
            'productos.*.tipo'     => 'required|string',
            'productos.*.unidad'   => 'required|string|in:MT,LT,GL,DR', // ¡VITAL! Validar la unidad
            'productos.*.obs'      => 'nullable|string|max:255',
            'productos.*.tm'       => 'required|numeric|min:0.001', // Esto ahora es la Cantidad Solicitada
            'productos.*.precio'   => 'required|numeric|min:0',
            'productos.*.del_tm'   => 'nullable|numeric|min:0',
            'productos.*.del_comp' => 'nullable|numeric|min:0',
        ]);

        Log::info('Datos validados para la cotización: ', $validated);

        DB::beginTransaction();
        try {
            $numeroPedido = CotizacionBunkering::generarNumeroPedido();
            $selectorCostos = (int)$validated['selector_costos'];
            
            $pctSelector = match ($selectorCostos) {
                1 => 30.00,
                2 => 20.00,
                3 => 10.00,
                4 => 0.00,
                5 => isset($validated['selector_costos_personalizado']) ? (float)$validated['selector_costos_personalizado'] : 0.00,
            };
            $pctTotalAjuste = $pctSelector;

            $subtotalGeneral = 0;
            $deliveryGeneral = 0;
            $totalGeneral = 0;
            $detallesParaGuardar = [];

            foreach ($validated['productos'] as $prod) {
                $tipo = $prod['tipo'];
                $unidad = $prod['unidad'] ?? 'MT'; 
                $cantidadIngresada = (float) $prod['tm']; 
                $precioIngresado = (float) $prod['precio']; 
                
                $delTm = isset($prod['del_tm']) ? (float)$prod['del_tm'] : null;
                $delComp = isset($prod['del_comp']) ? (float)$prod['del_comp'] : null;

                // 1. Obtener la densidad real del producto
                $productoDb = Producto::where('nombre', $tipo)->first();
                $litrosPorTm = $productoDb && $productoDb->litros_tm ? (float) $productoDb->litros_tm : 1000.0;

                // 2. Factores de conversión hacia Litros
                $factores = [
                    'MT' => $litrosPorTm,
                    'LT' => 1.0,
                    'GL' => 3.78541,
                    'DR' => 200.0
                ];
                $factor = $factores[$unidad] ?? $litrosPorTm;

                // 3. Cálculos de equivalencias absolutas
                $litrosTotales = $cantidadIngresada * $factor;
                $tmReales = $litrosTotales / $litrosPorTm;

                // 4. Cálculos Financieros
                $subtotalProd = $cantidadIngresada * $precioIngresado;
                
                // OJO: El Delivery se calcula usando las Toneladas Métricas Reales ($tmReales)
                $deliveryFinal = CotizacionBunkeringDetalle::calcularDeliveryRenglon($tipo, $tmReales, $delTm, $delComp);
                
                $recargoMonto = ($subtotalProd * ($pctTotalAjuste / 100));
                $totalRenglon = $subtotalProd + $recargoMonto + $deliveryFinal;

                $subtotalGeneral += $subtotalProd;
                $deliveryGeneral += $deliveryFinal;
                $totalGeneral += $totalRenglon;

                $detallesParaGuardar[] = [
                    'tipo_producto'            => $tipo,
                    'unidad'                   => $unidad,
                    'cantidad_solicitada'      => $cantidadIngresada,
                    'toneladas_metricas'       => $tmReales,
                    'volumen_litros'           => $litrosTotales,
                    'precio_unitario_tm'       => $precioIngresado,
                    'subtotal_producto'        => $subtotalProd,
                    'costo_delivery'           => $deliveryFinal,
                    'total_renglon'            => $totalRenglon,
                ];
            }

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

            foreach ($detallesParaGuardar as $det) {
                $cotizacion->detalles()->create($det);
            }

            DB::commit();
            return redirect()->route('cotizaciones-bunkering.show', $cotizacion->id)
                ->with('success', 'Cotización generada exitosamente con N° de Pedido: ' . $numeroPedido);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar la cotización: ' . $e->getMessage());
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
        $paramMatriz = \App\Models\Parametro::where('clave', 'ENLACE_CENTROS_METODOS')->first();
        $matrizCentros = $paramMatriz ? $paramMatriz->contenido : [];
        $paramDelivery = Parametro::where('clave', 'DELIVERY_BUNKERING')->first();
        $metodosDespacho = Parametro::where('clave', 'DELIVERY_BUNKERING_METODOS')->first();
        $configDelivery = $paramDelivery ? $paramDelivery->contenido : [
            'umbral_minimo_mt' => 890,
            'tarifa_plana_minima' => 8000,
            'tarifa_por_mt' => 9,
        ];
        $configDelivery['metodos_despacho'] = $metodosDespacho ? $metodosDespacho->contenido : [
            ['codigo' => 'TTS', 'etiqueta' => 'TRUCK TO SHIP (TTS)'],
            ['codigo' => 'STS', 'etiqueta' => 'SHIP TO SHIP (STS)'],
            ['codigo' => 'ALONGSITE', 'etiqueta' => 'ALONGSITE']
        ];
        return view('cotizaciones_bunkering.create', compact('cotizacion', 'productos', 'centrosSuministro', 'configDelivery', 'matrizCentros'));
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
            'centro_suministro'    => 'nullable|string|max:255',
            'vendedor'             => 'nullable|string|max:255',
            'metodo_despacho'      => 'nullable|string|max:100',
            'agencia_naviera'      => 'nullable|string|max:255',
            'fecha_entrega'        => 'nullable|date',
            'observaciones'        => 'nullable|string',
            'selector_costos_personalizado' => 'nullable|numeric|min:0|max:100',
            
            // Matriz dinámica de productos
            'productos'            => 'required|array|min:1',
            'productos.*.tipo'     => 'required|string',
            'productos.*.unidad'   => 'required|string|in:MT,LT,GL,DR', // Misma validación estricta
            'productos.*.obs'      => 'nullable|string|max:255',
            'productos.*.tm'       => 'required|numeric|min:0.001',
            'productos.*.precio'   => 'required|numeric|min:0',
            'productos.*.del_tm'   => 'nullable|numeric|min:0',
            'productos.*.del_comp' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $selectorCostos = (int)$validated['selector_costos'];
            
            $pctSelector = match ($selectorCostos) {
                1 => 30.00,
                2 => 20.00,
                3 => 10.00,
                4 => 0.00,
                5 => isset($validated['selector_costos_personalizado']) ? (float)$validated['selector_costos_personalizado'] : 0.00,
            };
            $pctTotalAjuste = $pctSelector;

            $subtotalGeneral = 0;
            $deliveryGeneral = 0;
            $totalGeneral = 0;
            $detallesParaGuardar = [];

            foreach ($validated['productos'] as $prod) {
                $tipo = $prod['tipo'];
                $unidad = $prod['unidad'] ?? 'MT'; 
                $cantidadIngresada = (float) $prod['tm']; 
                $precioIngresado = (float) $prod['precio']; 
                
                $delTm = isset($prod['del_tm']) ? (float)$prod['del_tm'] : null;
                $delComp = isset($prod['del_comp']) ? (float)$prod['del_comp'] : null;

                $productoDb = Producto::where('nombre', $tipo)->first();
                $litrosPorTm = $productoDb && $productoDb->litros_tm ? (float) $productoDb->litros_tm : 1000.0;

                $factores = [
                    'MT' => $litrosPorTm,
                    'LT' => 1.0,
                    'GL' => 3.78541,
                    'DR' => 200.0
                ];
                $factor = $factores[$unidad] ?? $litrosPorTm;

                $litrosTotales = $cantidadIngresada * $factor;
                $tmReales = $litrosTotales / $litrosPorTm;

                $subtotalProd = $cantidadIngresada * $precioIngresado;
                
                $deliveryFinal = CotizacionBunkeringDetalle::calcularDeliveryRenglon($tipo, $tmReales, $delTm, $delComp);
                
                $recargoMonto = ($subtotalProd * ($pctTotalAjuste / 100));
                $totalRenglon = $subtotalProd + $recargoMonto + $deliveryFinal;

                $subtotalGeneral += $subtotalProd;
                $deliveryGeneral += $deliveryFinal;
                $totalGeneral += $totalRenglon;

                $detallesParaGuardar[] = [
                    'tipo_producto'            => $tipo,
                    'unidad'                   => $unidad,
                    'cantidad_solicitada'      => $cantidadIngresada,
                    'toneladas_metricas'       => $tmReales,
                    'volumen_litros'           => $litrosTotales,
                    'precio_unitario_tm'       => $precioIngresado,
                    'subtotal_producto'        => $subtotalProd,
                    'costo_delivery_base'      => $deliveryFinal,
                    'recargo_nivel_monto'      => $recargoMonto,
                    'costo_delivery'           => $deliveryFinal,
                    'delivery_costo_tm'        => $delTm,
                    'delivery_tarifa_completa' => $delComp,
                    'total_renglon'            => $totalRenglon,
                ];
            }

            // Actualizamos la cabecera
            $cotizacion->update([
                'nombre_buque'          => $validated['nombre_buque'],
                'bandera_buque'         => $validated['bandera_buque'] ?? null,
                'imo_buque'             => $validated['imo_buque'] ?? null,
                'pagador'               => $validated['pagador'] ?? null,
                'punto_entrega'         => $validated['punto_entrega'],
                'vendedor'              => $validated['vendedor'] ?? null,
                'metodo_despacho'       => $validated['metodo_despacho'] ?? null,
                'centro_suministro'     => $validated['centro_suministro'] ?? null,
                'agencia_naviera'       => $validated['agencia_naviera'] ?? null,
                'fecha_entrega'         => $validated['fecha_entrega'] ?? null,
                'observaciones'         => $validated['observaciones'] ?? null,
                
                'costo_delivery'        => $deliveryGeneral,
                'total_general'         => $totalGeneral,
            ]);

            // Eliminamos los detalles viejos y guardamos los nuevos (Método Sync destructivo y seguro)
            $cotizacion->detalles()->delete();
            
            foreach ($detallesParaGuardar as $det) {
                $cotizacion->detalles()->create($det);
            }

            DB::commit();
            return redirect()->route('cotizaciones-bunkering.show', $cotizacion->id)
                ->with('success', 'Cotización actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar la cotización: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al actualizar la cotización: ' . $e->getMessage()]);
        }
    }

}