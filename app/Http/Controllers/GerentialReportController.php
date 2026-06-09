<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReportRecord;
use App\Models\ProcessedFile;

class GerentialReportController extends Controller
{
    public function index(Request $request)
    {
        // Obtener fechas disponibles para el filtro selector
        $availableDates = ProcessedFile::orderBy('report_date', 'desc')->pluck('report_date');
        
        // Seleccionar fecha por defecto (la última procesada o la actual)
        $selectedDate = $request->get('date', $availableDates->first() ?? now()->toDateString());

        // Obtener registros de esa fecha
        $records = ReportRecord::where('report_date', $selectedDate)->get();

        // Clasificación de variables operacionales limpias
        $ventasLitros = $records->where('tipo', 'VENTAS');
        $ventasUsd = $records->where('tipo', 'VENTAS (USD)')->first()->monto ?? 0;
        $opexRecords = $records->where('tipo', 'GASTOS OPERACIONALES');
        $bancosRecords = $records->where('tipo', 'DISPONIBILIDAD DE BANCOS (MONEDA EXTRANJERA)'); 
        $cajasRecords = $records->where('tipo', 'DISPONIBILIDAD DE CAJAS (MONEDA EXTRANJERA)'); 

        // Totales de apoyo
        $totalOpex = $opexRecords->sum('monto');
        $totalBancos = $bancosRecords->sum('monto');
        $totalCajas = $cajasRecords->sum('monto');
        $totalLiquidez = $totalBancos + $totalCajas;

        // Porcentajes para gráficas vectoriales
        $pctBancos = $totalLiquidez > 0 ? ($totalBancos / $totalLiquidez) * 100 : 0;
        $pctCajas = $totalLiquidez > 0 ? ($totalCajas / $totalLiquidez) * 100 : 0;

        return view('reports.admon', compact(
            'availableDates', 'selectedDate', 'ventasLitros', 'ventasUsd',
            'opexRecords', 'bancosRecords', 'cajasRecords', 'totalOpex',
            'totalBancos', 'totalCajas', 'totalLiquidez', 'pctBancos', 'pctCajas'
        ));
    }
}