<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentosSeeder extends Seeder
{
    public function run(): void
    {
        $departamentos = [
            ['departamento' => 'Administración', 'descripcion' => 'Área administrativa y coordinación'],
            ['departamento' => 'Finanzas', 'descripcion' => 'Gerencia de finanzas y contabilidad'],
            ['departamento' => 'Comercial y Operaciones', 'descripcion' => 'Dirección comercial, compras y operaciones'],
            ['departamento' => 'Flota', 'descripcion' => 'Gestión de patio, choferes y mecánicos'],
            ['departamento' => 'Tecnología', 'descripcion' => 'Soporte técnico, infraestructura y desarrollo'],
            ['departamento' => 'Seguridad', 'descripcion' => 'Seguridad industrial'],
            ['departamento' => 'Servicios Generales', 'descripcion' => 'Mantenimiento y operadores de servicio'],
        ];

        DB::table('departamentos')->insert($departamentos);
    }
}