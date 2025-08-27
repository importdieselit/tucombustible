<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('modelos')->truncate();
        DB::table('modelos')->insert([
            ['id' => 1, 'id_marca' => 1, 'modelo' => 'SILVERADO LS 4X2', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 2, 'id_marca' => 2, 'modelo' => 'CALIBER AUTOMATICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 3, 'id_marca' => 3, 'modelo' => 'FIESTA 1.6 AUTOMATICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 4, 'id_marca' => 3, 'modelo' => 'EXPLORER XLT 4X2', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 5, 'id_marca' => 3, 'modelo' => 'EXPLORER LIMITED 4X4', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 6, 'id_marca' => 4, 'modelo' => 'BT-50 2.2 4X2', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 7, 'id_marca' => 5, 'modelo' => 'LANCER 1.6 AUTOMATICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 8, 'id_marca' => 6, 'modelo' => '207 COMPACT 1.4 SINCRONICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 9, 'id_marca' => 6, 'modelo' => 'PARTNER 1.4 SINCRONICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 10, 'id_marca' => 7, 'modelo' => 'COROLLA 1.6 AUTOMATICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 11, 'id_marca' => 7, 'modelo' => 'FORTUNER 4X4 (BLINDADA)', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 12, 'id_marca' => 7, 'modelo' => 'COROLLA 1.8', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 13, 'id_marca' => 7, 'modelo' => 'COROLLA 1.8 SINCRONICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 14, 'id_marca' => 7, 'modelo' => 'MERU', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 15, 'id_marca' => 1, 'modelo' => 'OPTRA', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 16, 'id_marca' => 1, 'modelo' => 'LUV D MAX', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 17, 'id_marca' => 1, 'modelo' => 'AVEO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 18, 'id_marca' => 1, 'modelo' => 'SPARK', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 19, 'id_marca' => 1, 'modelo' => 'AVEO 2 Ptas', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 20, 'id_marca' => 1, 'modelo' => 'AVEO LT', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 21, 'id_marca' => 8, 'modelo' => 'STRALIS', 'vol' => 250, 'oil' => 45, 'fuel' => 400],
            ['id' => 22, 'id_marca' => 8, 'modelo' => 'TECTOR', 'vol' => 200, 'oil' => 35, 'fuel' => 350],
            ['id' => 23, 'id_marca' => 3, 'modelo' => 'FIESTA', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 24, 'id_marca' => 9, 'modelo' => 'GETZ', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 25, 'id_marca' => 9, 'modelo' => 'ELANTRA', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 26, 'id_marca' => 10, 'modelo' => 'RIO LS', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 27, 'id_marca' => 10, 'modelo' => 'RIO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 28, 'id_marca' => 5, 'modelo' => 'LANCER', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 29, 'id_marca' => 5, 'modelo' => 'LANCER GLX- 1.6L CVT', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 30, 'id_marca' => 7, 'modelo' => 'PICK-UP', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 31, 'id_marca' => 7, 'modelo' => 'HILUX 4X4 M/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 32, 'id_marca' => 7, 'modelo' => 'COROLLA', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 33, 'id_marca' => 7, 'modelo' => 'KAVAK', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 34, 'id_marca' => 7, 'modelo' => 'HILUX', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 35, 'id_marca' => 7, 'modelo' => 'FORTUNER', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 36, 'id_marca' => 7, 'modelo' => '4RUNNER', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 37, 'id_marca' => 7, 'modelo' => 'PREVIA', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 38, 'id_marca' => 7, 'modelo' => 'BELTA', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 39, 'id_marca' => 7, 'modelo' => 'COROLLA GLI', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 40, 'id_marca' => 6, 'modelo' => '207', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 41, 'id_marca' => 6, 'modelo' => '307', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 42, 'id_marca' => 3, 'modelo' => 'EXPLORER', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 43, 'id_marca' => 11, 'modelo' => 'FORZA', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 44, 'id_marca' => 1, 'modelo' => 'SILVERADO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 45, 'id_marca' => 7, 'modelo' => 'YARIS', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 46, 'id_marca' => 6, 'modelo' => '207 COMPACT 1.6 AUTOMATICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 47, 'id_marca' => 4, 'modelo' => 'BT-50 2.2 4X4', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 48, 'id_marca' => 5, 'modelo' => 'LANCER 2.0 AUTOMATICO', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 49, 'id_marca' => 7, 'modelo' => '4RUNNER LTD 4X4', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 50, 'id_marca' => 1, 'modelo' => 'AVEO LT A/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 51, 'id_marca' => 7, 'modelo' => 'CAMRY A/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 52, 'id_marca' => 7, 'modelo' => 'COROLLA 1.6', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 53, 'id_marca' => 7, 'modelo' => 'COROLLA 1.8 A/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 54, 'id_marca' => 7, 'modelo' => 'COROLLA XEi 1,8 AUT.', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 55, 'id_marca' => 9, 'modelo' => 'ELANTRA 1.6L A/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 56, 'id_marca' => 7, 'modelo' => 'FORTUNER 7P', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 57, 'id_marca' => 9, 'modelo' => 'GETZ GLS 1.6 A/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 58, 'id_marca' => 9, 'modelo' => 'GETZ GLS 1.6 M/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 59, 'id_marca' => 5, 'modelo' => 'LANCER GLX', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 60, 'id_marca' => 7, 'modelo' => 'LAND CRUISER', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 61, 'id_marca' => 1, 'modelo' => 'LUV DMAX 4X2', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 62, 'id_marca' => 1, 'modelo' => 'LUV DMAX 4X4', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 63, 'id_marca' => 7, 'modelo' => 'MERU MT', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 64, 'id_marca' => 13, 'modelo' => 'ODISSEY', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 65, 'id_marca' => 1, 'modelo' => 'OPTRA ADVANCE', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 66, 'id_marca' => 1, 'modelo' => 'OPTRA ADVANCE M/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 67, 'id_marca' => 1, 'modelo' => 'OPTRA ADVANCE T/A', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 68, 'id_marca' => 1, 'modelo' => 'OPTRA DESING', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 69, 'id_marca' => 1, 'modelo' => 'OPTRA M/T', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 70, 'id_marca' => 7, 'modelo' => 'YARIS B', 'vol' => null, 'oil' => null, 'fuel' => 0],
            ['id' => 71, 'id_marca' => 58, 'modelo' => 'ENTREGA INMEDIATA', 'vol' => 250, 'oil' => 40, 'fuel' => 0],
        ]);
    }
}