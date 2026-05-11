<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SedesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sedes = [
            [
                'nombre'               => 'Sede Caracas',
                'estado_id'            => 10,
                'ciudad_id'            => 52,
                'direccion_especifica' => 'Av. Principal de Boleíta, Galpón 1',
                'estatus'              => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'nombre'               => 'Sede Puerto Cabello',
                'estado_id'            => 7, 
                'ciudad_id'            => 39,
                'direccion_especifica' => 'Zona Industrial de Puerto Cabello, Calle 32',
                'estatus'              => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'nombre'               => 'Sede Guaraguao',
                'estado_id'            => 2, 
                'ciudad_id'            => 6,
                'direccion_especifica' => 'Avenida Panteón de Guaraguao, Galpón 12',
                'estatus'              => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'nombre'               => 'Sede La Guaira',
                'estado_id'            => 22, 
                'ciudad_id'            => 121,
                'direccion_especifica' => 'Zona Costera de La Guaira, Calle Los Peñones, Galpón 5',
                'estatus'              => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'nombre'               => 'Sede Maracaibo',
                'estado_id'            => 24, 
                'ciudad_id'            => 130,
                'direccion_especifica' => 'Avenida 5 de Julio, Zona Industrial de Maracaibo, Galpón 3',
                'estatus'              => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ];

        DB::table('sedes')->insert($sedes);
    }
}
