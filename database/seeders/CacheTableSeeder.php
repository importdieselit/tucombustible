<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CacheTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cache')->delete();
        
        \DB::table('cache')->insert(array (
            0 => 
            array (
                'expiration' => 1753851940,
                'key' => 'tucombustible_cache_super@example.com|127.0.0.1',
                'value' => 'i:1;',
            ),
            1 => 
            array (
                'expiration' => 1753851940,
                'key' => 'tucombustible_cache_super@example.com|127.0.0.1:timer',
                'value' => 'i:1753851940;',
            ),
        ));
        
        
    }
}