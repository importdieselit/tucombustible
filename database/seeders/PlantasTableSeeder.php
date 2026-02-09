<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlantasTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('plantas')->delete();
        
        \DB::table('plantas')->insert(array (
            0 => 
            array (
                'alias' => 'PLANTA GUATIRE',
                'created_at' => NULL,
                'direccion' => NULL,
                'id' => 1,
                'id_tabulador_viatico' => 1,
                'nombre' => 'PDVSA GUATIRE',
                'proveedor' => 1,
                'telefono' => NULL,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'alias' => 'PLANTA GUARAGUAO',
                'created_at' => NULL,
                'direccion' => NULL,
                'id' => 2,
                'id_tabulador_viatico' => 2,
                'nombre' => 'PDVSA GUARAGUAO',
                'proveedor' => 1,
                'telefono' => NULL,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'alias' => 'PLANTA PALITO',
                'created_at' => NULL,
                'direccion' => NULL,
                'id' => 3,
                'id_tabulador_viatico' => 3,
                'nombre' => 'PDVSA PALITO',
                'proveedor' => 1,
                'telefono' => NULL,
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'alias' => 'PLANTA YAGUA',
                'created_at' => NULL,
                'direccion' => NULL,
                'id' => 4,
                'id_tabulador_viatico' => 4,
                'nombre' => 'PDVSA YAGUA',
                'proveedor' => 1,
                'telefono' => NULL,
                'updated_at' => NULL,
            ),
            4 => 
            array (
                'alias' => 'PLANTA SANTA LUCIA ',
                'created_at' => NULL,
                'direccion' => NULL,
                'id' => 5,
                'id_tabulador_viatico' => 5,
                'nombre' => 'PDVSA SANTA LUCIA ',
                'proveedor' => 1,
                'telefono' => NULL,
                'updated_at' => NULL,
            ),
            5 => 
            array (
                'alias' => 'PLANTA BAJO GRANDE - MARACAIBO',
                'created_at' => NULL,
                'direccion' => 'MARACAIBO',
                'id' => 6,
                'id_tabulador_viatico' => 22,
                'nombre' => 'PDVSA BAJO GRANDE',
                'proveedor' => 1,
                'telefono' => NULL,
                'updated_at' => NULL,
            ),
            6 => 
            array (
                'alias' => 'Planta Termoelectrica JME',
                'created_at' => NULL,
                'direccion' => 'Km 8 Carretera panamericana',
                'id' => 7,
                'id_tabulador_viatico' => 24,
                'nombre' => 'Termoelectrica Jose Maria España',
                'proveedor' => 1,
                'telefono' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}