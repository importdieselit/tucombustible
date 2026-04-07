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
            $table->string('nombre', 255)->comment('Razón social o nombre del cliente'); // Aumentado a 255
            $table->string('alias')->nullable();
            $table->string('rif')->unique()->nullable(); // Recomendado: unique para evitar duplicados
            $table->string('contacto', 255)->nullable()->comment('Nombre de la persona de contacto'); // Aumentado a 255
            $table->string('dni', 15)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 255)->nullable(); // Aumentado a 255
            
            // --- NUEVOS CAMPOS DE UBICACIÓN ---
            $table->unsignedBigInteger('estado_id')->nullable();
            $table->unsignedBigInteger('ciudad_id')->nullable();
            // ----------------------------------

            $table->string('direccion')->nullable()->comment('Dirección fiscal');
            $table->text('direccion_operativa')->nullable()->comment('Dirección del lugar de despacho');
            $table->string('ciiu', 20)->nullable()->comment('Código de actividad económica');
            $table->string('sector', 50)->nullable();
            $table->string('periodo', 5)->default('M')->comment('Periodo del cupo: M = Mensual');
            $table->bigInteger('parent')->default(0)->comment('0 = Cliente Padre, ID = Cliente Sucursal');
            $table->string('token_registro', 255)->nullable()->comment('Token único para vincular sucursales');
 
            $table->float('cupo', 10, 0)->nullable()->comment('Cupo mensual aprobado en litros');
            $table->float('disponible', 10, 0)->nullable()->comment('Litros disponibles en el periodo actual');
            $table->integer('prepagado')->default(0);
 
            $table->unsignedBigInteger('registro_paso')->default(1)->comment('FK hacia registro_pasos.id');
            $table->integer('status')->default(1)->comment('0=Inactivo, 1=En registro, 2=Aprobado, 3=Rechazado');
            
            $table->string('telegram_id', 100)->nullable();
 
            $table->timestamps();
 
            // FKs
            $table->foreign('registro_paso')->references('id')->on('registro_pasos')->onUpdate('cascade');
            $table->foreign('estado_id')->references('id')->on('estados')->onDelete('set null');
            $table->foreign('ciudad_id')->references('id')->on('ciudades')->onDelete('set null');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};