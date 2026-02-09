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
        Schema::create('inventario_sustitutos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_inventario_sustituto');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->unsignedBigInteger('id_inventario')->nullable();
            $table->unsignedBigInteger('id_inventario_r1')->nullable();
            $table->unsignedBigInteger('id_inventario_r2')->nullable();
            $table->unsignedBigInteger('id_inventario_r3')->nullable();
            $table->unsignedBigInteger('id_inventario_r4')->nullable();
            $table->unsignedBigInteger('id_inventario_r5')->nullable();
            $table->unsignedBigInteger('id_inventario_r6')->nullable();
            $table->unsignedBigInteger('id_inventario_r7')->nullable();
            $table->unsignedBigInteger('id_inventario_r8')->nullable();
            $table->unsignedBigInteger('id_inventario_r9')->nullable();
            $table->unsignedBigInteger('id_inventario_r10')->nullable();
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
        Schema::dropIfExists('inventario_sustitutos');
    }
};
