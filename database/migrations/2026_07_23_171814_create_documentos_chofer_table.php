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
        Schema::create('documentos_chofer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chofer_id');
            $table->string('tipo');
            $table->string('doc');
            $table->date('fecha_in');
            $table->date('fecha_venc');
            $table->string('nro');
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
