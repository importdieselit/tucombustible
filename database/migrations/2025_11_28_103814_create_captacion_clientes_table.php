<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaptacionClientesTable extends Migration
{
    public function up()
    {
        Schema::create('captacion_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cliente_id')->nullable(); // se llena cuando se aprueba y se crea cliente real
            $table->string('tipo_cliente')->nullable();
            $table->string('rif')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('representante')->nullable();
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            $table->json('datos_adicionales')->nullable();
            $table->enum('estatus_captacion', [
                'registro_inicial',
                'por_verificar',
                'documento_incompleto',
                'planillas_enviadas',
                'pendiente_inspeccion',
                'rechazado_inspeccion',
                'aprobado'
            ])->default('registro_inicial');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('captacion_clientes');
    }
}
