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
        Schema::create('reportes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_tipo_reporte');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->text('descripcion');
            $table->string('lugar_reporte');
            $table->string('imagen', 500)->nullable();
            $table->enum('estatus_actual', ['ABIERTO', 'EN_PROCESO', 'CERRADO'])->default('ABIERTO');
            $table->boolean('requiere_ot')->default(false);
            $table->unsignedBigInteger('orden_trabajo_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reportes');
    }
};
