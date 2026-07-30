<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conteo;
use App\Models\ConteoDetalles;
use App\Models\Inventario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;


class ConteoController extends Controller
{

    public function create() {
        $productos = Inventario::where('estatus', 1)
            ->orderBy('codigo', 'asc') 
            ->get();

        // Generamos un número de auditoría único para este proceso
        $nro_auditoria = 'AUD-' . strtoupper(date('M')) . '-' . date('dHi');
        return view('inventario.conteo', compact('productos', 'nro_auditoria'));
    }

    public function store(Request $request) {
        DB::transaction(function () use ($request) {
            $conteo = Conteo::create([
                'codigo' => $request->codigo,
                'user_id' => auth()->id(),
                'observaciones' => $request->observaciones
            ]);

            foreach ($request->inventario as $id => $datos) {
                $diferencia = $datos['stock_fisico'] - $datos['stock_teorico'];

                ConteoDetalles::create([
                    'conteo_id' => $conteo->id,
                    'inventario_id' => $id,
                    'ubicacion_codigo' => $datos['codigo'], // 01-03-2-24
                    'stock_teorico' => $datos['stock_teorico'],
                    'stock_fisico' => $datos['stock_fisico'],
                    'diferencia' => $diferencia
                ]);

                // Opcional: Actualizar stock real si el estatus es PROCESADO
                // Inventario::where('id', $item['id'])->update(['existencia' => $item['fisico']]);
            }
        });

        return redirect()->route('conteo.estadisticas')->with('success', 'Auditoría guardada');
    }

    public function estadisticas() {
        $stats = ConteoDetalles::selectRaw('
            SUM(CASE WHEN diferencia < 0 THEN 1 ELSE 0 END) as faltantes,
            SUM(CASE WHEN diferencia > 0 THEN 1 ELSE 0 END) as sobrantes,
            SUM(CASE WHEN diferencia = 0 THEN 1 ELSE 0 END) as exactos,
            SUM(ABS(diferencia)) as total_ajuste_unidades
        ')->first();

        return view('inventario.estadisticas_conteo', compact('stats'));
    }

    // public function index()
    // {
    //     // Obtenemos los últimos conteos realizados
    //     $auditorias = ConteoAuditoria::with('usuario')
    //         ->orderBy('created_at', 'desc')
    //         ->paginate(10);

    //     // Estadísticas Globales (Data de prueba/ejemplo funcional)
    //     $stats = [
    //         'total_items_auditados' => DetalleConteo::sum('stock_fisico'),
    //         'items_con_discrepancia' => DetalleConteo::whereRaw('stock_fisico != stock_teorico')->count(),
    //         // Índice de Exactitud de Inventario (ERI)
    //         'eri' => $this->calcularERI(), 
    //         'valor_ajuste_positivo' => DetalleConteo::whereRaw('stock_fisico > stock_teorico')->sum('valor_diferencia'),
    //         'valor_ajuste_negativo' => DetalleConteo::whereRaw('stock_fisico < stock_teorico')->sum('valor_diferencia'),
    //     ];

    //     return view('almacen.conteo.index', compact('auditorias', 'stats'));
    // }

    private function calcularERI()
    {
        $total = ConteoDetalles::count();
        if($total == 0) return 100;
        $sin_error = ConteoDetalles::whereRaw('stock_fisico = stock_teorico')->count();
        return ($sin_error / $total) * 100;
    }
}
