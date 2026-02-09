<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TrabajosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('trabajos')->delete();
        
        
        
    }
}