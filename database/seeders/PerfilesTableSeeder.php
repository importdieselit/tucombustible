<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PerfilesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('perfiles')->delete();
        
        \DB::table('perfiles')->insert(array (
            0 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => 'tiene todos los permisos',
                'id' => 1,
                'nombre' => 'superadmin',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            1 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => 'administrador de sistema',
                'id' => 2,
                'nombre' => 'administrador Sistema',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            2 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => 'cliente del servicio',
                'id' => 3,
                'nombre' => 'cliente',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            3 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => 'chofer de vehiculo',
                'id' => 4,
                'nombre' => 'Conductor',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            4 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => 'mecanico de turno',
                'id' => 5,
                'nombre' => 'Mecanico',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            5 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 6,
                'nombre' => 'Operador de Patio',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            6 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 7,
                'nombre' => 'Auxiliar Administrativo',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            7 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 8,
                'nombre' => 'Coordinacion Administrativa',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            8 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 9,
                'nombre' => 'Ayudante de Transportista',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            9 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 10,
                'nombre' => 'Coord Facturacion',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            10 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 11,
                'nombre' => 'Coord. Operaciones',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            11 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 12,
                'nombre' => 'Jefe de Flota',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            12 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 13,
                'nombre' => 'Jefe de Seguridad',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            13 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 14,
                'nombre' => 'R.R.H.H.',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            14 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 15,
                'nombre' => 'Servicios Generales',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            15 => 
            array (
                'created_at' => '2020-05-18 10:14:00',
                'descripcion' => NULL,
                'id' => 16,
                'nombre' => 'Jefe de Seguridad Industrial',
                'updated_at' => '2020-05-18 10:14:00',
            ),
            16 => 
            array (
                'created_at' => NULL,
                'descripcion' => NULL,
                'id' => 17,
                'nombre' => 'Tesoreria',
                'updated_at' => NULL,
            ),
            17 => 
            array (
                'created_at' => NULL,
                'descripcion' => NULL,
                'id' => 18,
                'nombre' => 'Ventas',
                'updated_at' => NULL,
            ),
            18 => 
            array (
                'created_at' => '2025-12-09 10:01:26',
                'descripcion' => NULL,
                'id' => 19,
                'nombre' => 'otro perfil',
                'updated_at' => '2025-12-09 10:01:26',
            ),
        ));
        
        
    }
}