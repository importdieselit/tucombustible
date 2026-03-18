<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->comment('Razón social o nombre del cliente');
            $table->string('alias')->nullable();
            $table->string('rif')->nullable();
            $table->string('contacto', 50)->nullable()->comment('Nombre de la persona de contacto');
            $table->string('dni', 15)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('direccion')->nullable()->comment('Dirección fiscal');
            $table->text('direccion_operativa')->nullable()->comment('Dirección del lugar de despacho');
            $table->string('ciiu', 20)->nullable()->comment('Código de actividad económica');
            $table->string('sector', 50)->nullable();
            $table->string('periodo', 5)->default('M')->comment('Periodo del cupo: M = Mensual');
            $table->bigInteger('parent')->default(0)->comment('0 = Cliente Padre, ID = Cliente Sucursal');
            $table->string('token_registro', 255)->nullable()->comment('Token único para vincular sucursales');
 
            // Cupo de combustible (referencia rápida, el detalle vive en cliente_cupos)
            $table->float('cupo', 10, 0)->nullable()->comment('Cupo mensual aprobado en litros');
            $table->float('disponible', 10, 0)->nullable()->comment('Litros disponibles en el periodo actual');
            $table->integer('prepagado')->default(0);
 
            // Registro y aprobación
            $table->unsignedBigInteger('registro_paso')->default(1)->comment('FK hacia registro_pasos.id');
            $table->integer('status')->default(1)->comment('0=Inactivo, 1=En registro, 2=Aprobado, 3=Rechazado');
            $table->timestamp('fecha_aprobacion')->nullable()->comment('Fecha en que el cliente fue aprobado o rechazado');
 
            // Comunicación
            $table->string('telegram_id', 100)->nullable();
 
            $table->timestamps();
 
            // FK hacia registro_pasos
            $table->foreign('registro_paso')
                  ->references('id')
                  ->on('registro_pasos')
                  ->onUpdate('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['registro_paso']);
        });
 
        Schema::dropIfExists('clientes');
    }
};
