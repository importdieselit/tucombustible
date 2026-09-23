<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParametroLegalsTable extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_legales', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique()->comment('Ej: SALARIO_MINIMO, TOPE_VACACIONES');
            $table->string('valor')->comment('El valor configurado');
            $table->string('tipo_dato', 20)->default('numero')->comment('numero, texto, porcentaje, moneda');
            $table->string('descripcion')->nullable()->comment('Explicación para el usuario administrador');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_legales');
    }
}
