<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProveedoresTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('proveedores')->delete();
        
        \DB::table('proveedores')->insert(array (
            0 => 
            array (
                'created_at' => NULL,
                'direccion' => 'la campina',
                'email' => 'contacto@pdvsa.gob.ve',
                'id' => 1,
                'nombre' => 'PDVSA',
                'rif' => 'G-001219',
                'telefono' => '3248293',
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}