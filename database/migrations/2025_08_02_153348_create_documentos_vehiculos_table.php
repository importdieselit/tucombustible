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
        Schema::create('documentos_vehiculos', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->integer('vehiculo_id');
            $table->string('tipo');
            $table->text('doc');
            $table->date('fecha_in');
            $table->date('fecha_venc');
            $table->string('nro')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_vehiculos');
    }
};
