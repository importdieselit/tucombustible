<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aforo', function (Blueprint $table) {
            $table->id();
            // Asumiendo que usarás los IDs 1, 2, 3, 4, etc. para tus tanques
            $table->bigInteger('deposito_id')->comment('ID del tanque (1 para Tanque 01)'); 
            
            // Profundidad en centímetros (dos decimales)
            $table->decimal('profundidad_cm', 6, 2)->comment('Profundidad medida con la varilla.'); 
            
            // Volumen en Litros (dos decimales para alta precisión)
            $table->decimal('litros', 12, 2)->comment('Volumen real asociado a la profundidad.'); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aforo');
    }
};