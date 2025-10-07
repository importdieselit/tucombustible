<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            // --- Clasificación y Origen ---
            $table->foreignId('id_tipo_reporte')->constrained('tipo_reporte');
            $table->foreignId('usuario_id')->nullable()->constrained('users'); // Usuario de Laravel que reporta
            $table->unsignedBigInteger('cliente_id')->nullable(); // ID de Cliente (si es externo al sistema)

            // --- Contenido y Logística ---
            $table->text('descripcion');
            $table->string('lugar_reporte', 255);
            $table->string('imagen', 500)->nullable();

            // --- Ciclo de Vida del Ticket ---
            $table->enum('estatus_actual', ['ABIERTO', 'EN_PROCESO', 'CERRADO'])->default('ABIERTO');
            $table->boolean('requiere_ot')->default(false);
            $table->unsignedBigInteger('orden_trabajo_id')->nullable(); // FK a futuras órdenes de trabajo

            $table->timestamps();
            
            // Índices para optimizar búsquedas por estatus o cliente.
            $table->index(['cliente_id', 'estatus_actual']); 
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reportes');
    }
}
