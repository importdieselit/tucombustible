<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ParametrosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('parametros')->delete();
        
        \DB::table('parametros')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'id' => 1,
                'nombre' => 'peaje',
                'updated_at' => '2025-11-06 15:29:45',
                'valor' => '7.00',
            ),
            1 => 
            array (
                'created_at' => NULL,
                'id' => 2,
                'nombre' => 'desayuno',
                'updated_at' => NULL,
                'valor' => '10',
            ),
            2 => 
            array (
                'created_at' => NULL,
                'id' => 3,
                'nombre' => 'almuerzo',
                'updated_at' => NULL,
                'valor' => '10',
            ),
            3 => 
            array (
                'created_at' => NULL,
                'id' => 4,
                'nombre' => 'resguardo',
                'updated_at' => '2025-12-29 17:59:51',
                'valor' => '0',
            ),
        ));
        
        
    }
}