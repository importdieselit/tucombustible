<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlanConfigTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('plan_config')->delete();
        
        \DB::table('plan_config')->insert(array (
            0 => 
            array (
                'desc' => '',
                'id_pconfig' => 1,
                'id_plan' => 1,
                'id_tempario' => 8293,
                'title' => 'Stralis',
            ),
            1 => 
            array (
            'desc' => 'Plan para Tector con filtros Metalicos (modelos anteriores al 2012)',
                'id_pconfig' => 12,
                'id_plan' => 2,
                'id_tempario' => 8298,
                'title' => 'Tector < 2012',
            ),
            2 => 
            array (
                'desc' => '',
                'id_pconfig' => 4,
                'id_plan' => 1,
                'id_tempario' => 8297,
                'title' => 'Tector',
            ),
            3 => 
            array (
                'desc' => '',
                'id_pconfig' => 6,
                'id_plan' => 2,
                'id_tempario' => 8294,
                'title' => 'Stralis',
            ),
            4 => 
            array (
                'desc' => 'Plan para iveco Daily',
                'id_pconfig' => 9,
                'id_plan' => 1,
                'id_tempario' => 8301,
                'title' => 'Daily',
            ),
            5 => 
            array (
                'desc' => 'PLan para modelos tector superiores al año 2012 (filtro de aire plastico(',
                    'id_pconfig' => 14,
                    'id_plan' => 2,
                    'id_tempario' => 8298,
                    'title' => 'Tector > 2012',
                ),
                6 => 
                array (
                    'desc' => 'm3 stralis',
                    'id_pconfig' => 15,
                    'id_plan' => 3,
                    'id_tempario' => 8295,
                    'title' => 'Stralis',
                ),
                7 => 
                array (
                    'desc' => 'modelos tector aantes de 2012 filtro de aire metalico',
                    'id_pconfig' => 16,
                    'id_plan' => 3,
                    'id_tempario' => 8299,
                    'title' => 'Tector < 2012',
                ),
                8 => 
                array (
                    'desc' => 'M3 tector modelos posteriores a 2012 con filtro cavalino',
                    'id_pconfig' => 18,
                    'id_plan' => 3,
                    'id_tempario' => 8299,
                    'title' => 'Tector >2012',
                ),
                9 => 
                array (
                    'desc' => 'M4',
                    'id_pconfig' => 19,
                    'id_plan' => 4,
                    'id_tempario' => 8296,
                    'title' => 'Stralis',
                ),
                10 => 
                array (
                    'desc' => 'modelos tector aantes de 2012 filtro de aire metalico',
                    'id_pconfig' => 20,
                    'id_plan' => 4,
                    'id_tempario' => 8300,
                    'title' => 'Tector < 2012',
                ),
                11 => 
                array (
                    'desc' => 'M4 tector modelos posteriores a 2012 con filtro cavalino',
                    'id_pconfig' => 21,
                    'id_plan' => 4,
                    'id_tempario' => 8300,
                    'title' => 'Tector >2012',
                ),
                12 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 22,
                    'id_plan' => 1,
                    'id_tempario' => 8305,
                    'title' => 'CANTER',
                ),
                13 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 23,
                    'id_plan' => 2,
                    'id_tempario' => 8306,
                    'title' => 'CANTER',
                ),
                14 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 24,
                    'id_plan' => 3,
                    'id_tempario' => 8307,
                    'title' => 'CANTER',
                ),
                15 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 25,
                    'id_plan' => 4,
                    'id_tempario' => 8308,
                    'title' => 'CANTER',
                ),
                16 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 40,
                    'id_plan' => 2,
                    'id_tempario' => 8303,
                    'title' => 'DAILY',
                ),
                17 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 27,
                    'id_plan' => 3,
                    'id_tempario' => 8303,
                    'title' => 'DAILY',
                ),
                18 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 28,
                    'id_plan' => 4,
                    'id_tempario' => 8304,
                    'title' => 'DAILY',
                ),
                19 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 30,
                    'id_plan' => 183,
                    'id_tempario' => NULL,
                    'title' => '',
                ),
                20 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 35,
                    'id_plan' => 183,
                    'id_tempario' => NULL,
                    'title' => '',
                ),
                21 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 34,
                    'id_plan' => 182,
                    'id_tempario' => NULL,
                    'title' => '',
                ),
                22 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 36,
                    'id_plan' => 183,
                    'id_tempario' => NULL,
                    'title' => '',
                ),
                23 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 37,
                    'id_plan' => 162,
                    'id_tempario' => NULL,
                    'title' => 'Trailers',
                ),
                24 => 
                array (
                    'desc' => 'plan m1 stralis',
                    'id_pconfig' => 38,
                    'id_plan' => 150,
                    'id_tempario' => NULL,
                    'title' => 'stralis',
                ),
                25 => 
                array (
                    'desc' => '',
                    'id_pconfig' => 39,
                    'id_plan' => 150,
                    'id_tempario' => NULL,
                    'title' => 'tector',
                ),
            ));
        
        
    }
}