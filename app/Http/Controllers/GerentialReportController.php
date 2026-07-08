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
        $precioCompra = 0.54;
        
        // Si no está logueado Y el token no coincide, entonces al login
        if (!auth()->check() && $request->get('token') !== $tokenValido) {
            // abort(403, 'Acceso no autorizado');
        }
        
        // 1. Obtener archivos disponibles para armar los filtros
        $availableFiles = ProcessedFile::orderBy('report_date', 'desc')
            ->orderBy('turno', 'desc')
            ->get();

        // Determinar archivo por defecto si no se pasa por URL
        $defaultFile = $availableFiles->first();

        // 2. CONTROL DE ERRORES: Búsqueda exacta combinando Fecha y Turno
        $selectedDate = $request->get('date', $defaultFile ? $defaultFile->report_date : now()->toDateString());
        $selectedTurno = $request->get('turno', $defaultFile ? $defaultFile->turno : 'Vespertino');

        // 3. Filtrado Absoluto
        $records = ReportRecord::where('report_date', $selectedDate)
            ->where('turno', $selectedTurno)
            ->get();

        // Normalización de datos
        $records->transform(function ($item) {
            $item->tipo = trim($item->tipo);
            $item->cuenta = trim($item->cuenta);
            return $item;
        });

        // 4. Agrupaciones y Desgloses (NUEVO)
        $ventasDesglose = $records->filter(fn($q) => in_array($q->tipo, ['VENTAS', 'VENTAS (USD)']));
        $cxcDesglose = $records->filter(fn($item) => $item->tipo === 'CUENTAS POR COBRAR');
        $cxcRecords = $records->filter(fn($item) => $item->tipo === 'CUENTAS POR COBRAR');
        $inventarioDesglose = $records->filter(fn($item) => $item->tipo === 'INVENTARIO');

        // Clasificación de variables principales basados en el archivo CSV
        // Ya no buscamos 'LITROS VENDIDOS' porque el CSV lo desglosa en facturas, notas y devoluciones
        $ventasLitros = $records->where('tipo', 'VENTAS')->sum('monto');
        $ventasUsd = $records->where('cuenta', 'VENTAS REALIZADAS')->first()->monto ?? 0;
        $litrosComprados = $records->filter(fn($q) => $q->cuenta === 'LITROS COMPRADOS')->first()->monto ?? 0;
        
        // OPEX y Liquidez
        $bancosRecords = $records->filter(fn($item) => $item->tipo === 'DISPONIBILIDAD DE BANCOS (MONEDA EXTRANJERA)'); 
        $cajasRecords = $records->filter(fn($item) => $item->tipo === 'DISPONIBILIDAD DE CAJAS (MONEDA EXTRANJERA)'); 

        $balanceLitros = $litrosComprados - $ventasLitros;

        // El OPEX es TODO lo que diga 'GASTOS OPERACIONALES' 
        // EXCEPTO las cuentas que claramente son CxC o CxP (contaminación del sistema)
        $rawOpex = $records->where('tipo', 'GASTOS OPERACIONALES');
        $opexRecords = $rawOpex->reject(function ($item) {
            $pattern = '/(CUENTAS POR COBRAR|CXP NACIONALES|PAGARE)/i';
            return preg_match($pattern, $item->cuenta);
        });

        // CxC y CxP Totales
        $totalCxC = $cxcDesglose->sum('monto');
        $cxpRecords = $records->filter(function ($item) {
            return $item->tipo === 'CUENTAS POR PAGAR' || str_contains($item->cuenta, 'CXP NACIONALES');
        });
        
        $cxpCMGO = $records->where('cuenta', 'CXP (USD)')->where('descuenta', 'MARINE GAS OIL ( TM )')->first()->monto ?? 0;
        $cxpCDiesel = $records->where('cuenta', 'CXP (USD)')->where('descuenta', 'DIESEL')->first()->monto ?? 0;
        $cxpComb = $cxpCMGO + $cxpCDiesel;

        // Sumatorias base
        $totalOpex = $opexRecords->sum('monto');
        $totalCxP = $cxpRecords->sum('monto');
        $inventarioTotal = $inventarioDesglose->sum('monto');

        // KPIs de Inventario precisos (Para inyectar en la vista)
        $invDiesel = $inventarioDesglose->filter(fn($q) => str_contains(strtoupper($q->cuenta), 'DIESEL'))->sum('monto');
        $invMGO = $inventarioDesglose->filter(fn($q) => str_contains(strtoupper($q->cuenta), 'MARINE GAS OIL'))->sum('monto');

        $comprasUsd = $precioCompra * $litrosComprados; 
        $margenBruto = $ventasUsd > 0 ? (($ventasUsd - $comprasUsd) / $ventasUsd) * 100 : 0;

        // Liquidez: Agrupación por Tipo
        $totalBancos = $bancosRecords->sum('monto');
        $totalCajas = $cajasRecords->sum('monto');
        $totalLiquidez = $totalBancos + $totalCajas;

        $pctBancos = $totalLiquidez > 0 ? ($totalBancos / $totalLiquidez) * 100 : 0;
        $pctCajas = $totalLiquidez > 0 ? ($totalCajas / $totalLiquidez) * 100 : 0;
        $pctCxC_Ventas = $ventasUsd > 0 ? ($totalCxC / $ventasUsd) * 100 : 0;
        $pctCxP_Ventas = $ventasUsd > 0 ? ($totalCxP / $ventasUsd) * 100 : 0;
        
        $alertas = collect();

        // Reglas de Negocio Automatizadas
        if ($ventasUsd > 0 && $totalOpex > $ventasUsd) {
            $alertas->push("Atención de Rentabilidad: Los Gastos Operacionales ($" . number_format($totalOpex, 2) . ") superaron los ingresos por Ventas en esta fecha.");
        }

        if ($totalOpex > 0) {
            foreach ($opexRecords as $gasto) {
                if (($gasto->monto / $totalOpex) > 0.40) {
                    $alertas->push("Concentración de Gasto: '{$gasto->cuenta}' representa un " . number_format(($gasto->monto / $totalOpex) * 100, 1) . "% de los gastos totales.");
                }
            }
        }

        foreach ($cajasRecords as $caja) {
            if ($caja->monto < 0) {
                $alertas->push("Anomalía Contable: '{$caja->cuenta}' presenta un saldo negativo ($" . number_format($caja->monto, 2) . ").");
            }
        }

        foreach ($bancosRecords as $banco) {
            if ($banco->monto < 0) {
                $alertas->push("Alerta de Liquidez: '{$banco->cuenta}' se encuentra en sobregiro ($" . number_format($banco->monto, 2) . ").");
            }
        }

        if ($pctCxC_Ventas > 50) {
            $alertas->push("Riesgo de Flujo: Las Cuentas por Cobrar representan más del 50% de las ventas realizadas.");
        }

        return view('reports.admon', compact(
            'availableFiles', 'selectedDate', 'selectedTurno', 'ventasLitros', 'ventasUsd',
            'ventasDesglose', 'cxcDesglose', 'inventarioDesglose', 'invDiesel', 'invMGO',
            'opexRecords', 'bancosRecords', 'cajasRecords', 'totalOpex',
            'totalBancos', 'totalCajas', 'totalLiquidez', 'pctBancos', 'pctCajas',
            'cxcRecords', 'cxpRecords', 'totalCxC', 'totalCxP', 'pctCxC_Ventas', 'pctCxP_Ventas',
            'margenBruto', 'comprasUsd', 'inventarioTotal', 'alertas', 'balanceLitros', 'litrosComprados','cxpComb'
        ));
    }

    public function sendWhatsapp(Request $request, WhatsappApiService $whatsappService)
    {
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('temp', 'public');
            $fullPath = storage_path('app/public/' . $path);
            
            $response = $whatsappService->enviarImagen($request->caption, $fullPath);
            
            Storage::disk('public')->delete($path);

            if ($response && $response->successful()) {
                return response()->json(['success' => true]);
            }
        }
        
        return response()->json(['success' => false], 500);
    }
}