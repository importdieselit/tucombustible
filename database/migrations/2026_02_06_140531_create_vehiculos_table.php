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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->unsignedBigInteger('id_cliente')->default(348);
            $table->integer('estatus')->nullable();
            $table->string('flota', 100)->nullable();
            $table->unsignedBigInteger('marca')->nullable();
            $table->unsignedBigInteger('modelo')->nullable();
            $table->string('placa', 50)->nullable();
            $table->string('tipo', 10)->nullable();
            $table->string('tipo_diagrama', 40)->nullable();
            $table->string('serial_motor', 100)->nullable();
            $table->string('serial_carroceria', 100)->nullable();
            $table->string('transmision', 20)->nullable();
            $table->string('color', 40)->nullable();
            $table->string('anno', 20)->nullable();
            $table->integer('kilometraje')->nullable();
            $table->integer('horas_trabajo')->nullable()->default(0);
            $table->integer('sucursal')->nullable();
            $table->integer('ubicacion')->nullable();
            $table->string('ubicacion_1', 100)->nullable();
            $table->string('poliza_numero', 100)->nullable();
            $table->date('poliza_fecha_in')->nullable();
            $table->date('poliza_fecha_out')->nullable();
            $table->string('agencia', 50)->nullable();
            $table->text('observacion')->nullable();
            $table->date('salida_fecha')->nullable();
            $table->text('salida_motivo')->nullable();
            $table->integer('salida_id_usuario')->nullable();
            $table->date('fecha_in')->nullable();
            $table->float('vol', 10, 0)->nullable();
            $table->integer('km_contador')->default(0);
            $table->integer('hrs_contador')->default(0);
            $table->string('condicion')->nullable()->comment('recuperacion, repuesto, venta');
            $table->integer('km_mantt')->nullable()->default(0);
            $table->integer('hrs_mantt')->default(0);
            $table->double('cobertura')->nullable();
            $table->string('tipo_poliza')->nullable();
            $table->integer('id_poliza')->nullable();
            $table->string('certif_reg')->nullable();
            $table->string('disp', 1)->default('S');
            $table->double('carga_max', 10, 2)->nullable();
            $table->double('fuel')->nullable();
            $table->string('tipo_combustible')->nullable();
            $table->integer('HP')->nullable();
            $table->integer('CC')->nullable();
            $table->decimal('altura')->nullable();
            $table->decimal('ancho')->nullable();
            $table->decimal('largo')->nullable();
            $table->decimal('consumo')->nullable();
            $table->timestamps();
            $table->string('oil')->nullable();
            $table->string('rotc')->nullable();
            $table->date('rotc_venc')->nullable();
            $table->string('rcv')->nullable();
            $table->string('racda')->nullable();
            $table->string('semcamer')->nullable();
            $table->string('homologacion_intt')->nullable();
            $table->string('permiso_intt')->nullable();
            $table->boolean('gps')->default(false);
            $table->boolean('es_flota')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehiculos');
    }
};
