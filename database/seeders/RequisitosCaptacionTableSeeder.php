<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RequisitosCaptacionTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('requisitos_captacion')->delete();
        
        \DB::table('requisitos_captacion')->insert(array (
            0 => 
            array (
                'codigo' => 'A',
                'created_at' => '2025-11-28 15:50:08',
                'descripcion' => 'RIF legalizado',
                'id' => 1,
                'obligatorio' => 1,
                'tipo_cliente' => 'industrial',
                'updated_at' => '2025-11-28 15:50:08',
            ),
            1 => 
            array (
                'codigo' => 'B',
                'created_at' => '2025-11-28 15:50:08',
                'descripcion' => 'Documento constitutivo',
                'id' => 2,
                'obligatorio' => 1,
                'tipo_cliente' => 'industrial',
                'updated_at' => '2025-11-28 15:50:08',
            ),
            2 => 
            array (
                'codigo' => 'C',
                'created_at' => '2025-11-28 15:50:08',
                'descripcion' => 'Copia del representante legal',
                'id' => 3,
                'obligatorio' => 1,
                'tipo_cliente' => 'industrial',
                'updated_at' => '2025-11-28 15:50:08',
            ),
            3 => 
            array (
                'codigo' => 'D',
                'created_at' => '2025-11-28 15:50:08',
                'descripcion' => 'Lista de equipos y tanques',
                'id' => 4,
                'obligatorio' => 1,
                'tipo_cliente' => 'industrial',
                'updated_at' => '2025-11-28 15:50:08',
            ),
            4 => 
            array (
                'codigo' => 'E',
                'created_at' => '2025-11-28 15:50:08',
                'descripcion' => 'Croquis de ubicación',
                'id' => 5,
                'obligatorio' => 1,
                'tipo_cliente' => 'industrial',
                'updated_at' => '2025-11-28 15:50:08',
            ),
            5 => 
            array (
                'codigo' => 'F',
                'created_at' => '2025-11-28 15:50:08',
                'descripcion' => 'Constancia de bomberos',
                'id' => 6,
                'obligatorio' => 1,
                'tipo_cliente' => 'industrial',
                'updated_at' => '2025-11-28 15:50:08',
            ),
        ));
        
        
    }
}