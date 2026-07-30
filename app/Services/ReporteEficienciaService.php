<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteEficienciaService {
    
    /**
     * Refresca la tabla de agregación con los datos del mes en curso.
     */
    public function refrescarAgregados() {
        DB::statement("
            REPLACE INTO reporte_eficiencia_actual (usuario_id, total_realizados, salidas_tardias, entradas_tardias, ultima_actualizacion)
            SELECT 
                u.id,
                COUNT(i.id) as total,
                SUM(CASE WHEN i.respuesta_in IS NULL AND i.created_at > v.fecha_salida THEN 1 ELSE 0 END) as salida_tardia,
                SUM(CASE WHEN i.respuesta_in IS NOT NULL AND TIMESTAMPDIFF(MINUTE, v.updated_at, i.updated_at) > 60 THEN 1 ELSE 0 END) as entrada_tardia,
                NOW()
            FROM users u
            JOIN inspecciones i ON i.usuario_id = u.id
            LEFT JOIN viajes v ON i.viaje_id = v.id
            WHERE i.created_at >= LAST_DAY(NOW() - INTERVAL 1 MONTH) + INTERVAL 1 DAY
            GROUP BY u.id
        ");
    }

    /**
     * Realiza el corte mensual: Mueve a histórico y limpia la tabla actual.
     */
    public function ejecutarCierre() {
        return DB::transaction(function () {
            $periodo = Carbon::now()->format('Y-m');

            // 1. Mover al histórico
            DB::statement("
                INSERT INTO historico_eficiencia_checklist 
                (usuario_id, periodo, total_realizados, salidas_tardias, entradas_tardias, fecha_cierre)
                SELECT usuario_id, ?, total_realizados, salidas_tardias, entradas_tardias, NOW()
                FROM reporte_eficiencia_actual
            ", [$periodo]);

            // 2. Limpiar tabla actual
            return DB::table('reporte_eficiencia_actual')->truncate();
        });
    }
}