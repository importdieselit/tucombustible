<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        // Tabla de trabajo (Resumen del mes actual)
        Schema::create('reporte_eficiencia_actual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('users');
            $table->integer('total_realizados')->default(0);
            $table->integer('salidas_tardias')->default(0);
            $table->integer('entradas_tardias')->default(0);
            $table->timestamp('ultima_actualizacion')->useCurrent();
        });

        // Tabla Histórica (Cierres mensuales definitivos)
        Schema::create('historico_eficiencia_checklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('periodo', 7); // Ejemplo: 2026-05
            $table->integer('total_realizados');
            $table->integer('salidas_tardias');
            $table->integer('entradas_tardias');
            $table->timestamp('fecha_cierre')->useCurrent();
        });
    }

    public function down() {
        Schema::dropIfExists('historico_eficiencia_checklist');
        Schema::dropIfExists('reporte_eficiencia_actual');
    }
};