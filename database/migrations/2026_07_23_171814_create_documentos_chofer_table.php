<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentosChoferTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('documentos_chofer');

        Schema::create('documentos_chofer', function (Blueprint $table) {
            $table->id();
            
            // Si choferes.id es INT en lugar de BIGINT, usa integer() o unsignedInteger()
            $table->unsignedBigInteger('chofer_id'); 
            
            $table->string('tipo');
            $table->string('doc');
            $table->date('fecha_in');
            $table->date('fecha_venc');
            $table->string('nro');

            // Cambiar 'id' por 'id_chofer' si la columna se llama diferente en choferes
            $table->foreign('chofer_id')->references('id')->on('choferes')->onDelete('cascade');
            
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
        Schema::dropIfExists('documentos_chofer');  
    }
}