<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaptacionClientesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('captacion_clientes')->delete();
        
        DB::table('captacion_clientes')->insert(array (
            0 => 
            array (
                'atendido_por' => 'Oscar Osorio',
                'cliente_id' => NULL,
                'correo' => 'servicios.generales@sogebusa.com',
                'created_at' => '2025-12-03 14:53:44',
                'datos_adicionales' => NULL,
                'direccion' => 'CASCO CENRAL PUERTO CABELLO',
                'estatus_captacion' => 'registro_inicial',
                'gestion' => 'CUPO',
                'id' => 2,
                'observaciones' => NULL,
                'razon_social' => 'SOGEBUSA C.A',
                'representante' => 'Héctor Reyes ',
                'rif' => 'J-075770366',
                'solicitados' => 6000,
                'telefono' => '04244738083',
                'tipo_cliente' => 'MGO',
                'updated_at' => '2025-12-03 14:55:29',
            ),
            1 => 
            array (
                'atendido_por' => 'Oscar Osorio',
                'cliente_id' => NULL,
                'correo' => 'lyraservicespc@gmail.com',
                'created_at' => '2025-12-03 14:53:55',
                'datos_adicionales' => NULL,
                'direccion' => 'CALLE PUERTO CABELLO, AREA DE SERVICIO LOCAL, NRO 05 SECTOR PATIO 29-BPATANEMO CARABOBO ZONA POSTAL 2050.',
                'estatus_captacion' => 'registro_inicial',
                'gestion' => 'CUPO',
                'id' => 3,
                'observaciones' => NULL,
                'razon_social' => 'LYA SERVICE, C.A.',
                'representante' => 'José Lores LYA SERVICE',
                'rif' => 'J- 411046396',
                'solicitados' => 6000,
                'telefono' => '0412-5120411',
                'tipo_cliente' => 'MGO',
                'updated_at' => '2025-12-03 14:55:21',
            ),
            2 => 
            array (
                'atendido_por' => 'Oscar Osorio',
                'cliente_id' => NULL,
                'correo' => 'erojas.respectservice@gmail.com ',
                'created_at' => '2025-12-03 14:55:03',
                'datos_adicionales' => NULL,
            'direccion' => '( LAS LLEVES) PUERTO CABELLO',
                'estatus_captacion' => 'registro_inicial',
                'gestion' => 'CUPO',
                'id' => 4,
                'observaciones' => NULL,
                'razon_social' => ' Respect Service Dellmar C.A',
                'representante' => 'Engelberth Rojas',
                'rif' => 'J-503609348',
                'solicitados' => 6000,
                'telefono' => '0414-4145860',
                'tipo_cliente' => 'MGO',
                'updated_at' => '2025-12-03 14:55:16',
            ),
            3 => 
            array (
                'atendido_por' => 'Oscar Osorio',
                'cliente_id' => NULL,
                'correo' => 'jcano@pbl.servicom.com.ve',
                'created_at' => '2025-12-03 14:55:08',
                'datos_adicionales' => NULL,
                'direccion' => 'ZONA COLONIAL PUERTO CABELLO',
                'estatus_captacion' => 'registro_inicial',
                'gestion' => 'MIGRACION',
                'id' => 5,
                'observaciones' => NULL,
                'razon_social' => 'SERVICOM C.A',
                'representante' => 'JORGE',
                'rif' => 'J-075140427',
                'solicitados' => 18000,
                'telefono' => '0412-1405094',
                'tipo_cliente' => 'MGO',
                'updated_at' => '2025-12-03 14:55:12',
            ),
            4 => 
            array (
                'atendido_por' => 'Oscar Osorio',
                'cliente_id' => NULL,
                'correo' => 'disnalogistic@gmail.com',
                'created_at' => '2025-12-05 16:22:50',
                'datos_adicionales' => NULL,
                'direccion' => 'CALLE PUERTO CABELLO, EDIF. BOLIVARIANA DE PUERTOS, S.A., PISO P.B., LOCAL 4C, EN EL PATIO DE PUERTO SECTOR CASCO CENTRAL PUERTO CABELLO, CARABOBO, ZONA POSTAL 2050',
                'estatus_captacion' => 'registro_inicial',
                'gestion' => 'MIGRACION',
                'id' => 9,
                'observaciones' => NULL,
                'razon_social' => 'WORLD SEA LOGISTICS SERVICE, C.A.',
                'representante' => NULL,
                'rif' => '501398275',
                'solicitados' => 6000,
                'telefono' => '04243484776',
                'tipo_cliente' => 'MGO',
                'updated_at' => '2025-12-05 16:22:50',
            ),
        ));
        
        
    }
}