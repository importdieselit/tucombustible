<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TipoOrdenTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tipo_orden')->delete();
        
        \DB::table('tipo_orden')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'id_tipo_orden' => 1,
                'nombre' => 'Preventivo',
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'created_at' => NULL,
                'id_tipo_orden' => 2,
                'nombre' => 'Predictivo',
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'created_at' => NULL,
                'id_tipo_orden' => 3,
                'nombre' => 'Correctivo',
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'created_at' => NULL,
                'id_tipo_orden' => 4,
                'nombre' => 'Revision',
                'updated_at' => NULL,
            ),
            4 => 
            array (
                'created_at' => NULL,
                'id_tipo_orden' => 5,
                'nombre' => 'Mantenimiento',
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}