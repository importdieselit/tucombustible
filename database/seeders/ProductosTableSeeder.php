<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productos')->delete();
        
        \DB::table('productos')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'descripcion' => 'Maritime Gas Oil',
                'id' => 1,
                'nombre' => 'M.G.O.',
                'precio' => '2',
                'stock' => '-46300.00',
                'stock_minimo' => '500.00',
                'updated_at' => '2026-01-19 12:35:16',
            ),
            1 => 
            array (
                'created_at' => NULL,
                'descripcion' => 'DIESEL INDUSTRIAL',
                'id' => 2,
                'nombre' => 'DIESEL',
                'precio' => '1',
                'stock' => '-730650.00',
                'stock_minimo' => '500.00',
                'updated_at' => '2026-01-20 16:56:03',
            ),
            2 => 
            array (
                'created_at' => NULL,
                'descripcion' => 'Gasolina',
                'id' => 3,
                'nombre' => 'Gasolina',
                'precio' => '0',
                'stock' => '1200.00',
                'stock_minimo' => '100.00',
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}