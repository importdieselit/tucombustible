<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoCombustibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tipos = [
            ['nombre' => 'Diesel', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'MGO', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tipos_combustible')->insert($tipos);
    }
}
