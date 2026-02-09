<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VehiculoFotosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vehiculo_fotos')->delete();
        
        \DB::table('vehiculo_fotos')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'es_principal' => 1,
                'id' => 1,
                'ruta' => 'IMG-20251107-WA0040.jpg',
                'updated_at' => NULL,
                'vehiculo_id' => 18,
            ),
            1 => 
            array (
                'created_at' => NULL,
                'es_principal' => 1,
                'id' => 2,
                'ruta' => 'IMG-20251107-WA0042.jpg',
                'updated_at' => NULL,
                'vehiculo_id' => 45,
            ),
            2 => 
            array (
                'created_at' => NULL,
                'es_principal' => 1,
                'id' => 3,
                'ruta' => 'IMG-20251107-WA0043.jpg',
                'updated_at' => NULL,
                'vehiculo_id' => 48,
            ),
            3 => 
            array (
                'created_at' => NULL,
                'es_principal' => 1,
                'id' => 4,
                'ruta' => 'IMG-20251107-WA0044.jpg',
                'updated_at' => NULL,
                'vehiculo_id' => 16,
            ),
        ));
        
        
    }
}