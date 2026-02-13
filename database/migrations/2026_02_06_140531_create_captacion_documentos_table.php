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
        Schema::create('captacion_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('captacion_id');
            $table->integer('requisito_id');
            $table->string('tipo_anexo')->nullable();
            $table->string('nombre_documento')->nullable();
            $table->string('ruta')->nullable();
            $table->boolean('validado')->default(false);
            $table->unsignedBigInteger('validado_por')->nullable();
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
        Schema::dropIfExists('captacion_documentos');
    }
};
