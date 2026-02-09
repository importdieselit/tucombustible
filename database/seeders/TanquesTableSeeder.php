<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TanquesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tanques')->delete();
        
        
        
    }
}