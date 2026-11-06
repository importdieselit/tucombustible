<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_bunkering_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones_bunkering')->onDelete('cascade');
            $table->string('tipo_producto'); // MGO, VLSMGO, HLSMGO, etc.$table->decimal('volumen', 12, 3); // Cantidad métrica o barriles según se ingrese
            $table->string('unidad_medida')->default('Metric Tons (MT)'); // MT, Litros, Barriles$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_bunkering_detalles');
    }
};
