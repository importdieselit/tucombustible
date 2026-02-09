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
        Schema::create('orden_fotos', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('orden_id');
            $table->text('ruta_archivo');
            $table->string('descripcion')->nullable();
            $table->timestamp('created_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orden_fotos');
    }
};
