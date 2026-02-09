<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EquiposClientesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('equipos_clientes')->delete();
        
        
        
    }
}