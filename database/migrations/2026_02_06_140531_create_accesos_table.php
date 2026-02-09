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
        Schema::create('accesos', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('id_usuario')->index('accesos_id_usuario_foreign');
            $table->unsignedBigInteger('id_modulo')->nullable()->index('accesos_id_modulo_foreign');
            $table->boolean('read')->default(true);
            $table->boolean('update')->default(true);
            $table->boolean('create')->default(true);
            $table->boolean('delete')->default(false);
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
        Schema::dropIfExists('accesos');
    }
};
