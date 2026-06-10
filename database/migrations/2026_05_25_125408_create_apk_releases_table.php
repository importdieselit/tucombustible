<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('apk_releases', function (Blueprint $table) {
            $table->id();
            $table->integer('version_code')->unique(); // Ej: 15 (Para comparar numéricamente)
            $table->string('version_name');          // Ej: "1.0.5" (Visual)
            $table->string('file_path');             // Ruta del archivo APK en el servidor
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('apk_releases');
    }
};