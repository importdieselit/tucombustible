<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estatus_data', function (Blueprint $table) {
            $table->integer('id_estatus');
            $table->string('css', 50)->nullable();
            $table->string('hex', 50)->nullable();
            $table->string('icon_auto', 50)->nullable();
            $table->string('auto', 50)->nullable();
            $table->string('icon_orden', 50)->nullable();
            $table->string('orden', 50)->nullable();
            $table->string('icon_request', 30);
            $table->string('request', 20);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estatus_data');
    }
};
