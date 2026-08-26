<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ==========================================================
        // LIMPIEZA PREVENTIVA
        // Evita el error SQL 1050 si la migración se reintenta tras un fallo
        // ==========================================================
        Schema::dropIfExists('historial_movimientos_inventario');
        Schema::dropIfExists('inventario_despachos');
        Schema::dropIfExists('inventario_asociados');
        Schema::dropIfExists('inventario_equivalentes');

        // ==========================================================
        // FASE 1: CREACIÓN DE TABLAS Y CAMPOS MAESTROS
        // ==========================================================

        Schema::create('inventario_equivalentes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventario_id');
            $table->unsignedBigInteger('equivalente_id');
        });

        Schema::create('inventario_asociados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventario_id');
            $table->unsignedBigInteger('modelo_vehiculo_id');
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
            $table->unsignedBigInteger('inventario_id')->nullable();
            $table->unsignedBigInteger('ubicacion_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            
            $table->enum('tipo_movimiento', ['ENTRADA', 'SALIDA', 'TRASLADO']);
            $table->enum('tipo_operacion', ['COMPRA', 'DESPACHO', 'DEVOLUCION', 'AJUSTE_CONTEO', 'DESECHO', 'INICIAL']);
            $table->string('documento_referencia')->nullable();
            
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

        Schema::table('inventario_equivalentes', function (Blueprint $table) {
            $table->foreign('inventario_id')->references('id')->on('inventario')->onDelete('cascade');
            $table->foreign('equivalente_id')->references('id')->on('inventario')->onDelete('cascade');
            $table->unique(['inventario_id', 'equivalente_id'], 'uq_inventario_equiv');
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

        Schema::dropIfExists('inventario_despachos');
        Schema::dropIfExists('inventario_asociados');
        Schema::dropIfExists('inventario_equivalentes');
        Schema::dropIfExists('historial_movimientos_inventario');
    }
};