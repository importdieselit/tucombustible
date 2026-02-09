<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TipoReporteTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tipo_reporte')->delete();
        
        \DB::table('tipo_reporte')->insert(array (
            0 => 
            array (
                'activo' => 1,
                'created_at' => NULL,
                'descripcion' => 'Falla inherente a alguna unidad y si funcionamiento',
                'id' => 1,
                'tipo' => 'Flotas y Equipos',
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'activo' => 1,
                'created_at' => NULL,
                'descripcion' => 'indicar cualquier defecto encontrado en algun equipo o insumo utilizado',
                'id' => 2,
                'tipo' => 'conductor',
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'activo' => 1,
                'created_at' => NULL,
                'descripcion' => 'espacios, condicion de los espacios, seguridad industrial, etc',
                'id' => 3,
                'tipo' => 'Patio y Talleres',
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'activo' => 1,
                'created_at' => NULL,
                'descripcion' => 'dfd',
                'id' => 4,
                'tipo' => 'Accidente',
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}