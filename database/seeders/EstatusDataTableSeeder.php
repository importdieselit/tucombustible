<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EstatusDataTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('estatus_data')->delete();
        
        \DB::table('estatus_data')->insert(array (
            0 => 
            array (
                'auto' => 'Disponible',
                'css' => 'success',
                'hex' => '#28a745',
                'icon_auto' => 'fa-flag',
                'icon_orden' => 'fa-lock',
                'icon_request' => 'fa-check',
                'id_estatus' => 1,
                'orden' => 'CERRADA',
                'request' => 'Despachado',
            ),
            1 => 
            array (
                'auto' => 'En servicio',
                'css' => 'warning',
                'hex' => '#ffc107',
                'icon_auto' => 'fa-car',
                'icon_orden' => ' fa-unlock',
                'icon_request' => 'fa-file-o',
                'id_estatus' => 2,
                'orden' => 'ABIERTA',
                'request' => 'Solicitado',
            ),
            2 => 
            array (
                'auto' => 'Por Mantenimiento',
                'css' => 'warning',
                'hex' => '#ffbe00',
                'icon_auto' => 'fa-exclamation-triangle',
                'icon_orden' => 'fa-clock-o',
                'icon_request' => 'fa-flag',
                'id_estatus' => 3,
                'orden' => 'PROGRAMADA',
                'request' => 'Aprobado',
            ),
            3 => 
            array (
                'auto' => 'Inactivo',
                'css' => 'secondary',
                'hex' => '#6c757d',
                'icon_auto' => 'fa-exclamation-triangle',
                'icon_orden' => 'fa-times',
                'icon_request' => 'fa-eye',
                'id_estatus' => 4,
                'orden' => 'CANCELADA',
                'request' => 'observacion',
            ),
            4 => 
            array (
                'auto' => 'Fuera de Servicio',
                'css' => 'danger',
                'hex' => '#dc3545',
                'icon_auto' => 'fa-exclamation-triangle',
                'icon_orden' => 'fa fa-trash',
                'icon_request' => 'fa fa-trash',
                'id_estatus' => 5,
                'orden' => 'ELIMINADA',
                'request' => 'Rechazada',
            ),
        ));
        
        
    }
}