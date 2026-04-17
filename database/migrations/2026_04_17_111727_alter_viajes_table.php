<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterViajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('viajes', function (Blueprint $table) {
            // 1: Diesel, 2: MGO, 3: Flete, 4: Compra
            if (!Schema::hasColumn('viajes', 'tipo_planificacion')) {
                $table->integer('tipo_planificacion')->after('id')->index();
            }

            // Sede de ImporDiesel que despacha o recibe
            $table->unsignedBigInteger('sede_id')->nullable()->after('tipo_planificacion');
            $table->foreign('sede_id')->references('id')->on('sedes');

            // Gestión de Ayudante (Mejorado)
            // Nota: Si ya tienes una tabla de 'ayudantes' o 'empleados', cámbiala aquí
            $table->unsignedBigInteger('ayudante_id')->nullable()->after('chofer_id');
            
            // Campos para Fletes
            $table->string('tipo_remolque')->nullable(); // Cisterna, Batea, LowBoy, Cava
            $table->string('punto_salida')->nullable();   
            $table->string('punto_llegada')->nullable();  
            
            // Campos para Compras
            $table->string('codigo_sap')->nullable();

            // Cambiamos el nombre de un campo para que sea más descriptivo en Fletes/MGO
            // si el cliente no está en la base de datos (Externo)
            if (!Schema::hasColumn('viajes', 'nombre_cliente_externo')) {
                $table->string('nombre_cliente_externo')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
