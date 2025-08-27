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
        Schema::create('historial_mantenimiento', function (Blueprint $table) {
            $table->bigInteger('id_mantenimiento')->autoIncrement();
            $table->bigInteger('id_vehiculo');
            $table->bigInteger('id_servicio');
            $table->integer('kilometraje_mantenimiento');
            $table->date('fecha_mantenimiento');
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_mantenimiento');
    }
};
