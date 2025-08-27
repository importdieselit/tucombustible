<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarcasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('marcas')->truncate();
        DB::table('marcas')->insert([
            ['id' => 1, 'marca' => 'CHEVROLET'],
            ['id' => 2, 'marca' => 'DODGE'],
            ['id' => 3, 'marca' => 'FORD'],
            ['id' => 4, 'marca' => 'MAZDA'],
            ['id' => 5, 'marca' => 'MITSUBISHI'],
            ['id' => 6, 'marca' => 'PEUGEOT'],
            ['id' => 7, 'marca' => 'TOYOTA'],
            ['id' => 8, 'marca' => 'IVECO'],
            ['id' => 9, 'marca' => 'HYUNDAI'],
            ['id' => 10, 'marca' => 'KIA'],
            ['id' => 11, 'marca' => 'DOGDE'],
            ['id' => 12, 'marca' => 'DAIHATSU'],
            ['id' => 13, 'marca' => 'HONDA'],
            ['id' => 14, 'marca' => 'JEEP'],
            ['id' => 15, 'marca' => 'HUMMER'],
            ['id' => 16, 'marca' => 'LINCOLN LIMO'],
            ['id' => 17, 'marca' => 'VOLKSWAGEN'],
            ['id' => 18, 'marca' => 'RENAULT'],
            ['id' => 19, 'marca' => 'CHERY'],
            ['id' => 20, 'marca' => 'HAIMA'],
            ['id' => 21, 'marca' => 'FIAT'],
            ['id' => 22, 'marca' => 'PANEL L300'],
            ['id' => 23, 'marca' => 'GEELY'],
            ['id' => 24, 'marca' => 'TATA'],
            ['id' => 25, 'marca' => 'SUZUKI'],
            ['id' => 26, 'marca' => 'YAMAHA'],
            ['id' => 27, 'marca' => 'D INNOCENZO'],
            ['id' => 28, 'marca' => 'REMIVECA'],
            ['id' => 29, 'marca' => 'DIDI'],
            ['id' => 30, 'marca' => 'INMECAR'],
            ['id' => 31, 'marca' => 'ORINOCO'],
            ['id' => 32, 'marca' => 'FAB. NACIONAL'],
            ['id' => 33, 'marca' => 'INDUAGA'],
            ['id' => 34, 'marca' => 'FREEWAYS'],
            ['id' => 35, 'marca' => 'LAVAL'],
            ['id' => 36, 'marca' => 'FAB. EXTRANJERA'],
            ['id' => 37, 'marca' => 'IVROCA'],
            ['id' => 38, 'marca' => 'AGAMAR'],
            ['id' => 39, 'marca' => 'BATEAS GERPLAP'],
            ['id' => 40, 'marca' => 'SIN MARCA'],
            ['id' => 41, 'marca' => 'TECTOR'],
            ['id' => 42, 'marca' => 'TOYOTA HILUX'],
            ['id' => 43, 'marca' => 'LEXUS'],
            ['id' => 44, 'marca' => 'RETOÑO'],
            ['id' => 45, 'marca' => 'PLYMOUTH'],
            ['id' => 46, 'marca' => 'JAC'],
            ['id' => 47, 'marca' => 'BMW'],
            ['id' => 48, 'marca' => 'NHR'],
            ['id' => 49, 'marca' => 'GWV'],
            ['id' => 50, 'marca' => 'YALE'],
            ['id' => 51, 'marca' => 'HYSTER'],
            ['id' => 52, 'marca' => 'FABRICACION NAC'],
            ['id' => 53, 'marca' => 'BATEAS OCCIDENTE'],
            ['id' => 54, 'marca' => 'STRICK'],
            ['id' => 55, 'marca' => 'FURGO ESTACAS'],
            ['id' => 56, 'marca' => 'Eurocargo'],
            ['id' => 57, 'marca' => 'CITROEN'],
            ['id' => 58, 'marca' => 'ENCAVA'],
            ['id' => 59, 'marca' => 'prueba'],
        ]);
    }
}