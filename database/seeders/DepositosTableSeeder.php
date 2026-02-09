<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DepositosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('depositos')->delete();
        
        \DB::table('depositos')->insert(array (
            0 => 
            array (
                'alto' => NULL,
                'capacidad_litros' => '14690.00',
                'capacidad_maxima' => 0.0,
                'created_at' => NULL,
                'diametro' => 228.0,
                'forma' => 'CH',
                'id' => 3,
                'longitud' => 363.0,
                'nivel_actual_litros' => '9543.37',
                'nivel_alerta_litros' => '2000.00',
                'nivel_cm' => '140.00',
                'producto' => 'DIESEL',
                'serial' => '00',
                'ubicacion' => 'Los Cortijos area 1',
                'updated_at' => '2026-02-03 09:48:13',
            ),
            1 => 
            array (
                'alto' => NULL,
                'capacidad_litros' => '40872.00',
                'capacidad_maxima' => 0.0,
                'created_at' => NULL,
                'diametro' => 267.0,
                'forma' => 'CH',
                'id' => 4,
                'longitud' => 730.0,
                'nivel_actual_litros' => '38941.65',
                'nivel_alerta_litros' => '2000.00',
                'nivel_cm' => '242.00',
                'producto' => 'DIESEL',
                'serial' => '1',
                'ubicacion' => 'Los Cortijos - Area 2',
                'updated_at' => '2026-01-19 16:46:30',
            ),
            2 => 
            array (
                'alto' => NULL,
                'capacidad_litros' => '40704.00',
                'capacidad_maxima' => 0.0,
                'created_at' => NULL,
                'diametro' => 267.0,
                'forma' => 'CH',
                'id' => 5,
                'longitud' => 727.0,
                'nivel_actual_litros' => '38433.44',
                'nivel_alerta_litros' => '1000.00',
                'nivel_cm' => '239.00',
                'producto' => 'DIESEL',
                'serial' => '2',
                'ubicacion' => 'Los Cortijos - Area 2',
                'updated_at' => '2026-01-19 16:46:43',
            ),
            3 => 
            array (
                'alto' => NULL,
                'capacidad_litros' => '40704.00',
                'capacidad_maxima' => 0.0,
                'created_at' => NULL,
                'diametro' => 267.0,
                'forma' => 'CH',
                'id' => 6,
                'longitud' => 727.0,
                'nivel_actual_litros' => '31158.33',
                'nivel_alerta_litros' => '1000.00',
                'nivel_cm' => '191.00',
                'producto' => 'DIESEL',
                'serial' => '3',
                'ubicacion' => 'Los Cortijos - Area 2',
                'updated_at' => '2026-01-19 16:46:54',
            ),
            4 => 
            array (
                'alto' => NULL,
                'capacidad_litros' => '24660.00',
                'capacidad_maxima' => 0.0,
                'created_at' => NULL,
                'diametro' => 229.0,
                'forma' => 'CH',
                'id' => 7,
                'longitud' => 604.0,
                'nivel_actual_litros' => '1921.87',
                'nivel_alerta_litros' => '1000.00',
                'nivel_cm' => '30.00',
                'producto' => 'DIESEL',
                'serial' => '4',
                'ubicacion' => 'Los Cortijos - Area 2',
                'updated_at' => '2026-02-03 10:06:23',
            ),
            5 => 
            array (
                'alto' => 282.0,
                'capacidad_litros' => '86370.96',
                'capacidad_maxima' => 0.0,
                'created_at' => NULL,
                'diametro' => 260.0,
                'forma' => 'R',
                'id' => 8,
                'longitud' => 1178.0,
                'nivel_actual_litros' => '79632.80',
                'nivel_alerta_litros' => '1000.00',
                'nivel_cm' => '260.00',
                'producto' => 'DIESEL',
                'serial' => '5',
                'ubicacion' => 'Los Cortijos - Area 2',
                'updated_at' => '2026-02-03 09:46:39',
            ),
            6 => 
            array (
                'alto' => 282.0,
                'capacidad_litros' => '86370.96',
                'capacidad_maxima' => 0.0,
                'created_at' => NULL,
                'diametro' => 260.0,
                'forma' => 'R',
                'id' => 9,
                'longitud' => 1178.0,
                'nivel_actual_litros' => '30628.00',
                'nivel_alerta_litros' => '1000.00',
                'nivel_cm' => '100.00',
                'producto' => 'DIESEL',
                'serial' => '6',
                'ubicacion' => 'Los Cortijos - Area 2',
                'updated_at' => '2026-02-03 09:41:45',
            ),
        ));
        
        
    }
}