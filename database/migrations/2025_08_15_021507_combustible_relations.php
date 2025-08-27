<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CombustibleRelations extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adding foreign keys to existing tables

        // // Add foreign keys to 'ordenes' table
        // Schema::table('ordenes', function (Blueprint $table) {
        //     $table->foreign('id_vehiculo')->references('id')->on('vehiculos')->onDelete('cascade');
        //     // Assuming 'id_tipo_orden' table exists
        //     // $table->foreign('id_tipo_orden')->references('id')->on('tipos_orden')->onDelete('cascade');
        //     $table->foreign('responsable')->references('id')->on('personal')->onDelete('set null');
        // });
        
        // // Add foreign keys to 'movimientos_combustible' table
        // Schema::table('movimientos_combustible', function (Blueprint $table) {
        //     $table->foreign('deposito_id')->references('id')->on('depositos')->onDelete('cascade');
        //     $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');
        //     $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
        //     $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->onDelete('set null');
        // });
        
        // Add foreign keys to 'inspecciones' table
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->onDelete('cascade');
            $table->foreign('inspector_id')->references('id')->on('personal')->onDelete('set null');
        });

        // Add foreign keys to 'inspeccion_item_respuestas' table
        Schema::table('inspeccion_item_respuestas', function (Blueprint $table) {
            $table->foreign('inspeccion_id')->references('id')->on('inspecciones')->onDelete('cascade');
            $table->foreign('checklist_item_id')->references('id')->on('checklist_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspeccion_item_respuestas', function (Blueprint $table) {
            $table->dropForeign(['inspeccion_id']);
            $table->dropForeign(['checklist_item_id']);
        });
        
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
            $table->dropForeign(['inspector_id']);
        });

        Schema::table('movimientos_combustible', function (Blueprint $table) {
            $table->dropForeign(['deposito_id']);
            $table->dropForeign(['proveedor_id']);
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['vehiculo_id']);
        });
        
        Schema::table('ordenes', function (Blueprint $table) {
            $table->dropForeign(['id_vehiculo']);
            $table->dropForeign(['id_tipo_orden']);
            $table->dropForeign(['responsable']);
        });
    }

}
