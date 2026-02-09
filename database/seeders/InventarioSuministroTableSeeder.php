<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventarioSuministroTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventario_suministro')->delete();
        
        \DB::table('inventario_suministro')->insert(array (
            0 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-09-01 11:01:45',
                'destino' => NULL,
                'estatus' => 1,
                'fecha_in' => NULL,
                'id_auto' => 15,
                'id_emisor' => 1,
                'id_inventario' => 1881,
                'id_inventario_suministro' => 18,
                'id_orden' => 23,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-09-01 11:30:31',
            ),
            1 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 2,
                'created_at' => '2025-09-01 11:01:53',
                'destino' => NULL,
                'estatus' => 5,
                'fecha_in' => NULL,
                'id_auto' => 15,
                'id_emisor' => 1,
                'id_inventario' => 1047,
                'id_inventario_suministro' => 19,
                'id_orden' => 23,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-09-01 14:49:30',
            ),
            2 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 4,
                'created_at' => '2025-09-01 11:05:13',
                'destino' => NULL,
                'estatus' => 1,
                'fecha_in' => NULL,
                'id_auto' => 15,
                'id_emisor' => 1,
                'id_inventario' => 2148,
                'id_inventario_suministro' => 21,
                'id_orden' => 23,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-09-01 14:49:34',
            ),
            3 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 2,
                'created_at' => '2025-09-01 14:53:08',
                'destino' => NULL,
                'estatus' => 1,
                'fecha_in' => NULL,
                'id_auto' => 20,
                'id_emisor' => 1,
                'id_inventario' => 2305,
                'id_inventario_suministro' => 22,
                'id_orden' => 24,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-09-01 14:54:51',
            ),
            4 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-09-01 14:53:28',
                'destino' => NULL,
                'estatus' => 1,
                'fecha_in' => NULL,
                'id_auto' => 20,
                'id_emisor' => 1,
                'id_inventario' => 4431,
                'id_inventario_suministro' => 23,
                'id_orden' => 24,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-09-01 14:54:48',
            ),
            5 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-10-20 16:38:48',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 42,
                'id_emisor' => 1,
                'id_inventario' => 5063,
                'id_inventario_suministro' => 25,
                'id_orden' => 25,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-10-20 16:38:48',
            ),
            6 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-11-03 12:31:04',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 45,
                'id_emisor' => 1,
                'id_inventario' => 1920,
                'id_inventario_suministro' => 26,
                'id_orden' => 53,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-11-03 12:31:04',
            ),
            7 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 2,
                'created_at' => '2025-11-03 21:59:42',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 15,
                'id_emisor' => 477,
                'id_inventario' => 5285,
                'id_inventario_suministro' => 27,
                'id_orden' => 35,
                'id_usuario' => 477,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-11-03 21:59:42',
            ),
            8 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 8,
                'created_at' => '2025-11-03 22:01:19',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 22,
                'id_emisor' => 477,
                'id_inventario' => 5372,
                'id_inventario_suministro' => 28,
                'id_orden' => 36,
                'id_usuario' => 477,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-11-03 22:01:40',
            ),
            9 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-11-06 11:48:45',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 46,
                'id_emisor' => 477,
                'id_inventario' => 4098,
                'id_inventario_suministro' => 29,
                'id_orden' => 58,
                'id_usuario' => 477,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-11-06 11:48:45',
            ),
            10 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-11-06 14:42:01',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 46,
                'id_emisor' => 1,
                'id_inventario' => 5319,
                'id_inventario_suministro' => 30,
                'id_orden' => 58,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-11-06 14:42:01',
            ),
            11 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-11-11 11:18:05',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 30,
                'id_emisor' => 1,
                'id_inventario' => 5102,
                'id_inventario_suministro' => 31,
                'id_orden' => 64,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-11-11 11:18:05',
            ),
            12 => 
            array (
                'anulacion' => NULL,
                'cantidad' => 1,
                'created_at' => '2025-11-14 12:29:35',
                'destino' => NULL,
                'estatus' => 2,
                'fecha_in' => NULL,
                'id_auto' => 22,
                'id_emisor' => 1,
                'id_inventario' => 1985,
                'id_inventario_suministro' => 32,
                'id_orden' => 66,
                'id_usuario' => 1,
                'kilometraje' => NULL,
                'servicio' => NULL,
                'updated_at' => '2025-11-14 12:29:35',
            ),
        ));
        
        
    }
}