<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones_bunkering', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido')->unique(); // Ej: 261105001
            
            // Campos Abiertos de Operación y Buque
            $table->string('nombre_buque');
            $table->string('bandera_buque')->nullable();
            $table->string('imo_buque')->nullable();
            $table->string('pagador')->nullable();
            $table->string('punto_entrega');
            $table->string('vendedor')->nullable();
            
            // Logística y Agencia
            $table->string('metodo_despacho')->nullable(); // Ej: Bunkering directo / STS
            $table->string('centro_suministro')->nullable();
            $table->string('agencia_naviera')->nullable();
            
            $table->date('fecha_nominacion')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones_bunkering');
    }
};
