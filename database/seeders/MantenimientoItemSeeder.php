<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MantenimientoItemSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('mantenimiento_items')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $items = [
            // ==========================================
            // CATEGORÍA: OVERHAUL (Mantenimiento Mayor)
            // ==========================================
            [
                'categoria' => 'OVERHAUL',
                'nombre' => 'MOTOR DIÉSEL (RECONSTRUCCIÓN COMPLETA)',
                'costo_promedio' => 14000.00, // Valores financieros completos sin redondeo
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'OVERHAUL',
                'nombre' => 'TRANSMISIÓN Y CAJA DE CAMBIOS PESADA',
                'costo_promedio' => 3500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'OVERHAUL',
                'nombre' => 'ESTRUCTURA DE CABINA (CHUTO)',
                'costo_promedio' => 2800.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'OVERHAUL',
                'nombre' => 'RECONSTRUCCIÓN Y ARENADO DE CISTERNA (TANQUE)',
                'costo_promedio' => 5500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ==========================================
            // CATEGORÍA: REPARACIÓN GENERAL (Focalizada)
            // ==========================================
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'SISTEMA DE DESCARGA (VÁLVULAS API Y ACOPLES)',
                'costo_promedio' => 1250.00, // Específico para tanques cisterna
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'VÁLVULAS DE ALIVIO Y RECUPERACIÓN DE VAPORES',
                'costo_promedio' => 480.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'SISTEMA NEUMÁTICO Y FRENOS DE SERVICIO',
                'costo_promedio' => 650.00, // Vital por el peso del combustible líquido
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'FRENO DE MOTOR (JACOBS / RETARDADOR)',
                'costo_promedio' => 450.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'KIT DE EMBRAGUE COMPLETO (CLUTCH)',
                'costo_promedio' => 800.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'SISTEMA ELÉCTRICO ANTI-CHISPA (NORMA HAZMAT)',
                'costo_promedio' => 320.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'CHASIS, QUINTA RUEDA Y PERNO REY',
                'costo_promedio' => 950.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'SUSPENSIÓN DE EJES (CHUTO Y CISTERNA)',
                'costo_promedio' => 750.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'SISTEMA DE INYECCIÓN DE COMBUSTIBLE',
                'costo_promedio' => 1600.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'CUBICAJE, CALIBRACIÓN Y CERTIFICACIÓN LEGAL',
                'costo_promedio' => 600.00, // Trámite de metrología del tanque obligatorio
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'LATONERÍA, PINTURA E IDENTIFICACIÓN DE SEGURIDAD',
                'costo_promedio' => 850.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'categoria' => 'REPARACION GENERAL',
                'nombre' => 'SISTEMA DE FILTRADO Y TRAMPAS DE AGUA',
                'costo_promedio' => 220.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('mantenimiento_items')->insert($items);
    }
}