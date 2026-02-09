<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->integer('id_taller_personal')->comment('Id');
            $table->integer('id_taller')->comment('Id Taller');
            $table->integer('id_usuario')->comment('Id Usuario / Empresa');
            $table->integer('estatus')->nullable()->comment('Estatus del Mecanico (Activo / Inactivo)');
            $table->string('nombre', 30)->comment('Nombre Mecanico');
            $table->string('apellido', 30)->comment('Apellido');
            $table->string('ci', 15)->nullable()->comment('Cedula de Identidad');
            $table->integer('dependencia')->nullable()->default(0)->comment('Dependencia');
            $table->string('cargo', 50)->comment('Cargo');
            $table->text('direccion')->nullable()->comment('Direccion');
            $table->text('telefono')->nullable()->comment('Telefonos');
            $table->string('email', 50)->nullable()->comment('Email');
            $table->text('observaciones')->nullable()->comment('Observaciones');
            $table->char('fecha_in', 10)->nullable()->comment('Fecha Ingreso');
            $table->integer('jefe_taller')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal');
    }
};
