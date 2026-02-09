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
        Schema::create('inventario_asociados', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->unsignedBigInteger('id_inventario')->nullable();
            $table->unsignedBigInteger('marca')->nullable();
            $table->unsignedBigInteger('modelo')->nullable();
            $table->string('fecha_in', 20);
            $table->text('observacion');
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
        Schema::dropIfExists('inventario_asociados');
    }
};
