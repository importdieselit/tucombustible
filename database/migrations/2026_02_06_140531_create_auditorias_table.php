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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_auditoria')->primary();
            $table->string('tabla');
            $table->string('accion');
            $table->unsignedBigInteger('id_usuario')->nullable()->index('auditorias_id_usuario_foreign');
            $table->text('descripcion');
            $table->timestamp('fecha_accion')->useCurrent();
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
        Schema::dropIfExists('auditorias');
    }
};
