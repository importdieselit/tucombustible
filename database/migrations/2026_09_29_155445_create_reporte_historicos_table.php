<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReporteHistoricosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('reportes_historicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_reporte')->index(); // Ej: 'dashboard_comercial', 'operaciones_diarias'
            $table->date('fecha')->index();
            $table->enum('turno', ['matutino', 'vespertino', 'diario'])->index(); // 'diario' por si hay reportes de un solo cierre
            $table->json('contenido'); // Aquí se guardará todo el arreglo de datos congelado
            $table->timestamps();
            
            // Evitar duplicados del mismo reporte, en la misma fecha y turno
            $table->unique(['nombre_reporte', 'fecha', 'turno']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reporte_historicos');
    }
}
