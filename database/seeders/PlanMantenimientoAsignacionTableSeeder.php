<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlanMantenimientoAsignacionTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('plan_mantenimiento_asignacion')->delete();
        
        
        
    }
}