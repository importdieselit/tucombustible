<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->bigInteger('id_usuario');
            $table->integer('estatus')->nullable();
            $table->string('flota')->nullable();
            $table->bigInteger('marca')->nullable();
            $table->bigInteger('modelo')->nullable();
            $table->string('placa')->nullable();
            $table->string('tipo')->nullable();
            $table->string('tipo_diagrama')->nullable();
            $table->string('serial_motor')->nullable();
            $table->string('serial_carroceria')->nullable();
            $table->string('transmision')->nullable();
            $table->string('color')->nullable();
            $table->string('anno')->nullable();
            $table->integer('kilometraje')->nullable();
            $table->integer('sucursal')->nullable();
            $table->integer('ubicacion')->nullable();
            $table->string('ubicacion_1')->nullable();
            $table->string('poliza_numero')->nullable();
            $table->date('poliza_fecha_in')->nullable();
            $table->date('poliza_fecha_out')->nullable();
            $table->string('agencia')->nullable();
            $table->text('observacion')->nullable();
            $table->date('salida_fecha')->nullable();
            $table->text('salida_motivo')->nullable();
            $table->integer('salida_id_usuario')->nullable();
            $table->date('fecha_in')->nullable();
            $table->float('vol')->nullable();
            $table->integer('km_contador')->nullable();
            $table->string('condicion')->nullable();
            $table->integer('km_mantt')->default('0');
            $table->double('cobertura')->nullable();
            $table->string('tipo_poliza')->nullable();
            $table->integer('id_poliza')->nullable();
            $table->string('certif_reg')->nullable();
            $table->string('disp')->default('S');
            $table->double('carga_max')->nullable();
            $table->double('fuel')->nullable();
            $table->string('tipo_combustible')->nullable();
            $table->integer('HP')->nullable();
            $table->integer('CC')->nullable();
            $table->decimal('altura')->nullable();
            $table->decimal('ancho')->nullable();
            $table->decimal('largo')->nullable();
            $table->decimal('consumo')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('oil')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
