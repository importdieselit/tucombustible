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
        Schema::create('repost_tanques', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_tanque');
            $table->unsignedBigInteger('id_us');
            $table->float('lectura_in', 10, 0)->nullable();
            $table->float('lectura_out', 10, 0)->nullable();
            $table->float('qty', 10, 0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repost_tanques');
    }
};
