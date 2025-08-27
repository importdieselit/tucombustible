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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->bigInteger('id_auditoria')->autoIncrement();
            $table->string('tabla');
            $table->string('accion');
            $table->bigInteger('id_usuario')->nullable();
            $table->text('descripcion');
            $table->timestamp('fecha_accion')->default('CURRENT_TIMESTAMP');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
