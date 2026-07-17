<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumos_operativos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sede_id')->comment('Sede donde se despacha el consumo');
            $table->unsignedBigInteger('vehiculo_id')->nullable()->comment('Vehículo de la flota (nulo si es maquinaria/planta)');
            $table->string('equipo_maquinaria', 150)->nullable()->comment('Nombre del equipo/planta si no es vehículo');
            $table->unsignedBigInteger('deposito_id')->comment('Tanque específico de donde succiona');
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->decimal('cantidad_litros', 12, 2);
            $table->unsignedBigInteger('user_id')->comment('Operador que despacha');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Llaves foráneas
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('restrict');
            $table->foreign('deposito_id')->references('id')->on('depositos')->onDelete('restrict');
            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumos_operativos');
    }
};