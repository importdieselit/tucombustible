<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VehiculosPrecargadosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vehiculos_precargados')->delete();
        
        \DB::table('vehiculos_precargados')->insert(array (
            0 => 
            array (
                'cantidad_cargada' => 2000.0,
                'created_at' => '2025-09-23 11:14:43',
                'estatus' => 0,
                'fecha_hora_carga' => '2025-09-23 11:14:43',
                'fecha_hora_despacho' => NULL,
                'id' => 1,
                'id_vehiculo' => 19,
                'tipo_producto' => 'D',
                'updated_at' => '2025-09-23 11:14:43',
            ),
        ));
        
        
    }
}