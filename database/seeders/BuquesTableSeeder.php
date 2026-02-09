<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BuquesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('buques')->delete();
        
        \DB::table('buques')->insert(array (
            0 => 
            array (
                'bandera' => 'venezuela',
                'cliente_id' => 379,
                'created_at' => '2025-12-22 17:25:41',
                'id' => 1,
                'imo' => '24934443',
                'nombre' => 'NVoBuque',
                'updated_at' => '2025-12-22 17:25:41',
            ),
        ));
        
        
    }
}