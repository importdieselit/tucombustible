<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HistorialMantenimientoTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('historial_mantenimiento')->delete();
        
        
        
    }
}