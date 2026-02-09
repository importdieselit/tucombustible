<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NominacionesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('nominaciones')->delete();
        
        
        
    }
}