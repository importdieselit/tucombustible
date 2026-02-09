<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventarioOldTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventario_old')->delete();
        
        
        
    }
}