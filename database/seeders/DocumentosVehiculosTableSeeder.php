<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocumentosVehiculosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('documentos_vehiculos')->delete();
        
        
        
    }
}