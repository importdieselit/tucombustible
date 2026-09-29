<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_combustible', function (Blueprint $table) {
            $table->string('numero_factura', 100)->nullable()->after('sap');
            $table->string('factura_path', 255)->nullable()->after('numero_factura');
            $table->unsignedBigInteger('usuario_id')->nullable()->after('factura_path');
        });
    }

    public function down(): void
    {
        Schema::table('compras_combustible', function (Blueprint $table) {
            $table->dropColumn(['numero_factura', 'factura_path', 'usuario_id']);
        });
    }
};