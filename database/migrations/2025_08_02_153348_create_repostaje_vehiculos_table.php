<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repostaje_vehiculos', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->bigInteger('id_vehiculo')->nullable();
            $table->bigInteger('id_tanque')->nullable();
            $table->float('qty');
            $table->float('qtya')->nullable();
            $table->float('rest');
            $table->dateTime('fecha');
            $table->text('obs')->nullable();
            $table->bigInteger('id_us');
            $table->text('pic')->nullable();
            $table->integer('type')->nullable();
            $table->string('ref')->nullable();
            $table->string('placa_ext')->nullable();
            $table->string('nombre_ext')->nullable();
            $table->string('origin')->nullable();
            $table->bigInteger('id_admin')->nullable();
            $table->string('ticket')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repostaje_vehiculos');
    }
};
