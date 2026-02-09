<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MuellesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('muelles')->delete();
        
        \DB::table('muelles')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'id' => 1,
                'nombre' => 'Guaraguao',
                'ubicacion' => 28,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'created_at' => NULL,
                'id' => 7,
                'nombre' => 'Jose',
                'ubicacion' => 28,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'created_at' => NULL,
                'id' => 8,
                'nombre' => 'Terminal Guanta',
                'ubicacion' => 28,
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'created_at' => NULL,
                'id' => 9,
                'nombre' => '31',
                'ubicacion' => 27,
                'updated_at' => NULL,
            ),
            4 => 
            array (
                'created_at' => NULL,
                'id' => 10,
                'nombre' => '32',
                'ubicacion' => 27,
                'updated_at' => NULL,
            ),
            5 => 
            array (
                'created_at' => NULL,
                'id' => 11,
                'nombre' => 'PDVSA',
                'ubicacion' => 27,
                'updated_at' => NULL,
            ),
            6 => 
            array (
                'created_at' => NULL,
                'id' => 12,
                'nombre' => 'Terminal Alfaca',
                'ubicacion' => 27,
                'updated_at' => NULL,
            ),
            7 => 
            array (
                'created_at' => NULL,
                'id' => 13,
                'nombre' => 'Bajo Grande',
                'ubicacion' => 23,
                'updated_at' => NULL,
            ),
            8 => 
            array (
                'created_at' => NULL,
                'id' => 14,
                'nombre' => 'Terminal Maracaibo',
                'ubicacion' => 23,
                'updated_at' => NULL,
            ),
            9 => 
            array (
                'created_at' => NULL,
                'id' => 15,
                'nombre' => 'Bauxilum',
                'ubicacion' => 29,
                'updated_at' => NULL,
            ),
            10 => 
            array (
                'created_at' => NULL,
                'id' => 16,
                'nombre' => 'SIDOR',
                'ubicacion' => 29,
                'updated_at' => NULL,
            ),
            11 => 
            array (
                'created_at' => NULL,
                'id' => 17,
                'nombre' => 'Ferrominera',
                'ubicacion' => 29,
                'updated_at' => NULL,
            ),
            12 => 
            array (
                'created_at' => NULL,
                'id' => 18,
                'nombre' => 'combustible',
                'ubicacion' => 32,
                'updated_at' => NULL,
            ),
            13 => 
            array (
                'created_at' => NULL,
                'id' => 19,
                'nombre' => 'Servicios',
                'ubicacion' => 34,
                'updated_at' => NULL,
            ),
            14 => 
            array (
                'created_at' => NULL,
                'id' => 20,
                'nombre' => 'Nautico',
                'ubicacion' => 33,
                'updated_at' => NULL,
            ),
            15 => 
            array (
                'created_at' => NULL,
                'id' => 21,
                'nombre' => 'Sur',
                'ubicacion' => 33,
                'updated_at' => NULL,
            ),
            16 => 
            array (
                'created_at' => NULL,
                'id' => 22,
                'nombre' => '1',
                'ubicacion' => 31,
                'updated_at' => NULL,
            ),
            17 => 
            array (
                'created_at' => NULL,
                'id' => 23,
                'nombre' => '2',
                'ubicacion' => 31,
                'updated_at' => NULL,
            ),
            18 => 
            array (
                'created_at' => NULL,
                'id' => 24,
                'nombre' => 'Maritimo CRP',
                'ubicacion' => 31,
                'updated_at' => NULL,
            ),
            19 => 
            array (
                'created_at' => NULL,
                'id' => 25,
            'nombre' => '4 (carga General)',
                'ubicacion' => 30,
                'updated_at' => NULL,
            ),
            20 => 
            array (
                'created_at' => NULL,
                'id' => 26,
                'nombre' => '5',
                'ubicacion' => 30,
                'updated_at' => NULL,
            ),
            21 => 
            array (
                'created_at' => NULL,
                'id' => 27,
            'nombre' => '6 (bolipuertos)',
                'ubicacion' => 30,
                'updated_at' => NULL,
            ),
            22 => 
            array (
                'created_at' => NULL,
                'id' => 28,
            'nombre' => 'Internacional (Puerto Este)',
                'ubicacion' => 37,
                'updated_at' => NULL,
            ),
            23 => 
            array (
                'created_at' => NULL,
                'id' => 29,
                'nombre' => 'Ferrys',
                'ubicacion' => 37,
                'updated_at' => NULL,
            ),
            24 => 
            array (
                'created_at' => NULL,
                'id' => 30,
                'nombre' => 'Cruceros',
                'ubicacion' => 37,
                'updated_at' => NULL,
            ),
            25 => 
            array (
                'created_at' => NULL,
                'id' => 31,
                'nombre' => 'Principal Bolipuertos',
                'ubicacion' => 39,
                'updated_at' => NULL,
            ),
            26 => 
            array (
                'created_at' => NULL,
                'id' => 32,
                'nombre' => 'Isla de LLenado - MGO-1 PDVSA',
                'ubicacion' => 38,
                'updated_at' => NULL,
            ),
            27 => 
            array (
                'created_at' => NULL,
                'id' => 33,
                'nombre' => '1',
                'ubicacion' => 40,
                'updated_at' => NULL,
            ),
            28 => 
            array (
                'created_at' => NULL,
                'id' => 34,
                'nombre' => '2',
                'ubicacion' => 40,
                'updated_at' => NULL,
            ),
            29 => 
            array (
                'created_at' => NULL,
                'id' => 35,
                'nombre' => '3',
                'ubicacion' => 40,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}