<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequisitoCaptacion;

class RequisitosCaptacionSeeder extends Seeder
{
    public function run()
    {
        $reqs = [
            ['tipo_cliente'=>'industrial','codigo'=>'A','descripcion'=>'RIF legalizado','obligatorio'=>true],
            ['tipo_cliente'=>'industrial','codigo'=>'B','descripcion'=>'Documento constitutivo','obligatorio'=>true],
            ['tipo_cliente'=>'industrial','codigo'=>'C','descripcion'=>'Copia del representante legal','obligatorio'=>true],
            ['tipo_cliente'=>'industrial','codigo'=>'D','descripcion'=>'Lista de equipos y tanques','obligatorio'=>true],
            ['tipo_cliente'=>'industrial','codigo'=>'E','descripcion'=>'Croquis de ubicación','obligatorio'=>true],
            ['tipo_cliente'=>'industrial','codigo'=>'F','descripcion'=>'Constancia de bomberos','obligatorio'=>true],
        ];

        foreach ($reqs as $r) RequisitoCaptacion::create($r);
    }
}
