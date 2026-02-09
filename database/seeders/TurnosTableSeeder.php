<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TurnosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('turnos')->delete();
        
        
        
    }
}