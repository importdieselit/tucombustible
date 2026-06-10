<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReportRecord;
use App\Models\ProcessedFile;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\Storage;

class GerentialReportController extends Controller
{
    public function admon(Request $request)
    {

        $tokenValido = config('services.reporte.internal_token');
        // Si no está logueado Y el token no coincide, entonces al login
        if (!auth()->check() && $request->get('token') !== $tokenValido) {
        // abort(403, 'Acceso no autorizado');
        }
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

        $alertas = collect();

        // Regla 1: Rentabilidad (Gastos vs Ventas)
        if ($ventasUsd > 0 && $totalOpex > $ventasUsd) {
            $alertas->push("Atención de Rentabilidad: Los Gastos Operacionales ($" . number_format($totalOpex, 2) . ") superaron los ingresos por Ventas en esta fecha.");
        }

        // Regla 2: Concentración de OPEX (Un gasto acapara más del 40% del total)
        if ($totalOpex > 0) {
            foreach ($opexRecords as $gasto) {
                if (($gasto->monto / $totalOpex) > 0.40) {
                    $alertas->push("Concentración de Gasto: '{$gasto->cuenta}' representa un " . number_format(($gasto->monto / $totalOpex) * 100, 1) . "% de los gastos totales. Verificar si requiere desglose.");
                }
            }
        }

        // Regla 3: Anomalías en Cajas (Valores negativos)
        foreach ($cajasRecords as $caja) {
            if ($caja->monto < 0) {
                $alertas->push("Anomalía Contable: La '{$caja->cuenta}' presenta un saldo negativo ($" . number_format($caja->monto, 2) . "). Las cajas físicas no deberían estar en negativo; posible error de transcripción.");
            }
        }

        // Regla 4: Sobregiros Bancarios
        foreach ($bancosRecords as $banco) {
            if ($banco->monto < 0) {
                $alertas->push("Alerta de Liquidez: '{$banco->cuenta}' se encuentra en sobregiro ($" . number_format($banco->monto, 2) . ").");
            }
        }

        // Retornar las alertas compactadas a la vista
        return view('reports.admon', compact(
            'availableDates', 'selectedDate', 'ventasLitros', 'ventasUsd',
            'opexRecords', 'bancosRecords', 'cajasRecords', 'totalOpex',
            'totalBancos', 'totalCajas', 'totalLiquidez', 'pctBancos', 'pctCajas',
            'alertas' 
        ));
    }

    public function sendWhatsapp(Request $request, WhatsappApiService $whatsappService)
    {
        if ($request->hasFile('image')) {
            // Guardar archivo temporalmente
            $path = $request->file('image')->store('temp', 'public');
            $fullPath = storage_path('app/public/' . $path);
            
            // Usamos tu servicio
            // NOTA: Si tu API requiere URL pública, usa: asset('storage/'.$path)
            // Si tu servicio acepta la ruta absoluta, envíalo así:
            $response = $whatsappService->enviarImagen($request->caption, $fullPath);
            
            // Limpiar archivo después de enviar
            Storage::disk('public')->delete($path);

            if ($response && $response->successful()) {
                return response()->json(['success' => true]);
            }
        }
        
        return response()->json(['success' => false], 500);
    }
}