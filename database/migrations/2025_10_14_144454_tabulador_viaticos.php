<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabulador_viaticos', function (Blueprint $table) {
            $table->id();
            $table->string('destino')->unique(); // CIUDAD del tabulador
            $table->string('tipo_viaje')->nullable();
            
            // Pagos base (Ejecutivo, Chofer, Ayudante, Peajes)
            $table->decimal('pago_chofer_ejecutivo', 8, 2)->default(0);
            $table->decimal('pago_chofer', 8, 2)->default(0);
            $table->decimal('pago_ayudante', 8, 2)->default(0);
            $table->decimal('peajes_por_zona', 8, 2)->default(0);

            // Viáticos por comida y pernocta
            $table->decimal('viatico_desayuno', 8, 2)->default(0);
            $table->decimal('viatico_almuerzo', 8, 2)->default(0);
            $table->decimal('viatico_cena', 8, 2)->default(0);
            $table->decimal('costo_pernocta', 8, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabulador_viaticos');
    }
};
