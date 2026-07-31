<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            // Define el ancho de la celda. Por defecto 1 (ocupa una posición).
            $table->integer('colspan')->default(1)->after('posicion');
            
            // Define si el slot está subdividido (ej. 'A', 'B', '1', '2'). Nulo si es celda única.
            $table->string('subposicion', 5)->nullable()->after('colspan');
        });
    }


    public function down()
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn(['colspan', 'subposicion']);
        });
    }
};
