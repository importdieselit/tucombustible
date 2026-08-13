<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ==========================================================
        // FASE 1: CREACIÓN DE TABLAS Y CAMPOS MAESTROS (Sin restricciones)
        // ==========================================================

        Schema::create('inventario_equivalentes', function (Blueprint $table) {
            $table->id();
            // Si inventario.id es INT, usamos unsignedInteger en lugar de foreignId
            $table->unsignedBigInteger('inventario_id');
            $table->unsignedBigInteger('equivalente_id');
        });

        Schema::create('inventario_asociados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventario_id'); // Tipo INT para emparejar con tu catálogo
            $table->unsignedBigInteger('modelo_vehiculo_id'); // Ajustar si 'modelos' usa BIGINT o INT
            $table->string('nivel_prioridad', 20)->default('ALTA'); 
            $table->timestamps();
        });

        Schema::create('inventario_despachos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_trabajo_id'); 
            $table->unsignedBigInteger('inventario_id'); 
            $table->unsignedBigInteger('ubicacion_origen_id')->nullable(); 
            $table->decimal('cantidad_solicitada', 10, 2);
            $table->decimal('cantidad_despachada', 10, 2)->default(0);
            $table->enum('estatus', ['SOLICITUD', 'APROBADO', 'DESPACHADO', 'RECHAZADO', 'CANCELADO'])->default('SOLICITUD');
            $table->unsignedBigInteger('usuario_solicita_id'); 
            $table->unsignedBigInteger('usuario_despacha_id')->nullable(); 
            $table->timestamp('fecha_despacho')->nullable();
            $table->timestamps();
        });

         Schema::create('historial_movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventario_id')->nullable(); // No es obligatorio porque algunos movimientos pueden ser de inventario no registrado
            $table->unsignedBigInteger('ubicacion_id')->nullable(); // No es obligatorio porque algunos movimientos pueden ser de inventario en tránsito o sin ubicación definida
            $table->unsignedBigInteger('usuario_id')->nullable(); // No es obligatorio porque algunos movimientos pueden ser automáticos o por sistema
            
            $table->enum('tipo_movimiento', ['ENTRADA', 'SALIDA', 'TRASLADO']);
            $table->enum('tipo_operacion', ['COMPRA', 'DESPACHO', 'DEVOLUCION', 'AJUSTE_CONTEO', 'DESECHO', 'INICIAL']);
            $table->string('documento_referencia')->nullable(); // Ej: OC-001, OT-4500
            
            $table->decimal('cantidad_previa', 12, 4);
            $table->decimal('cantidad_movilizada', 12, 4);
            $table->decimal('cantidad_final', 12, 4);
            
            $table->text('observacion')->nullable();
            $table->timestamps();
            
            $table->index(['inventario_id', 'created_at']);
        });


        // ==========================================================
        // FASE 2: ASIGNACIÓN DE LLAVES FORÁNEAS (Foreign Keys)
        // ==========================================================
        // Esta fase se ejecuta únicamente cuando TODAS las tablas de la fase 1 ya existen en el motor de BD.

        Schema::table('inventario_equivalentes', function (Blueprint $table) {
            $table->foreign('inventario_id')->references('id')->on('inventario')->onDelete('cascade');
            $table->foreign('equivalente_id')->references('id')->on('inventario')->onDelete('cascade');
            $table->unique(['inventario_id', 'equivalente_id'], 'uq_inventario_equiv'); // Índice único con nombre explícito
        });

        Schema::table('inventario_asociados', function (Blueprint $table) {
            $table->foreign('inventario_id')->references('id')->on('inventario')->onDelete('cascade');
            $table->foreign('modelo_vehiculo_id')->references('id')->on('modelos')->onDelete('cascade');
        });

        Schema::table('inventario_despachos', function (Blueprint $table) {
            $table->foreign('inventario_id')->references('id')->on('inventario')->onDelete('restrict');
            $table->foreign('ubicacion_origen_id')->references('id')->on('ubicaciones')->onDelete('set null');
            $table->foreign('usuario_solicita_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('usuario_despacha_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('historial_movimientos_inventario', function (Blueprint $table) {
            $table->foreign('inventario_id')->references('id')->on('inventario')->onDelete('set null');
            $table->foreign('ubicacion_id')->references('id')->on('ubicaciones')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        // ==========================================================
        // REVERSA: ELIMINAR LLAVES FORÁNEAS PRIMERO
        // ==========================================================
        // Si no eliminas las restricciones primero, MySQL impedirá borrar las tablas.

        Schema::table('inventario_despachos', function (Blueprint $table) {
            $table->dropForeign(['inventario_id']);
            $table->dropForeign(['ubicacion_origen_id']);
            $table->dropForeign(['usuario_solicita_id']);
            $table->dropForeign(['usuario_despacha_id']);
        });

        Schema::table('inventario_asociados', function (Blueprint $table) {
            $table->dropForeign(['inventario_id']);
            $table->dropForeign(['modelo_vehiculo_id']);
        });

        Schema::table('inventario_equivalentes', function (Blueprint $table) {
            $table->dropForeign(['inventario_id']);
            $table->dropForeign(['equivalente_id']);
        });
        Schema::table('historial_movimientos_inventario', function (Blueprint $table) {
            $table->dropForeign(['inventario_id']);
            $table->dropForeign(['ubicacion_id']);
            $table->dropForeign(['usuario_id']);
        });

        // Ahora que las restricciones no existen, podemos borrar las tablas con seguridad
        Schema::dropIfExists('inventario_despachos');
        Schema::dropIfExists('inventario_asociados');
        Schema::dropIfExists('inventario_equivalentes');
        Schema::dropIfExists('historial_movimientos_inventario');
    }
};