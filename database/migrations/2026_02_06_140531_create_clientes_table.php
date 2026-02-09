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
        Schema::create('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('nombre', 100)->comment('Nombre del cliente o empresa');
            $table->string('alias')->nullable();
            $table->string('contacto', 50)->nullable()->comment('Nombre de la persona de contacto');
            $table->string('dni', 15)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 50)->nullable();
            $table->timestamps();
            $table->string('rif')->nullable();
            $table->string('direccion')->nullable();
            $table->float('disponible', 10, 0)->nullable();
            $table->integer('prepagado')->default(0);
            $table->float('cupo', 10, 0)->nullable();
            $table->string('ciiu', 20)->nullable();
            $table->bigInteger('parent')->default(0);
            $table->string('periodo', 5)->default('M');
            $table->string('sector', 50)->nullable();
            $table->string('telegram_id', 100)->nullable();
            $table->integer('status')->default(1);
            $table->string('tipo', 50)->default('DIESEL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clientes');
    }
};
