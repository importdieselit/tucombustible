<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametrosLegalesSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            [
                'clave' => 'VACACIONES_DIAS_BASE',
                'valor' => '15',
                'tipo_dato' => 'numero',
                'descripcion' => 'Días hábiles de disfrute para el primer año de servicio según LOTTT'
            ],
            [
                'clave' => 'VACACIONES_TOPE_MAXIMO',
                'valor' => '30',
                'tipo_dato' => 'numero',
                'descripcion' => 'Tope máximo de días hábiles de vacaciones acumulables'
            ],
            [
                'clave' => 'SALARIO_MINIMO_BASE',
                'valor' => '130.00',
                'tipo_dato' => 'moneda',
                'descripcion' => 'Salario mínimo base legal vigente'
            ],
        ];

        DB::table('parametros_legales')->insert($parametros);
    }
}