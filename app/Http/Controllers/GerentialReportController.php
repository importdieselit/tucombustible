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
        $precioCompra= 0.54;
        
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

        // 3. Filtrado Absoluto: Ya no hay mezclas de montos de diferentes turnos
        $records = ReportRecord::where('report_date', $selectedDate)
            ->where('turno', $selectedTurno)
            ->get();

        // Normalización de datos
        $records->transform(function ($item) {
            $item->tipo = trim($item->tipo);
            $item->cuenta = trim($item->cuenta);
            return $item;
        });


        // Clasificación de variables principales basados en el archivo CSV
        // Buscamos directamente por cuenta para evitar cruces con cotizaciones
        $ventasLitros = $records->where('cuenta', 'LITROS VENDIDOS')->first()->monto ?? 0;
        $ventasUsd = $records->where('cuenta', 'VENTAS REALIZADAS')->first()->monto ?? 0;
        $litrosComprados = $records->filter(fn($q) => trim($q->cuenta) === 'LITROS COMPRADOS')->first()->monto ?? 0;
        
        // OPEX y Liquidez
        $opexRecords = $records->filter(fn($item) => trim($item->tipo) === 'GASTOS OPERACIONALES');
        $bancosRecords = $records->filter(fn($item) => trim($item->tipo) === 'DISPONIBILIDAD DE BANCOS (MONEDA EXTRANJERA)'); 
        $cajasRecords = $records->filter(fn($item) => trim($item->tipo) === 'DISPONIBILIDAD DE CAJAS (MONEDA EXTRANJERA)'); 

        $balanceLitros = $litrosComprados - $ventasLitros;

        // 2. Extracción por Patrones (Agregación Inteligente)
        $ventasUsd = $records->where('cuenta', 'VENTAS REALIZADAS')->sum('monto');
        
        // El OPEX es TODO lo que diga 'GASTOS OPERACIONALES' 
        // EXCEPTO las cuentas que claramente son CxC o CxP (contaminación del sistema)
        $rawOpex = $records->where('tipo', 'GASTOS OPERACIONALES');
        
        $opexRecords = $rawOpex->reject(function ($item) {
            $pattern = '/(CUENTAS POR COBRAR|CXP NACIONALES|PAGARE)/i';
            return preg_match($pattern, $item->cuenta);
        });

        // 3. CxC: Capturamos tanto las CxC individuales como la cuenta agrupada
        $cxcRecords = $records->filter(function ($item) {
            return $item->tipo === 'CUENTAS POR COBRAR' || 
                   str_contains($item->cuenta, 'CUENTAS POR COBRAR');
        });

        // 4. CxP: Capturamos las deudas (Proveedor + Nacionales)
        $cxpRecords = $records->filter(function ($item) {
            return $item->tipo === 'CUENTAS POR PAGAR' || 
                   str_contains($item->cuenta, 'CXP NACIONALES');
        });
        $cxpCMGO= $records->where('cuenta' ,'CXP (USD)')->where('descuenta', 'MARINE GAS OIL ( TM )')->first()->monto ?? 0;
        $cxpCDiesel = $records->where('cuenta' ,'CXP (USD)')->where('descuenta', 'DIESEL')->first()->monto ?? 0;
        $cxpComb = $cxpCMGO + $cxpCDiesel;
        // 5. Inventario: Captura dinámica
        $inventario = $records->where('tipo', 'INVENTARIO')->sum('monto');

        // Sumatorias base
        $totalOpex = $opexRecords->sum('monto');
        $totalCxC = $cxcRecords->sum('monto');
        $totalCxP = $cxpRecords->sum('monto');

        $comprasUsd = $precioCompra * $litrosComprados; 
        $margenBruto = $ventasUsd > 0 ? (($ventasUsd - $comprasUsd) / $ventasUsd) * 100 : 0;

        // 6. Liquidez: Agrupación por Tipo
        $totalBancos = $records->where('tipo', 'DISPONIBILIDAD DE BANCOS (MONEDA EXTRANJERA)')->sum('monto');
        $totalCajas = $records->where('tipo', 'DISPONIBILIDAD DE CAJAS (MONEDA EXTRANJERA)')->sum('monto');

        // Control de Cartera (CxC y CxP)
        $cxcRecords = $records->filter(fn($item) => trim($item->tipo) === 'CUENTAS POR COBRAR');
        $cxpRecords = $records->filter(fn($item) => trim($item->tipo) === 'CUENTAS POR PAGAR');

        // Sumatorias de apoyo
        $totalLiquidez = $totalBancos + $totalCajas;

        // Porcentajes para gráficas vectoriales
        $pctBancos = $totalLiquidez > 0 ? ($totalBancos / $totalLiquidez) * 100 : 0;
        $pctCajas = $totalLiquidez > 0 ? ($totalCajas / $totalLiquidez) * 100 : 0;

        // Cálculo de % de control vs Ventas (Evitando división por cero)
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

        // Nueva Alerta: Control de Deuda
        if ($pctCxC_Ventas > 50) {
            $alertas->push("Riesgo de Flujo: Las Cuentas por Cobrar representan más del 50% de las ventas realizadas.");
        }


        return view('reports.admon', compact(
            'availableFiles', 'selectedDate', 'selectedTurno', 'ventasLitros', 'ventasUsd',
            'opexRecords', 'bancosRecords', 'cajasRecords', 'totalOpex',
            'totalBancos', 'totalCajas', 'totalLiquidez', 'pctBancos', 'pctCajas',
            'cxcRecords', 'cxpRecords', 'totalCxC', 'totalCxP', 'pctCxC_Ventas', 'pctCxP_Ventas',
            'margenBruto', 'comprasUsd', 'inventario', 'alertas', 'balanceLitros', 'litrosComprados','cxpComb'
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