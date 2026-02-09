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
        Schema::create('repostaje_vehiculos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_vehiculo')->nullable();
            $table->unsignedBigInteger('id_tanque')->nullable();
            $table->float('qty', 10, 0);
            $table->float('qtya', 10, 0)->nullable();
            $table->float('rest', 10, 0);
            $table->dateTime('fecha');
            $table->text('obs')->nullable();
            $table->unsignedBigInteger('id_us');
            $table->longText('pic')->nullable();
            $table->tinyInteger('type')->nullable();
            $table->string('ref', 100)->nullable();
            $table->string('placa_ext', 50)->nullable();
            $table->string('nombre_ext', 50)->nullable();
            $table->string('origin')->nullable();
            $table->unsignedBigInteger('id_admin')->nullable();
            $table->string('ticket', 50)->nullable();
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
        Schema::dropIfExists('repostaje_vehiculos');
    }
};
