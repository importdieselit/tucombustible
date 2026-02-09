<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RepostajeVehiculosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('repostaje_vehiculos')->delete();
        
        \DB::table('repostaje_vehiculos')->insert(array (
            0 => 
            array (
                'created_at' => '2026-01-21 13:53:45',
                'fecha' => '2026-01-21 14:37:00',
                'id' => 6,
                'id_admin' => NULL,
                'id_tanque' => 3,
                'id_us' => 1,
                'id_vehiculo' => 64,
                'nombre_ext' => NULL,
                'obs' => 'Despacho a vehiculo: Sin notas',
                'origin' => NULL,
                'pic' => NULL,
                'placa_ext' => NULL,
                'qty' => 120.0,
                'qtya' => 8812.06,
                'ref' => NULL,
                'rest' => 8692.06,
                'ticket' => NULL,
                'type' => NULL,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'created_at' => '2026-01-13 13:20:53',
                'fecha' => '2026-01-02 11:19:00',
                'id' => 5,
                'id_admin' => NULL,
                'id_tanque' => 3,
                'id_us' => 502,
                'id_vehiculo' => 60,
                'nombre_ext' => NULL,
                'obs' => 'Despacho a vehiculo: Sin notas',
                'origin' => NULL,
                'pic' => NULL,
                'placa_ext' => NULL,
                'qty' => 50.0,
                'qtya' => 13408.8,
                'ref' => NULL,
                'rest' => 13358.8,
                'ticket' => NULL,
                'type' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}