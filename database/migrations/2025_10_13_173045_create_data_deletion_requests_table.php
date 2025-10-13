<?php
// database/migrations/YYYY_MM_DD_create_data_deletion_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_eliminacion', function (Blueprint $table) {
            $table->id();
            // Identificador del usuario (ej. ID de Telegram o Email)
            $table->string('user_identifier')->index();
            $table->string('user_type')->nullable(); // Ej. 'telegram', 'email'
            $table->text('reason')->nullable(); // Razón de la solicitud
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->timestamps();
            $table->unsignedBigInteger('approved_by_admin')->nullable(); // Quién lo aprobó
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_eliminacion');
    }
};