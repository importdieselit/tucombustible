<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes_lubricantes', function (Blueprint $table) {
            $table->string('telefono', 11)->nullable()
                  ->after('email')
                  ->comment('Teléfono de contacto');
        });
    }

    public function down(): void
    {
        Schema::table('clientes_lubricantes', function (Blueprint $table) {
            $table->dropColumn('telefono');
        });
    }
};