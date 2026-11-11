<?php

namespace App\Http\Controllers;

use App\Models\PrecioBunkerHistorico;
use App\Models\Producto; // Asegúrate de importar tu modelo Producto
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class PrecioBunkerHistoricoController extends Controller
{
    // 1. Carga la vista principal (Cáscara HTML)
    public function index()
    {
        $productos = Producto::where('ctz', true)->orderBy('proveedor')->get();
        return view('admin.bunker-historico.index', compact('productos'));
    }

    public function obtenerDatos(Request $request)
    {
        // 1. Paginamos solo las fechas distintas (10 fechas por página)
        $fechasPaginadas = PrecioBunkerHistorico::select('fecha')
            ->distinct()
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        // 2. Buscamos todos los registros de esas fechas (quitamos el orderBy de SQL)
        $registros = PrecioBunkerHistorico::whereIn('fecha', $fechasPaginadas->pluck('fecha'))->get();

        // 3. Definimos nuestro orden personalizado (Mapeo de prioridades)
        // NOTA: Incluyo 'HSFO' en la posición 1 por si Ship & Bunker lo envía con ese nombre en lugar de IFO 380
        $ordenPrioridad = [
            'MGO'     => 1,
            'IFO 380' => 2,
            'IFO380'  => 2,
            'HSFO'    => 2,
            'VLSFO'   => 3,
            'LSMGO'   => 4
        ];

        // 4. Ordenamos la colección en memoria y luego agrupamos por fecha
        $registrosAgrupados = $registros->sortBy(function ($registro) use ($ordenPrioridad) {
            // Si el combustible existe en nuestro arreglo, usa su número. Si no, le asigna 99 para mandarlo al final.
            return $ordenPrioridad[$registro->combustible] ?? 99;
        })->groupBy('fecha');

        // 5. Formateamos el JSON para que JS no tenga que pensar
        $dataTransformada = [];
        foreach ($fechasPaginadas as $item) {
            // Usamos ->values() para resetear las llaves del arreglo después de ordenar
            $dataTransformada[] = [
                'fecha' => Carbon::parse($item->fecha)->format('d/m/Y'),
                'combustibles' => $registrosAgrupados->get($item->fecha)->values()
            ];
        }

        $fechaMasReciente = PrecioBunkerHistorico::max('fecha');

        return response()->json([
            'paginacion' => [
                'current_page' => $fechasPaginadas->currentPage(),
                'last_page' => $fechasPaginadas->lastPage(),
                'data' => $dataTransformada
            ],
            'ultima_fecha' => carbon::parse($fechaMasReciente)->format('d/m/Y')
        ]);
    }

    // 3. API para sincronizar el precio con la tabla de productos
    public function sincronizarProducto(Request $request)
    {
        $request->validate([
            'historico_id' => 'required|exists:precios_bunker_historico,id'
        ]);

        $registro = PrecioBunkerHistorico::find($request->historico_id);

        // Buscamos el producto en tu tabla que coincida con el nombre (ej. contiene VLSFO)
        // NOTA: Si tu producto en BD se llama "Combustible VLSFO 0.5%", esto lo encontrará usando LIKE
        $producto = Producto::where('nombre', 'LIKE', '%' . $registro->combustible . '%')->first();

        if ($producto) {
            $precioAnterior = $producto->precio;
            $producto->precio = $registro->precio;
            $producto->save();

            return response()->json([
                'success' => true,
                'producto' => $producto->nombre,
                'precio' => $registro->precio,
                'message' => "{$producto->nombre} actualizado: de $" . number_format($precioAnterior, 2) . " a $" . number_format($registro->precio, 2)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "No se encontró un producto en tu base de datos que coincida con la palabra '{$registro->combustible}'."
        ], 404);
    }


    public function ejecutarScraperManualmente(Request $request)
    {
        try {
            // Ejecutamos el comando exactamente como lo harías en la terminal
            $exitCode = Artisan::call('bunker:sync');
            
            // Atrapamos lo que sea que el comando haya impreso (info, warnings, errors)
            $output = Artisan::output();

            // Artisan devuelve 0 cuando el comando termina en SUCCESS
            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Extracción completada. Los precios han sido guardados en la base de datos.',
                    'log' => $output
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'El scraper falló. Es posible que Cloudflare nos haya bloqueado temporalmente.',
                    'log' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error crítico al intentar ejecutar el comando: ' . $e->getMessage()
            ], 500);
        }
    }
}