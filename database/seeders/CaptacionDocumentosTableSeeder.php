<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CaptacionDocumentosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('captacion_documentos')->delete();
        
        \DB::table('captacion_documentos')->insert(array (
            0 => 
            array (
                'captacion_id' => 1,
                'created_at' => '2025-11-28 18:35:46',
                'id' => 1,
                'nombre_documento' => 'Landing Page IMPORDIESEL .pdf',
                'requisito_id' => 1,
                'ruta' => 'clientes/1/documentos/YYYANLy3YMSIwc0AzGO7L7lXnzUVn6coLCV8Sawl.pdf',
                'tipo_anexo' => 'A',
                'updated_at' => '2025-11-28 18:35:46',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            1 => 
            array (
                'captacion_id' => 1,
                'created_at' => '2025-11-28 18:35:46',
                'id' => 2,
                'nombre_documento' => 'Declaración bajo fe de juramento para solicitud de cupo de combustible .pdf',
                'requisito_id' => 2,
                'ruta' => 'clientes/1/documentos/vAllLBaVkAbqjMnFoR1lRR33SwqYuLYPcOovXNIs.pdf',
                'tipo_anexo' => 'B',
                'updated_at' => '2025-11-28 18:35:46',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            2 => 
            array (
                'captacion_id' => 1,
                'created_at' => '2025-11-28 18:35:46',
                'id' => 3,
                'nombre_documento' => 'Industrial Hidrocarburos - REGIONAL BOLIVAR.pdf',
                'requisito_id' => 3,
                'ruta' => 'clientes/1/documentos/EXEEIBcduwcUOaLoje4WN95wj0mARiWjKmfjkF2B.pdf',
                'tipo_anexo' => 'C',
                'updated_at' => '2025-11-28 18:35:46',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            3 => 
            array (
                'captacion_id' => 1,
                'created_at' => '2025-11-28 18:35:46',
                'id' => 4,
                'nombre_documento' => 'Solicitud de cupo de combustible Industrial .pdf',
                'requisito_id' => 4,
                'ruta' => 'clientes/1/documentos/6Zl2l5wLdLrL6YJZTp9b2mYd0kK2QmKnqUfXyI8B.pdf',
                'tipo_anexo' => 'D',
                'updated_at' => '2025-11-28 18:35:46',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            4 => 
            array (
                'captacion_id' => 1,
                'created_at' => '2025-11-28 18:35:46',
                'id' => 5,
                'nombre_documento' => 'REQUISITOS PARA CUPO INDUSTRIAL.doc',
                'requisito_id' => 5,
                'ruta' => 'clientes/1/documentos/uPMqRno6h4H2GaSA1TULLKhBVpdKYqG7XVXOMTqI.doc',
                'tipo_anexo' => 'E',
                'updated_at' => '2025-11-28 18:35:46',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            5 => 
            array (
                'captacion_id' => 5,
                'created_at' => '2025-12-05 12:33:29',
                'id' => 6,
                'nombre_documento' => 'Declaración bajo fe de juramento para solicitud de cupo de combustible .pdf',
                'requisito_id' => 1,
                'ruta' => 'clientes/5/documentos/aIzhiyuIpa3dAuAlThF0JrwN9tkhfpXf7gsRldqA.pdf',
                'tipo_anexo' => 'A',
                'updated_at' => '2025-12-05 12:33:29',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            6 => 
            array (
                'captacion_id' => 5,
                'created_at' => '2025-12-05 13:42:30',
                'id' => 7,
                'nombre_documento' => 'Inspeccion_Salida_A89DC8K_20251021.pdf',
                'requisito_id' => 2,
                'ruta' => 'clientes/5/documentos/74Vu5ojcCYvyAat5vTKu7YZ8PjHJLzl5OywxAPbO.pdf',
                'tipo_anexo' => 'B',
                'updated_at' => '2025-12-05 13:42:30',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            7 => 
            array (
                'captacion_id' => 5,
                'created_at' => '2025-12-05 13:45:23',
                'id' => 8,
                'nombre_documento' => 'DOC YHENDER.pdf',
                'requisito_id' => 3,
                'ruta' => 'clientes/5/documentos/2bvAf3ykj5I9iXnQOe5MNbSSJwM1dKDEzzfUjRe3.pdf',
                'tipo_anexo' => 'C',
                'updated_at' => '2025-12-05 13:45:23',
                'validado' => 0,
                'validado_por' => NULL,
            ),
            8 => 
            array (
                'captacion_id' => 5,
                'created_at' => '2025-12-05 13:50:18',
                'id' => 9,
                'nombre_documento' => 'APP TUCOMBUSTIBLE.pdf',
                'requisito_id' => 4,
                'ruta' => 'clientes/5/documentos/IaVPwA6PSmhix3GfVpeHHbuCkabMedtmWmx6SbtH.pdf',
                'tipo_anexo' => 'D',
                'updated_at' => '2025-12-05 13:50:18',
                'validado' => 0,
                'validado_por' => NULL,
            ),
        ));
        
        
    }
}