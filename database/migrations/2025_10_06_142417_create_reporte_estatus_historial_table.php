<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReporteEstatusHistorialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reporte_estatus_historial', function (Blueprint $table) {
             $table->id();
            $table->foreignId('reporte_id')->constrained('reportes')->onDelete('cascade');
            $table->foreignId('usuario_modifica_id')->constrained('users'); // Quién hizo el cambio

            $table->string('estatus_anterior', 50);
            $table->string('estatus_nuevo', 50);
            $table->text('nota_cambio')->nullable();

            $table->timestamps(); // Registra la fecha del cambio
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
}
