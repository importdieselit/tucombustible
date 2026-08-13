<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Control de archivos procesados
        Schema::create('processed_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->unique();
            $table->date('report_date');
            $table->timestamps();
        });

        // Histórico de registros del reporte
        Schema::create('report_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processed_file_id')->constrained('processed_files')->onDelete('cascade');
            $table->date('report_date');
            $table->string('tipo');
            $table->string('cuenta');
            $table->string('descuenta')->nullable();
            $table->decimal('monto', 15, 4);
            $table->string('campo1')->nullable();
            $table->string('tipo_oper')->nullable();
            $table->integer('orden')->nullable();
            $table->integer('reng')->nullable();
            $table->timestamps();

            // Índices de optimización para consultas rápidas por fecha
            $table->index('report_date');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_records');
        Schema::dropIfExists('processed_files');
    }
};
