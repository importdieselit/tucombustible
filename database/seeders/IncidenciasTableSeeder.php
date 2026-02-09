<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IncidenciasTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('incidencias')->delete();
        
        \DB::table('incidencias')->insert(array (
            0 => 
            array (
                'conductor_id' => 473,
                'created_at' => '2025-11-20 12:38:19',
                'descripcion' => 'Prueba de aplicacion',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => 'incidencias/1763653099_691f35eb666af.jpg',
                'id' => 1,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'otro',
                'titulo' => 'descaste de caucho',
                'ubicacion' => 'boleita',
                'updated_at' => '2025-11-20 12:38:19',
                'vehiculo_id' => NULL,
            ),
            1 => 
            array (
                'conductor_id' => 473,
                'created_at' => '2025-11-20 12:53:19',
                'descripcion' => 'Pruebas de reporte de incidencias',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => 'incidencias/1763653999_691f396f3e235.jpg',
                'id' => 2,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'accidente',
                'titulo' => 'caucho espichado',
                'ubicacion' => 'boleita',
                'updated_at' => '2025-11-20 12:53:19',
                'vehiculo_id' => NULL,
            ),
            2 => 
            array (
                'conductor_id' => 473,
                'created_at' => '2025-11-20 12:57:41',
                'descripcion' => 'Pruebas de reporte de incidencias',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => 'incidencias/1763654261_691f3a75cd28d.jpg',
                'id' => 3,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'accidente',
                'titulo' => 'caucho espichado',
                'ubicacion' => 'boleita',
                'updated_at' => '2025-11-20 12:57:41',
                'vehiculo_id' => NULL,
            ),
            3 => 
            array (
                'conductor_id' => 473,
                'created_at' => '2025-11-20 13:22:43',
                'descripcion' => 'no arranca por falta de aceite y mantenimiento',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => 'incidencias/1763655763_691f4053199c3.jpg',
                'id' => 4,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'averia',
                'titulo' => 'motor trancado',
                'ubicacion' => 'boleita',
                'updated_at' => '2025-11-20 13:22:43',
                'vehiculo_id' => NULL,
            ),
            4 => 
            array (
                'conductor_id' => 473,
                'created_at' => '2025-11-20 13:33:27',
                'descripcion' => 'choqué con moto y autobús',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => 'incidencias/1763656407_691f42d78f398.jpg',
                'id' => 5,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'accidente',
                'titulo' => 'choqué',
                'ubicacion' => 'boleita',
                'updated_at' => '2025-11-20 13:33:27',
                'vehiculo_id' => NULL,
            ),
            5 => 
            array (
                'conductor_id' => 475,
                'created_at' => '2025-11-20 17:19:55',
                'descripcion' => 'por dentro y por fuera muchas gracias por su atención',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => NULL,
                'id' => 6,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'otro',
                'titulo' => 'se lavó la unidad 31 y la unidad 89 el amigo Alexan jonder jean y Alberto i',
                'ubicacion' => NULL,
                'updated_at' => '2025-11-20 17:19:55',
                'vehiculo_id' => NULL,
            ),
            6 => 
            array (
                'conductor_id' => 475,
                'created_at' => '2025-11-20 17:20:25',
                'descripcion' => 'por dentro y por fuera muchas gracias por su atención',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => 'incidencias/1763670025_691f7809cf808.jpg',
                'id' => 7,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'otro',
                'titulo' => 'se lavó la unidad 31 y la unidad 89 el amigo Alexan jonder jean y Alberto i',
                'ubicacion' => 'boleita',
                'updated_at' => '2025-11-20 17:20:25',
                'vehiculo_id' => NULL,
            ),
            7 => 
            array (
                'conductor_id' => 481,
                'created_at' => '2025-11-26 12:10:56',
                'descripcion' => 'Operación de descontaminación de tanques',
                'estado' => 'pendiente',
                'fecha_resolucion' => NULL,
                'foto' => 'incidencias/1764169856_69271880503d7.jpg',
                'id' => 8,
                'latitud' => NULL,
                'longitud' => NULL,
                'notas_admin' => NULL,
                'pedido_id' => NULL,
                'tipo' => 'otro',
                'titulo' => 'Bolivariana de Puerto Cabello',
                'ubicacion' => 'Puero Cabello',
                'updated_at' => '2025-11-26 12:10:56',
                'vehiculo_id' => NULL,
            ),
        ));
        
        
    }
}