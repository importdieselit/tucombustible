<?php

namespace App\Traits;

use App\Services\ReporteHistoricoService;
use Illuminate\Http\Request;

trait HasReportesHistoricos
{
    protected function procesarReporte(
        Request $request,
        string $vista,
        string $nombreReporte,
        $fechaConsolidada,
        string $turno,
        callable $callbackGenerarData,
        $fechasDisponibles=null
    ) {
        $service = app(ReporteHistoricoService::class);
       
        $solicitaGuardar = $request->boolean('guardar_snapshot');
        // Detecta si el usuario está buscando una fecha o rango específico en el formulario/URL
        $esConsultaFechaEspecifica = $request->filled('fecha') || $request->filled('fecha_inicio');
        
        // CASO 3: Ejecución desde Bot/Cron con guardar_snapshot=true
        if ($solicitaGuardar) {
            $data = $callbackGenerarData(); // Genera la data en vivo
            $service->guardar($nombreReporte, $fechaConsolidada, $turno, $data); // Guarda o actualiza en BD
            
            $data['es_historico'] = false;
            $data['snapshot_guardado'] = true;
            return view($vista, $data);
        }

        // CASO 2: Si el usuario consultó una fecha específica, busca en el histórico
        if ($esConsultaFechaEspecifica) {
            $historicoData = $service->obtener($nombreReporte, $fechaConsolidada, $turno);
                
            if ($historicoData) {
                // Si existe el snapshot, se retorna congelado
                $historicoData['es_historico'] = true;
                $historicoData['fechasDisponibles']=$fechasDisponibles;
                return view($vista, $historicoData);
            }
            // Si NO existe el histórico, la ejecución continúa al CASO 1 (Fallback en vivo)
        }

        // CASO 1: Consulta directa normal o Fallback (no había snapshot guardado)
        $data = $callbackGenerarData();
        $data['es_historico'] = false;
        $data['fechasDisponibles']=$fechasDisponibles;
        
        return view($vista, $data);
    }
}