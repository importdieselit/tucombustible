<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TemparioCategoriasTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tempario_categorias')->delete();
        
        \DB::table('tempario_categorias')->insert(array (
            0 => 
            array (
                'categoria' => 'MANTENIMIENTO',
                'codigo' => '1',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 22,
                'id_usuario' => 76,
            ),
            1 => 
            array (
                'categoria' => 'REPARACION',
                'codigo' => '2',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 23,
                'id_usuario' => 76,
            ),
            2 => 
            array (
                'categoria' => 'SERVICIO',
                'codigo' => '3',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 24,
                'id_usuario' => 76,
            ),
            3 => 
            array (
                'categoria' => 'REVISION',
                'codigo' => '4',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 25,
                'id_usuario' => 76,
            ),
            4 => 
            array (
                'categoria' => 'latoneria y pintura',
                'codigo' => '5',
                'costo_mo' => 0.0,
                'id_tempario_categoria' => 42,
                'id_usuario' => 88,
            ),
            5 => 
            array (
                'categoria' => 'Rutinas de mantenimiento',
                'codigo' => 'm2',
                'costo_mo' => 0.0,
                'id_tempario_categoria' => 43,
                'id_usuario' => 88,
            ),
            6 => 
            array (
                'categoria' => 'ELECTRICIDAD',
                'codigo' => '5',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 44,
                'id_usuario' => 76,
            ),
            7 => 
            array (
                'categoria' => 'MOTOR',
                'codigo' => '6',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 45,
                'id_usuario' => 76,
            ),
            8 => 
            array (
                'categoria' => 'ELEVADOR',
                'codigo' => '7',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 46,
                'id_usuario' => 76,
            ),
            9 => 
            array (
                'categoria' => 'AUXILIO VIAL',
                'codigo' => '8',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 47,
                'id_usuario' => 76,
            ),
            10 => 
            array (
                'categoria' => 'INYECTORES',
                'codigo' => '9',
                'costo_mo' => 20.0,
                'id_tempario_categoria' => 48,
                'id_usuario' => 76,
            ),
            11 => 
            array (
                'categoria' => 'Carros y Camionetas',
                'codigo' => 'T-1',
                'costo_mo' => 5.0,
                'id_tempario_categoria' => 70,
                'id_usuario' => 104,
            ),
            12 => 
            array (
                'categoria' => 'Montacargas',
                'codigo' => 'T-2',
                'costo_mo' => 5.0,
                'id_tempario_categoria' => 71,
                'id_usuario' => 104,
            ),
            13 => 
            array (
                'categoria' => 'Camiones y Chutos',
                'codigo' => 'T-3',
                'costo_mo' => 5.0,
                'id_tempario_categoria' => 72,
                'id_usuario' => 104,
            ),
            14 => 
            array (
                'categoria' => 'INYECCION',
                'codigo' => '',
                'costo_mo' => 0.0,
                'id_tempario_categoria' => 73,
                'id_usuario' => 76,
            ),
        ));
        
        
    }
}