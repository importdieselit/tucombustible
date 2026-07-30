<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoordenadasToDepositosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('depositos', function (Blueprint $table) {
            $table->float('orden_x')->nullable()->after('alto');
            $table->float('orden_z')->nullable()->after('orden_x');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('depositos', function (Blueprint $table) {
            $table->dropColumn(['orden_x', 'orden_z']);
        });
    }
}
