<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AutosTiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('autos_tipos')->truncate();
        DB::table('autos_tipos')->insert([
            ['id_autos_tipo' => 1, 'tipo' => 'Atv', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 2, 'tipo' => 'Caravan', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 3, 'tipo' => 'Cargo', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 4, 'tipo' => 'Convertible', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 5, 'tipo' => 'Coupe', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 6, 'tipo' => 'Crossover', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 7, 'tipo' => 'Golfcar', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 8, 'tipo' => 'Hatchback', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 9, 'tipo' => 'Hatchback 4 Ptas', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 10, 'tipo' => 'Híbrido', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 11, 'tipo' => 'Jeep', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 12, 'tipo' => 'Microvan', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 13, 'tipo' => 'Minicargo', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 14, 'tipo' => 'Minisuv', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 15, 'tipo' => 'Minivan', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 16, 'tipo' => 'Motocicleta A', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 17, 'tipo' => 'Motocicleta B', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 18, 'tipo' => 'Motocicleta C', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 19, 'tipo' => 'Pickup', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 20, 'tipo' => 'Pickup Doble Cabina', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 21, 'tipo' => 'Sedan', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 22, 'tipo' => 'Shuttle', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 23, 'tipo' => 'Deportivo', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 24, 'tipo' => 'Camioneta / Suv', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 25, 'tipo' => 'Camión', 'esquema' => 2, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 26, 'tipo' => 'Van', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 27, 'tipo' => 'Wagon', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 28, 'tipo' => 'Camión Sencillo', 'esquema' => 2, 'vol' => 35, 'trailer' => false],
            ['id_autos_tipo' => 29, 'tipo' => 'Camión Toronto', 'esquema' => 3, 'vol' => 35, 'trailer' => false],
            ['id_autos_tipo' => 30, 'tipo' => 'Chuto Sencillo', 'esquema' => 2, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 31, 'tipo' => 'Chuto Toronto', 'esquema' => 3, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 32, 'tipo' => 'Furgón 1 Eje', 'esquema' => 4, 'vol' => 45, 'trailer' => true],
            ['id_autos_tipo' => 33, 'tipo' => 'Furgón 2 Ejes', 'esquema' => 5, 'vol' => 45, 'trailer' => true],
            ['id_autos_tipo' => 34, 'tipo' => 'Furgón 3 Ejes', 'esquema' => 6, 'vol' => 45, 'trailer' => true],
            ['id_autos_tipo' => 35, 'tipo' => 'Trailer', 'esquema' => 6, 'vol' => 45, 'trailer' => true],
            ['id_autos_tipo' => 36, 'tipo' => 'FURGON CHASIS LARGO', 'esquema' => null, 'vol' => null, 'trailer' => true],
            ['id_autos_tipo' => 37, 'tipo' => 'SUPER DUTY', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 38, 'tipo' => 'CAVA', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 39, 'tipo' => 'CHASIS LARGO', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 40, 'tipo' => 'AMBULANCIA', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 41, 'tipo' => 'CAMIONETA', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 42, 'tipo' => 'TANQUE', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 43, 'tipo' => 'CAVAS SECAS', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 44, 'tipo' => 'PORTACONTENEDOR', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 45, 'tipo' => 'SPORT WAGON', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 46, 'tipo' => 'HATCHBACK 4 PUERTAS', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 47, 'tipo' => 'SUV', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 48, 'tipo' => 'N/A', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 49, 'tipo' => 'MONTACARGA', 'esquema' => 1, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 50, 'tipo' => 'BATEA', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 51, 'tipo' => 'LOW-BOY', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 52, 'tipo' => 'ESTACAS', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 53, 'tipo' => 'CHASIS', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 54, 'tipo' => 'CASILLERO', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 55, 'tipo' => 'AUTOMOVIL', 'esquema' => null, 'vol' => null, 'trailer' => false],
            ['id_autos_tipo' => 56, 'tipo' => 'STATION WAGON', 'esquema' => null, 'vol' => null, 'trailer' => false],
        ]);
    }
}