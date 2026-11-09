<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('categoria_iso')->nullable()->after('descripcion');
            $table->string('densidad')->nullable()->after('categoria_iso');
            $table->string('viscosidad')->nullable()->after('densidad');
            $table->string('azufre')->nullable()->after('viscosidad');
            $table->text('aplicacion')->nullable()->after('azufre');
        });
    }

    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['categoria_iso', 'densidad', 'viscosidad', 'azufre', 'aplicacion']);
        });
    }
};