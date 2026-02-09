<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AlmacenesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('almacenes')->delete();
        
        \DB::table('almacenes')->insert(array (
            0 => 
            array (
                'created_at' => '2025-08-27 00:16:18',
                'descripcion' => NULL,
                'id' => 1,
                'id_usuario' => NULL,
                'nombre' => 'Impordiesel 1',
                'ubicacion' => NULL,
                'updated_at' => '2025-08-27 00:16:18',
            ),
            1 => 
            array (
                'created_at' => '2025-08-27 00:16:18',
                'descripcion' => NULL,
                'id' => 2,
                'id_usuario' => NULL,
                'nombre' => 'Impordiesel 2
',
                'ubicacion' => NULL,
                'updated_at' => '2025-08-27 00:16:18',
            ),
        ));
        
        
    }
}