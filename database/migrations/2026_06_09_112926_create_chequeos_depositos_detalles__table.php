<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chequeos_depositos_detalles', function (Blueprint $table) {
            $table->id();
            
            // Relación con la cabecera (Si se borra el chequeo, se borra su detalle)
            $table->foreignId('id_chequeo')
                  ->constrained('chequeos_depositos')
                  ->onDelete('cascade');
            
            // Relación con el tanque específico (Tu tabla se llama 'depositos')
            $table->foreignId('id_deposito')
                  ->constrained('depositos')
                  ->onDelete('restrict');
            
            // Mediciones físicas y calculadas con precisión decimal
            $table->decimal('centimetros_medidos', 6, 2);
            $table->decimal('litros_calculados', 12, 2);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chequeos_depositos_detalles');
    }
};