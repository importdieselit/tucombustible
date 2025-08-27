<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PersonasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('personas')->truncate(); // Limpiar la tabla antes de insertar nuevos datos
        DB::table('personas')->insert([
            ['id' => 1, 'nombre' => 'demo', 'telefono' => '0212-2615556', 'created_at' => Carbon::parse('2013-08-07'), 'updated_at' => Carbon::now()],
            ['id' => 2, 'nombre' => 'Impormotor', 'telefono' => '0212-9633570', 'created_at' => Carbon::parse('2014-03-24'), 'updated_at' => Carbon::now()],
            ['id' => 3, 'nombre' => 'Inversiones Impormotor LLS C.A.', 'telefono' => '02129633570', 'created_at' => Carbon::parse('2013-12-02'), 'updated_at' => Carbon::now()],
            ['id' => 76, 'nombre' => 'FARMATODO - CENDIS', 'telefono' => '0800 -327628636', 'created_at' => Carbon::parse('2016-01-01'), 'updated_at' => Carbon::now()],
            ['id' => 81, 'nombre' => 'Inversiones Impormotor LLS C.A. - Master', 'telefono' => '0212-7633570', 'created_at' => Carbon::parse('2016-07-06'), 'updated_at' => Carbon::now()],
            ['id' => 92, 'nombre' => 'FERMIN LONGA', 'telefono' => '021294980016', 'created_at' => Carbon::parse('2017-07-06'), 'updated_at' => Carbon::now()],
            ['id' => 101, 'nombre' => 'Excelsior Gamma', 'telefono' => '0000', 'created_at' => Carbon::parse('2019-05-29'), 'updated_at' => Carbon::now()],
            ['id' => 103, 'nombre' => 'User', 'telefono' => '0000', 'created_at' => Carbon::parse('2020-01-04'), 'updated_at' => Carbon::now()],
            ['id' => 104, 'nombre' => 'Covencaucho', 'telefono' => null, 'created_at' => Carbon::parse('2019-12-15'), 'updated_at' => Carbon::now()],
        ]);
    }
}