<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ModelosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('modelos')->delete();
        
        \DB::table('modelos')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'id' => 1,
                'id_marca' => 8,
                'modelo' => 'STRALIS',
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'created_at' => NULL,
                'id' => 2,
                'id_marca' => 46,
                'modelo' => 'HCF 1061K',
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'created_at' => NULL,
                'id' => 3,
                'id_marca' => 8,
                'modelo' => 'TECTOR',
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'created_at' => NULL,
                'id' => 4,
                'id_marca' => 31,
                'modelo' => 'A5200',
                'updated_at' => NULL,
            ),
            4 => 
            array (
                'created_at' => NULL,
                'id' => 5,
                'id_marca' => 3,
                'modelo' => 'CARGO',
                'updated_at' => NULL,
            ),
            5 => 
            array (
                'created_at' => NULL,
                'id' => 6,
                'id_marca' => 60,
                'modelo' => 'CISTERNA',
                'updated_at' => NULL,
            ),
            6 => 
            array (
                'created_at' => NULL,
                'id' => 7,
                'id_marca' => 1,
                'modelo' => 'NPR',
                'updated_at' => NULL,
            ),
            7 => 
            array (
                'created_at' => NULL,
                'id' => 8,
                'id_marca' => 61,
                'modelo' => 'CH613HDT',
                'updated_at' => NULL,
            ),
            8 => 
            array (
                'created_at' => NULL,
                'id' => 9,
                'id_marca' => 5,
                'modelo' => 'FM618',
                'updated_at' => NULL,
            ),
            9 => 
            array (
                'created_at' => NULL,
                'id' => 10,
                'id_marca' => 7,
                'modelo' => 'HILUX',
                'updated_at' => NULL,
            ),
            10 => 
            array (
                'created_at' => NULL,
                'id' => 11,
                'id_marca' => 62,
                'modelo' => 'NPR',
                'updated_at' => NULL,
            ),
            11 => 
            array (
                'created_at' => NULL,
                'id' => 12,
                'id_marca' => 46,
                'modelo' => 'CHUTO',
                'updated_at' => NULL,
            ),
            12 => 
            array (
                'created_at' => NULL,
                'id' => 13,
                'id_marca' => 63,
                'modelo' => 'CILINDRO',
                'updated_at' => NULL,
            ),
            13 => 
            array (
                'created_at' => NULL,
                'id' => 14,
                'id_marca' => 64,
                'modelo' => 'T300',
                'updated_at' => NULL,
            ),
            14 => 
            array (
                'created_at' => NULL,
                'id' => 15,
                'id_marca' => 65,
                'modelo' => 'VHD',
                'updated_at' => NULL,
            ),
            15 => 
            array (
                'created_at' => NULL,
                'id' => 16,
                'id_marca' => 5,
                'modelo' => 'FUSO',
                'updated_at' => NULL,
            ),
            16 => 
            array (
                'created_at' => NULL,
                'id' => 17,
                'id_marca' => 66,
                'modelo' => 'CASCADIA',
                'updated_at' => NULL,
            ),
            17 => 
            array (
                'created_at' => NULL,
                'id' => 18,
                'id_marca' => 67,
                'modelo' => '2500',
                'updated_at' => NULL,
            ),
            18 => 
            array (
                'created_at' => NULL,
                'id' => 19,
                'id_marca' => 8,
                'modelo' => 'EUROCARGO',
                'updated_at' => NULL,
            ),
            19 => 
            array (
                'created_at' => NULL,
                'id' => 20,
                'id_marca' => 8,
                'modelo' => 'FREIGHTLINER',
                'updated_at' => NULL,
            ),
            20 => 
            array (
                'created_at' => NULL,
                'id' => 21,
                'id_marca' => 8,
                'modelo' => 'EUROTECH',
                'updated_at' => NULL,
            ),
            21 => 
            array (
                'created_at' => NULL,
                'id' => 22,
                'id_marca' => 4,
                'modelo' => 'BT-50',
                'updated_at' => NULL,
            ),
            22 => 
            array (
                'created_at' => NULL,
                'id' => 23,
                'id_marca' => 14,
                'modelo' => 'CHEROKEE',
                'updated_at' => NULL,
            ),
            23 => 
            array (
                'created_at' => NULL,
                'id' => 24,
                'id_marca' => 1,
                'modelo' => 'CORSA',
                'updated_at' => NULL,
            ),
            24 => 
            array (
                'created_at' => NULL,
                'id' => 25,
                'id_marca' => 8,
                'modelo' => 'EUROTRAKKER',
                'updated_at' => NULL,
            ),
            25 => 
            array (
                'created_at' => NULL,
                'id' => 26,
                'id_marca' => 31,
                'modelo' => 'CISTERNA',
                'updated_at' => NULL,
            ),
            26 => 
            array (
                'created_at' => NULL,
                'id' => 27,
                'id_marca' => 68,
                'modelo' => 'CISTERNA',
                'updated_at' => NULL,
            ),
            27 => 
            array (
                'created_at' => NULL,
                'id' => 28,
                'id_marca' => 61,
                'modelo' => 'R612SXHD',
                'updated_at' => NULL,
            ),
            28 => 
            array (
                'created_at' => NULL,
                'id' => 29,
                'id_marca' => 69,
                'modelo' => 'DUOLIKA 5T',
                'updated_at' => NULL,
            ),
            29 => 
            array (
                'created_at' => NULL,
                'id' => 30,
                'id_marca' => 70,
                'modelo' => 'CISTERNA',
                'updated_at' => NULL,
            ),
            30 => 
            array (
                'created_at' => NULL,
                'id' => 31,
                'id_marca' => 69,
                'modelo' => 'DFA1045BA01',
                'updated_at' => NULL,
            ),
            31 => 
            array (
                'created_at' => NULL,
                'id' => 32,
                'id_marca' => 71,
                'modelo' => '1999',
                'updated_at' => NULL,
            ),
            32 => 
            array (
                'created_at' => NULL,
                'id' => 33,
                'id_marca' => 72,
                'modelo' => '1972',
                'updated_at' => NULL,
            ),
            33 => 
            array (
                'created_at' => NULL,
                'id' => 34,
                'id_marca' => 73,
                'modelo' => '2005',
                'updated_at' => NULL,
            ),
            34 => 
            array (
                'created_at' => NULL,
                'id' => 35,
                'id_marca' => 8,
                'modelo' => '2007',
                'updated_at' => NULL,
            ),
            35 => 
            array (
                'created_at' => NULL,
                'id' => 36,
                'id_marca' => 35,
                'modelo' => '2024',
                'updated_at' => NULL,
            ),
            36 => 
            array (
                'created_at' => NULL,
                'id' => 37,
                'id_marca' => 8,
                'modelo' => '740E42TZ',
                'updated_at' => NULL,
            ),
            37 => 
            array (
                'created_at' => NULL,
                'id' => 38,
                'id_marca' => 72,
                'modelo' => '1978',
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}