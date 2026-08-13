<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos_precargados', function (Blueprint $table) {
            $table->bigIncrements('id')->change();

            // Eliminar campos obsoletos
            $table->dropColumn(['fecha_hora_despacho', 'tipo_producto']);

            // Renombrar cantidad para estandarizar
            $table->renameColumn('cantidad_cargada', 'cantidad_litros');

            // Agregar relaciones, usuario de auditoría y flag de precinto
            $table->foreignId('id_sede')->after('id_vehiculo')->constrained('sedes')->onDelete('restrict');
            $table->foreignId('id_deposito')->nullable()->after('id_sede')->constrained('depositos')->onDelete('restrict');
            $table->foreignId('id_tipo_combustible')->after('id_deposito')->constrained('tipos_combustible')->onDelete('restrict');
            $table->foreignId('id_usuario')->after('id_tipo_combustible')->constrained('users')->onDelete('restrict');
            $table->boolean('esta_precintado')->default(false)->after('id_usuario');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos_precargados', function (Blueprint $table) {
            $table->dropForeign(['id_sede']);
            $table->dropForeign(['id_deposito']);
            $table->dropForeign(['id_tipo_combustible']);
            $table->dropForeign(['id_usuario']);

            $table->dropColumn(['id_sede', 'id_deposito', 'id_tipo_combustible', 'id_usuario', 'esta_precintado']);

            $table->renameColumn('cantidad_litros', 'cantidad_cargada');
            $table->timestamp('fecha_hora_despacho')->nullable();
            $table->string('tipo_producto', 1);
        });
    }
};