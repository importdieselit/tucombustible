<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RequisitosCaptacionTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('requisitos_captacion')->delete();
    
        $requisitos = [
            // Solo para el Cliente Padre
            ['id' => 1, 'codigo' => 'RIF_LEG', 'descripcion' => 'RIF legalizado', 'tipo_cliente' => 'padre'],
            ['id' => 2, 'codigo' => 'DOC_CONST', 'descripcion' => 'Documento constitutivo', 'tipo_cliente' => 'padre'],
            ['id' => 3, 'codigo' => 'CED_REP', 'descripcion' => 'Copia del representante legal', 'tipo_cliente' => 'padre'],
        
            // Para ambos (Padre y Sucursales)
            ['id' => 4, 'codigo' => 'LIST_EQ', 'descripcion' => 'Lista de equipos y tanques', 'tipo_cliente' => 'ambos'],
            ['id' => 5, 'codigo' => 'CROQUIS', 'descripcion' => 'Croquis de ubicación', 'tipo_cliente' => 'ambos'],
            ['id' => 6, 'codigo' => 'BOMBEROS', 'descripcion' => 'Constancia de bomberos', 'tipo_cliente' => 'ambos'],
        ];

        foreach ($requisitos as $req) {
            \DB::table('requisitos_captacion')->insert(array_merge($req, [
                'obligatorio' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}