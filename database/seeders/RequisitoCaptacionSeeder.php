<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequisitoCaptacion;

class RequisitoCaptacionSeeder extends Seeder
{
    public function run()
    {
        $requisitos = [
            // Requisitos para AMBOS
            ['codigo' => 'RIF', 'descripcion' => 'Registro de Información Fiscal (Actualizado)', 'tipo_cliente' => 'ambos'],
            ['codigo' => 'CI_REP', 'descripcion' => 'Cédula del Representante Legal', 'tipo_cliente' => 'ambos'],
            
            // Requisitos solo para el PADRE (Sede Principal)
            ['codigo' => 'ACTA', 'descripcion' => 'Acta Constitutiva y últimas modificaciones', 'tipo_cliente' => 'padre'],
            ['codigo' => 'REG_MERC', 'descripcion' => 'Registro Mercantil', 'tipo_cliente' => 'padre'],
            ['codigo' => 'EST_CUENTA', 'descripcion' => 'Referencia Bancaria', 'tipo_cliente' => 'padre'],

            // Requisitos solo para SUCURSAL
            ['codigo' => 'CARTA_AUT', 'descripcion' => 'Carta de Autorización de la Principal', 'tipo_cliente' => 'sucursal'],
            ['codigo' => 'ARREND', 'descripcion' => 'Contrato de Arrendamiento o Propiedad del local', 'tipo_cliente' => 'sucursal'],
        ];

        foreach ($requisitos as $req) {
            RequisitoCaptacion::updateOrCreate(['codigo' => $req['codigo']], $req);
        }
    }
}