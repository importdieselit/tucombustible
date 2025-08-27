<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('perfiles')->truncate();
        DB::table('perfiles')->insert([
            [
                'id' => 1,
                'nombre' => 'superadmin',
                'descripcion' => 'tiene todos los permisos',
                'created_at' => Carbon::parse('2020-05-18 10:14:00'),
                'updated_at' => Carbon::parse('2020-05-18 10:14:00')
            ],
            [
                'id' => 2,
                'nombre' => 'administrador',
                'descripcion' => 'administrador de sistema',
                'created_at' => Carbon::parse('2020-05-18 10:14:00'),
                'updated_at' => Carbon::parse('2020-05-18 10:14:00')
            ],
            [
                'id' => 3,
                'nombre' => 'cliente',
                'descripcion' => 'cliente del servicio',
                'created_at' => Carbon::parse('2020-05-18 10:14:00'),
                'updated_at' => Carbon::parse('2020-05-18 10:14:00')
            ],
            [
                'id' => 4,
                'nombre' => 'conductor',
                'descripcion' => 'chofer de vehiculo',
                'created_at' => Carbon::parse('2020-05-18 10:14:00'),
                'updated_at' => Carbon::parse('2020-05-18 10:14:00')
            ],
            [
                'id' => 5,
                'nombre' => 'mecanico',
                'descripcion' => 'mecanico de turno',
                'created_at' => Carbon::parse('2020-05-18 10:14:00'),
                'updated_at' => Carbon::parse('2020-05-18 10:14:00')
            ],
        ]);
    }
}