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
        Schema::create('reporte_estatus_historial', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('reporte_id');
            $table->unsignedBigInteger('usuario_modifica_id');
            $table->string('estatus_anterior', 50);
            $table->string('estatus_nuevo', 50);
            $table->text('nota_cambio')->nullable();
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
        Schema::dropIfExists('reporte_estatus_historial');
    }
};
