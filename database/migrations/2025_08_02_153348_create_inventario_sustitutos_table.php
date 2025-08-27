<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventario_sustitutos', function (Blueprint $table) {
            $table->bigInteger('id_inventario_sustituto')->autoIncrement();
            $table->bigInteger('id_usuario')->nullable();
            $table->bigInteger('id_inventario')->nullable();
            $table->bigInteger('id_inventario_r1')->nullable();
            $table->bigInteger('id_inventario_r2')->nullable();
            $table->bigInteger('id_inventario_r3')->nullable();
            $table->bigInteger('id_inventario_r4')->nullable();
            $table->bigInteger('id_inventario_r5')->nullable();
            $table->bigInteger('id_inventario_r6')->nullable();
            $table->bigInteger('id_inventario_r7')->nullable();
            $table->bigInteger('id_inventario_r8')->nullable();
            $table->bigInteger('id_inventario_r9')->nullable();
            $table->bigInteger('id_inventario_r10')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_sustitutos');
    }
};
