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
        Schema::create('requisitos_captacion', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('tipo_cliente');
            $table->string('codigo');
            $table->string('descripcion');
            $table->boolean('obligatorio')->default(true);
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
        Schema::dropIfExists('requisitos_captacion');
    }
};
