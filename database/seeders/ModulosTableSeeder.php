<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Limpiar la tabla antes de insertar
        DB::table('modulos')->delete();
        
        DB::table('modulos')->insert([
            ['id' => 1, 'modulo' => 'Vehículos', 'ruta' => 'vehiculos.index', 'icono' => 'fa fa-car', 'orden' => 2, 'id_padre' => 0, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 1, 'descripcion' => 'Gestión de vehículos'],
            ['id' => 2, 'modulo' => 'Mantenimiento', 'ruta' => 'ordenes.index', 'icono' => 'fa fa-clipboard-check', 'orden' => 3, 'id_padre' => 0, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 1, 'descripcion' => 'Gestion de ordenes'],
            ['id' => 3, 'modulo' => 'Almacen', 'ruta' => 'inventario.index', 'icono' => 'fa fa-boxes', 'orden' => 5, 'id_padre' => 0, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 1, 'descripcion' => 'Gestión de inventario'],
            ['id' => 4, 'modulo' => 'Combustible', 'ruta' => 'combustible.index', 'icono' => 'fa fa-gas-pump', 'orden' => 4, 'id_padre' => 0, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 1, 'descripcion' => 'Gestión de tanques'],
            ['id' => 5, 'modulo' => 'Administrar', 'ruta' => 'usuarios.index', 'icono' => 'fa-users-gear', 'orden' => 5, 'id_padre' => 0, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 0, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 6, 'modulo' => 'Checklist', 'ruta' => 'inspecciones.index', 'icono' => NULL, 'orden' => 3, 'id_padre' => 2, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 7, 'modulo' => 'Reportes', 'ruta' => 'reports.index', 'icono' => 'fa fa-list', 'orden' => 7, 'id_padre' => 0, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 8, 'modulo' => 'Logistica', 'ruta' => 'logistica.index', 'icono' => 'fa fa-route', 'orden' => 6, 'id_padre' => 0, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 9, 'modulo' => 'Historial', 'ruta' => 'viajes.list', 'icono' => NULL, 'orden' => 7, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 0, 'visible' => 0, 'descripcion' => 'Gestion de operaciones'],
            ['id' => 10, 'modulo' => 'Listado', 'ruta' => 'vehiculos.list', 'icono' => 'bi-list-ul', 'orden' => 1, 'id_padre' => NULL, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 0, 'descripcion' => 'Ver todos los vehículos'],
            ['id' => 11, 'modulo' => 'Crear', 'ruta' => 'vehiculos.create', 'icono' => 'bi-plus-circle', 'orden' => 2, 'id_padre' => NULL, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 0, 'descripcion' => 'Registrar nuevo vehículo'],
            ['id' => 12, 'modulo' => 'Marcas', 'ruta' => 'marcas.index', 'icono' => 'bi-tags', 'orden' => 3, 'id_padre' => NULL, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 0, 'descripcion' => 'Gestión de marcas'],
            ['id' => 13, 'modulo' => 'Modelos', 'ruta' => 'modelos.index', 'icono' => 'bi-tag', 'orden' => 4, 'id_padre' => NULL, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 0, 'descripcion' => 'Gestión de modelos'],
            ['id' => 14, 'modulo' => 'Mecanicos', 'ruta' => NULL, 'icono' => NULL, 'orden' => 9, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 0, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 20, 'modulo' => 'Listado', 'ruta' => 'ordenes.list', 'icono' => 'bi-list-check', 'orden' => 1, 'id_padre' => 2, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 1, 'descripcion' => 'Ver todas las órdenes'],
            ['id' => 21, 'modulo' => 'Crear', 'ruta' => 'ordenes.create', 'icono' => 'bi-plus-circle', 'orden' => 2, 'id_padre' => 2, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 1, 'descripcion' => 'Crear nueva orden'],
            ['id' => 30, 'modulo' => 'Inventario', 'ruta' => 'inventario.list', 'icono' => 'bi-box', 'orden' => 1, 'id_padre' => 3, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 1, 'descripcion' => 'Ver productos'],
            ['id' => 31, 'modulo' => 'Proveedores', 'ruta' => 'proveedores.index', 'icono' => 'bi-truck', 'orden' => 2, 'id_padre' => 3, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 0, 'visible' => 0, 'descripcion' => 'Gestión de proveedores'],
            ['id' => 40, 'modulo' => 'Depositos', 'ruta' => 'depositos.index', 'icono' => 'bi-list-ol', 'orden' => 1, 'id_padre' => 4, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 0, 'descripcion' => 'Ver tanques'],
            ['id' => 41, 'modulo' => 'Entradas/salidas', 'ruta' => 'combustible.list', 'icono' => 'bi-list-ol', 'orden' => 2, 'id_padre' => 4, 'created_at' => '2025-08-12 23:07:11', 'updated_at' => '2025-08-12 23:07:11', 'url_directa' => 1, 'visible' => 0, 'descripcion' => 'Historial de repostajes'],
            ['id' => 42, 'modulo' => 'Despachos', 'ruta' => 'despachos.list', 'icono' => 'bi-list-ol', 'orden' => 3, 'id_padre' => 4, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 43, 'modulo' => 'Gestion de Clientes', 'ruta' => 'clientes.list', 'icono' => 'bi-person', 'orden' => 4, 'id_padre' => 4, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => ''],
            ['id' => 44, 'modulo' => 'Recarga', 'ruta' => 'combustible.recarga', 'icono' => NULL, 'orden' => 5, 'id_padre' => 4, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => '1'],
            ['id' => 45, 'modulo' => 'Surtir (00)', 'ruta' => 'combustible.createDespachoIndustrial', 'icono' => NULL, 'orden' => 1, 'id_padre' => 4, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 47, 'modulo' => 'Choferes', 'ruta' => 'choferes.index', 'icono' => 'bi-user', 'orden' => 8, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 48, 'modulo' => 'Solicitudes', 'ruta' => 'inventario.requests', 'icono' => 'bi-document', 'orden' => 3, 'id_padre' => 3, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 0, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 49, 'modulo' => 'Pedidos Clientes', 'ruta' => 'combustible.pedidos', 'icono' => NULL, 'orden' => 2, 'id_padre' => 4, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 50, 'modulo' => 'Por Despachar', 'ruta' => 'combustible.aprobados', 'icono' => NULL, 'orden' => 6, 'id_padre' => 4, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 51, 'modulo' => 'Usuarios', 'ruta' => 'usuarios.list', 'icono' => 'fa-users', 'orden' => 3, 'id_padre' => 5, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 52, 'modulo' => 'Clientes', 'ruta' => 'clientes.index', 'icono' => 'fa fa-address-book', 'orden' => 1, 'id_padre' => 0, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 53, 'modulo' => 'Captacion/Registro', 'ruta' => 'clientes.create', 'icono' => NULL, 'orden' => 1, 'id_padre' => 52, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 55, 'modulo' => 'Recarga Prepagado', 'ruta' => 'combustible.createPrepago', 'icono' => NULL, 'orden' => 4, 'id_padre' => 52, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 56, 'modulo' => 'Planificacion Despacho', 'ruta' => 'viajes.create', 'icono' => NULL, 'orden' => 1, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 57, 'modulo' => 'Planificacion MGO', 'ruta' => 'viajes.mgo', 'icono' => NULL, 'orden' => 2, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 58, 'modulo' => 'Planificacion Flete', 'ruta' => 'combustible.createFlete', 'icono' => NULL, 'orden' => 3, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 59, 'modulo' => 'Planificacion Compra', 'ruta' => 'combustible.createCompra', 'icono' => NULL, 'orden' => 4, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 0, 'descripcion' => NULL],
            ['id' => 60, 'modulo' => 'Calendario', 'ruta' => 'viajes.calendario', 'icono' => NULL, 'orden' => 5, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 61, 'modulo' => 'Tabulador de Viajes', 'ruta' => 'viaticos.tabulador', 'icono' => NULL, 'orden' => 6, 'id_padre' => 8, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
            ['id' => 63, 'modulo' => 'Logistica_old', 'ruta' => 'viajes.index', 'icono' => 'fa fa-route', 'orden' => 6, 'id_padre' => 0, 'created_at' => NULL, 'updated_at' => NULL, 'url_directa' => 1, 'visible' => 1, 'descripcion' => NULL],
        ]);
    }
}