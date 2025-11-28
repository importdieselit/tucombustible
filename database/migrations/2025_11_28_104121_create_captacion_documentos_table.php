<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaptacionDocumentosTable extends Migration
{
    public function up()
    {
        Schema::create('captacion_documentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('captacion_id');
            $table->string('tipo_anexo')->nullable(); // A, B, C, D, E, F
            $table->string('nombre_documento')->nullable();
            $table->string('ruta')->nullable();
            $table->boolean('validado')->default(false);
            $table->unsignedBigInteger('validado_por')->nullable();
            $table->timestamps();

            $table->foreign('captacion_id')->references('id')->on('captacion_clientes')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('captacion_documentos');
    }
}
