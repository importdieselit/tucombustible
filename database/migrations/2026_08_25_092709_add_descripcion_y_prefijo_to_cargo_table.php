<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescripcionYPrefijoToCargoTable extends Migration
{
   public function up(): void
    {
        Schema::table('cargo', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('nombre');
            $table->string('prefijo', 5)->nullable()->comment('Ej: CHF para Chofer')->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('cargo', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'prefijo']);
        });
    }
}
