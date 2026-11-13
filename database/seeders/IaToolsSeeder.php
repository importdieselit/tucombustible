<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IaTool;

class IaToolsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiamos la tabla por si lo corres varias veces (evitar duplicados)
        IaTool::truncate();

        $herramientas = [
            [
                'name' => 'consultar_metricas_dashboard',
                'description' => 'Consulta las métricas gerenciales del combustible. Devuelve el inventario físico en tanques, vehículos precargados, volumen comprometido y disponibilidad real para la venta.',
                'is_active' => true,
                'required_roles' => ['admin', 'superadmin'],
                'api_endpoint' => 'https://tucombustible.com.ve/api/admin/reportes/combustible-dashboard', // Ajusta tu dominio real
                'http_method' => 'GET',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ejecutar' => ['type' => 'boolean', 'description' => 'Siempre true']
                    ]
                ]
            ],
            [
                'name' => 'consultar_viajes_programados',
                'description' => 'Consulta el calendario y la planificación de viajes logísticos y despachos programados de la empresa.',
                'is_active' => true,
                'required_roles' => ['admin', 'superadmin', 'logistica'],
                'api_endpoint' => 'https://tucombustible.com.ve/api/viajes/calendario',
                'http_method' => 'GET',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ejecutar' => ['type' => 'boolean', 'description' => 'Siempre true']
                    ]
                ]
            ],
            [
                'name' => 'consultar_estado_conductores',
                'description' => 'Obtiene las estadísticas de los conductores, sus incidencias, viajes asignados y disponibilidad de flota humana.',
                'is_active' => true,
                'required_roles' => ['admin', 'superadmin'],
                'api_endpoint' => 'https://tucombustible.com.ve/api/conductor/estadisticas',
                'http_method' => 'GET',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ejecutar' => ['type' => 'boolean', 'description' => 'Siempre true']
                    ]
                ]
            ],
            [
                'name' => 'consultar_alertas_mecanicas',
                'description' => 'Verifica las estadísticas del taller mecánico, inspecciones, vehículos en mantenimiento y alertas de flota.',
                'is_active' => true,
                'required_roles' => ['admin', 'superadmin', 'mecanico'],
                'api_endpoint' => 'https://tucombustible.com.ve/api/mecanico/estadisticas',
                'http_method' => 'GET',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ejecutar' => ['type' => 'boolean', 'description' => 'Siempre true']
                    ]
                ]
            ]
        ];

        foreach ($herramientas as $herramienta) {
            IaTool::create($herramienta);
        }
    }
}