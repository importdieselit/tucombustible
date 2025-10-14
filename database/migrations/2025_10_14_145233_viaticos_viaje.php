<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViaticosViaje extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
    {
        Schema::create('viaticos_viaje', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viaje_id');
            $table->string('concepto'); // Ej: Pago Chofer, Viático Desayuno, Peajes
            $table->decimal('monto_base', 8, 2); // Monto original del tabulador
            $table->integer('cantidad'); // Número de unidades (ej. personas, peajes, días)
            
            // Campo editable por el Coordinador Administrativo
            $table->decimal('monto_ajustado', 8, 2)->nullable(); 
            
            $table->unsignedBigInteger('ajustado_por')->nullable(); // ID del admin que hizo el ajuste
            $table->boolean('es_editable')->default(false); // Define si el concepto puede ajustarse

            $table->foreign('viaje_id')->references('id')->on('viajes')->onDelete('cascade');
            $table->foreign('ajustado_por')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viaticos_viaje');
    }
}
