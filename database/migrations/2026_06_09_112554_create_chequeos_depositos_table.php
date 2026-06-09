<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chequeos_depositos', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('id_sede')
                  ->constrained('sedes')
                  ->onDelete('restrict');
            
            // Quién hizo la medición
            $table->foreignId('id_usuario')
                  ->constrained('users')
                  ->onDelete('restrict');
            
            $table->date('fecha');
            $table->enum('turno', ['Mañana', 'Tarde/Noche']);
            
            $table->timestamps();

            // Índice compuesto para evitar que carguen dos veces el mismo turno en la misma sede el mismo día
            $table->unique(['id_sede', 'fecha', 'turno'], 'uid_sede_fecha_turno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chequeos_depositos');
    }
};