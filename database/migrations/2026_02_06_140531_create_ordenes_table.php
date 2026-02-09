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
        Schema::create('ordenes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('nro_orden', 20)->nullable()->comment('Numero de Orden por Usuario');
            $table->string('tipo', 30)->nullable()->comment('Servicio, Reparacion, matenimiento');
            $table->string('estatus', 20)->nullable()->comment('Abierta, Revisión, Reparación, Facturada y Cerrada');
            $table->unsignedBigInteger('id_vehiculo')->nullable();
            $table->integer('kilometraje')->nullable()->comment('Kilometraje del Vehiculos en apertura de orden');
            $table->text('descripcion_1')->nullable()->comment('Descripcion');
            $table->text('descripcion_2')->nullable();
            $table->text('descripcion_3')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('id_us_in')->nullable();
            $table->date('fecha_in')->nullable()->comment('Fecha Apertura');
            $table->time('hora_in')->nullable();
            $table->date('fecha_out')->nullable()->comment('Fecha_Cierre');
            $table->time('hora_out')->nullable();
            $table->integer('id_us_out')->nullable();
            $table->integer('id_plan')->default(0);
            $table->text('observacion')->nullable();
            $table->integer('origen')->nullable();
            $table->text('anulacion')->nullable();
            $table->string('chfr', 50)->nullable();
            $table->string('responsable', 50)->nullable()->default('Sin Indicar');
            $table->integer('parent')->nullable();
            $table->bigInteger('inspeccion_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ordenes');
    }
};
