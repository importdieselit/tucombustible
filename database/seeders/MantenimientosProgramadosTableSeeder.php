<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MantenimientosProgramadosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mantenimientos_programados')->delete();
        
        \DB::table('mantenimientos_programados')->insert(array (
            0 => 
            array (
                'created_at' => '2025-11-24 10:13:08',
                'descripcion' => 'PLAN 10.000 KM / 200 HORAS - RUTINA DE CAMBIO M1',
                'estatus' => 2,
                'fecha' => '2025-12-02',
                'id' => 6,
                'km' => 1782,
                'orden_id' => 94,
                'plan_id' => NULL,
                'tipo' => 'M1',
                'updated_at' => '2025-11-24 10:13:08',
                'user_id' => NULL,
                'vehiculo_id' => 18,
            ),
            1 => 
            array (
                'created_at' => '2025-11-24 10:10:54',
                'descripcion' => 'PLAN 10.000 KM / 200 HORAS - RUTINA DE CAMBIO M1',
                'estatus' => 2,
                'fecha' => '2025-12-02',
                'id' => 5,
                'km' => 69304,
                'orden_id' => 93,
                'plan_id' => NULL,
                'tipo' => 'M1',
                'updated_at' => '2025-11-24 10:10:54',
                'user_id' => NULL,
                'vehiculo_id' => 16,
            ),
            2 => 
            array (
                'created_at' => '2025-11-24 10:08:55',
                'descripcion' => 'PLAN 10.000 KM / 200 HORAS - RUTINA DE CAMBIO M1',
                'estatus' => 2,
                'fecha' => '2025-12-02',
                'id' => 4,
                'km' => 743266,
                'orden_id' => 92,
                'plan_id' => NULL,
                'tipo' => 'M1',
                'updated_at' => '2025-11-24 10:08:55',
                'user_id' => NULL,
                'vehiculo_id' => 30,
            ),
            3 => 
            array (
                'created_at' => '2025-11-24 10:14:57',
                'descripcion' => 'PLAN 10.000 KM / 200 HORAS - RUTINA DE CAMBIO M1',
                'estatus' => 2,
                'fecha' => '2025-12-02',
                'id' => 7,
                'km' => 111815,
                'orden_id' => 95,
                'plan_id' => NULL,
                'tipo' => 'M1',
                'updated_at' => '2025-11-24 10:14:57',
                'user_id' => NULL,
                'vehiculo_id' => 13,
            ),
            4 => 
            array (
                'created_at' => '2025-12-08 11:53:59',
                'descripcion' => 'PLAN 10.000 KM / 200 HORAS - RUTINA DE CAMBIO M1',
                'estatus' => 2,
                'fecha' => '2025-12-10',
                'id' => 8,
                'km' => 647792,
                'orden_id' => 127,
                'plan_id' => NULL,
                'tipo' => 'M1',
                'updated_at' => '2025-12-08 11:53:59',
                'user_id' => NULL,
                'vehiculo_id' => 14,
            ),
        ));
        
        
    }
}