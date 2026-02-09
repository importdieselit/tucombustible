<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TipoVehiculosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tipo_vehiculos')->delete();
        
        \DB::table('tipo_vehiculos')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'esquema' => 1,
                'id' => 1,
                'tipo' => 'CAMION',
                'trailer' => 0,
                'updated_at' => NULL,
                'vol' => 35.0,
            ),
            1 => 
            array (
                'created_at' => NULL,
                'esquema' => 2,
                'id' => 2,
                'tipo' => 'CISTERNA',
                'trailer' => 0,
                'updated_at' => NULL,
                'vol' => 30000.0,
            ),
            2 => 
            array (
                'created_at' => NULL,
                'esquema' => 3,
                'id' => 3,
                'tipo' => 'CHUTO',
                'trailer' => 0,
                'updated_at' => NULL,
                'vol' => 0.0,
            ),
            3 => 
            array (
                'created_at' => NULL,
                'esquema' => NULL,
                'id' => 4,
                'tipo' => 'FURGON',
                'trailer' => 0,
                'updated_at' => NULL,
                'vol' => NULL,
            ),
            4 => 
            array (
                'created_at' => NULL,
                'esquema' => NULL,
                'id' => 5,
                'tipo' => 'TANQUE',
                'trailer' => 0,
                'updated_at' => NULL,
                'vol' => NULL,
            ),
        ));
        
        
    }
}