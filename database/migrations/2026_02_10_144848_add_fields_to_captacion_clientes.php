<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToCaptacionClientes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('captacion_clientes', function (Blueprint $table) {
            $table->string('estado')->after('telefono')->nullable();
            $table->string('ciudad')->after('estado')->nullable();
            $table->string('tipo_solicitud')->after('tipo_cliente')->comment('nuevo, migracion');
            $table->integer('cantidad_litros')->after('ciudad')->default(0);
            $table->enum('tipo_servicio', ['Maritimo', 'Industrial'])->after('cantidad_litros')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('captacion_clientes', function (Blueprint $table) {
            
        });
    }
}
