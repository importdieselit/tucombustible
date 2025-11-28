<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequisitosCaptacionTable extends Migration
{
    public function up()
    {
        Schema::create('requisitos_captacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tipo_cliente');    // industrial, marino, flota, etc.
            $table->string('codigo');          // ANEXO-A, ANEXO-B...
            $table->string('descripcion');     // Ej: "Registro de Información Fiscal (RIF)"
            $table->boolean('obligatorio')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('requisitos_captacion');
    }
}
