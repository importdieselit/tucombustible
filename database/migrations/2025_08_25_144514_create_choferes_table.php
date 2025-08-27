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
        Schema::create('choferes', function (Blueprint $table) {
            $table->id();
            $table->string('licencia_numero')->unique();
            $table->date('licencia_vencimiento');
            $table->string('documento_vialidad_numero')->nullable();
            $table->date('documento_vialidad_vencimiento')->nullable();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->onDelete('set null');
            $table->foreignId('persona_id')->constrained('personas')->onDelete('cascade'); // Agregamos la llave foránea
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('choferes');
    }
};
