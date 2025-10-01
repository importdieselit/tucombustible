<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisoPerfilSeeder extends Seeder
{
    /**
     * Define los permisos base para los perfiles del sistema.
     * Basado en la tabla 'permiso_perfil' y los IDs definidos por el usuario.
     * * @return void
     */
    public function run()
    {
        // Limpiar la tabla antes de sembrar para evitar duplicados
        DB::table('permiso_perfil')->truncate();

        // ----------------------------------------------------
        // 1. Mapeo de IDs (TU ESTRUCTURA)
        // ----------------------------------------------------

        // IDs de Perfiles
        $perfiles = [
            'superadmin' => 1,
            'administrador' => 2,
            'cliente' => 3,
            'conductor' => 4,
            'mecanico' => 5,
            'Jefe de Patio' => 6,
            'Operaciones' => 7,
            'Almacen' => 8,
        ];

        // IDs de Módulos
        $modulos = [
            'Vehículos' => 1,
            'Órdenes' => 2,
            'Almacen' => 3, // Asumimos que es una vista general de almacén
            'Combustible' => 4,
            'Administrar' => 5, // Configuración general
            'Marcas' => 12,
            'Modelos' => 13,
            'Inventario' => 30,
            'Proveedores' => 31,
            'Depositos' => 40, // Entradas de mercancía
            'Despachos' => 42, // Salidas de mercancía/pedidos
            'Clientes' => 43,
            'Recarga' => 44, // Acción de Recarga de Combustible
            'Choferes' => 47,
            'Usuarios' => 51, // CRUD de usuarios
        ];
        
        // Estructura de permisos: [modulo_id, create, read, update, delete]
        $permisosBase = [];
        $now = now();

        // ----------------------------------------------------
        // 2. DEFINICIÓN DE PERMISOS POR PERFIL
        // ----------------------------------------------------

        // === PERFIL: SUPERADMIN (ID 1) ===
        foreach ($modulos as $id_modulo) {
            $permisosBase[] = [
                'id_perfil' => $perfiles['superadmin'],
                'id_modulo' => $id_modulo,
                'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 1,
                'created_at' => $now, 'updated_at' => $now
            ];
        }

        // === PERFIL: ADMINISTRADOR (ID 2) ===
        // Acceso total a casi todo, menos delete en algunos maestros sensibles
        $modulos_admin_crud = [1, 2, 4, 30, 40, 42, 44, 47, 51]; // Vehículos, Órdenes, Comb., Inventario, Depósitos, Despachos, Recarga, Choferes, Usuarios
        $modulos_admin_cru = [3, 5, 12, 13, 31, 43]; // Almacén, Administrar, Marcas, Modelos, Proveedores, Clientes
        
        foreach ($modulos_admin_crud as $id) {
            $permisosBase[] = ['id_perfil' => 2, 'id_modulo' => $id, 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 1, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach ($modulos_admin_cru as $id) {
            $permisosBase[] = ['id_perfil' => 2, 'id_modulo' => $id, 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        }

        // === PERFIL: CLIENTE (ID 3) ===
        // Solo lectura de sus datos.
        $permisosBase[] = ['id_perfil' => 3, 'id_modulo' => $modulos['Vehículos'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 3, 'id_modulo' => $modulos['Órdenes'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 3, 'id_modulo' => $modulos['Despachos'], 'create' => 1, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now]; // Crear solicitudes/pedidos

        // === PERFIL: CONDUCTOR (ID 4) ===
        // Foco en su asignación y reporte (U/C de estatus/recargas).
        $permisosBase[] = ['id_perfil' => 4, 'id_modulo' => $modulos['Vehículos'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 4, 'id_modulo' => $modulos['Recarga'], 'create' => 1, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 4, 'id_modulo' => $modulos['Despachos'], 'create' => 0, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 4, 'id_modulo' => $modulos['Choferes'], 'create' => 0, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];

        // === PERFIL: MECÁNICO (ID 5) ===
        // CRUD de Órdenes y uso de Inventario/Despachos
        $permisosBase[] = ['id_perfil' => 5, 'id_modulo' => $modulos['Vehículos'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 5, 'id_modulo' => $modulos['Órdenes'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 5, 'id_modulo' => $modulos['Inventario'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 5, 'id_modulo' => $modulos['Despachos'], 'create' => 1, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now]; // Crear despacho de repuestos

        // === PERFIL: JEFE DE PATIO (ID 6) ===
        // Movimientos de inventario (Depósitos/Despachos) y estatus de vehículos.
        $permisosBase[] = ['id_perfil' => 6, 'id_modulo' => $modulos['Vehículos'], 'create' => 0, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 6, 'id_modulo' => $modulos['Órdenes'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 6, 'id_modulo' => $modulos['Inventario'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 6, 'id_modulo' => $modulos['Depositos'], 'create' => 1, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 6, 'id_modulo' => $modulos['Despachos'], 'create' => 1, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];

        // === PERFIL: OPERACIONES (ID 7) ===
        // CRUD de Vehículos y gestión logística (Despachos/Rutas)
        $permisosBase[] = ['id_perfil' => 7, 'id_modulo' => $modulos['Vehículos'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 7, 'id_modulo' => $modulos['Órdenes'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 7, 'id_modulo' => $modulos['Combustible'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 7, 'id_modulo' => $modulos['Despachos'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 7, 'id_modulo' => $modulos['Choferes'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];

        // === PERFIL: ALMACEN (ID 8) ===
        // CRUD de Inventario, Proveedores y movimientos (Depósitos/Despachos)
        $permisosBase[] = ['id_perfil' => 8, 'id_modulo' => $modulos['Almacen'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 8, 'id_modulo' => $modulos['Inventario'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 8, 'id_modulo' => $modulos['Proveedores'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 8, 'id_modulo' => $modulos['Depositos'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 8, 'id_modulo' => $modulos['Despachos'], 'create' => 1, 'read' => 1, 'update' => 1, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 8, 'id_modulo' => $modulos['Marcas'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];
        $permisosBase[] = ['id_perfil' => 8, 'id_modulo' => $modulos['Modelos'], 'create' => 0, 'read' => 1, 'update' => 0, 'delete' => 0, 'created_at' => $now, 'updated_at' => $now];


        // ----------------------------------------------------
        // 3. INSERCIÓN EN LA BASE DE DATOS
        // ----------------------------------------------------
        DB::table('permiso_perfil')->insert($permisosBase);
    }
}