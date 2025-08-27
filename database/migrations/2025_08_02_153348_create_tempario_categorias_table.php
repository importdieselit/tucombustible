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
        Schema::create('tempario_categorias', function (Blueprint $table) {
            $table->integer('id_tempario_categoria')->autoIncrement();
            $table->integer('id_usuario');
            $table->string('codigo');
            $table->string('categoria');
            $table->float('costo_mo')->default('0.00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tempario_categorias');
    }
};
