<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 255)->comment('Nombre o razón social del cliente');
            $table->string('rif', 20)->unique()->comment('RIF del cliente lubricante');
            $table->string('email', 100)->comment('Correo electrónico de contacto');
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('clientes_lubricantes');
    }
};
