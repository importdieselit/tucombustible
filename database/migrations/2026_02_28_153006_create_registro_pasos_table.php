
Copiar

<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_pasos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->comment('Descripción del paso del registro');
            $table->integer('orden')->comment('Orden de aparición en el flujo');
            $table->tinyInteger('activo')->default(1)->comment('1 = activo, 0 = desactivado sin eliminar');
            $table->timestamps();
        });
 
        // Seeder integrado — los 5 pasos del nuevo flujo de registro
        DB::table('registro_pasos')->insert([
            [
                'id'        => 1,
                'nombre'    => 'Registro de Datos',
                'orden'     => 1,
                'activo'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'        => 2,
                'nombre'    => 'En espera de Respuesta del Ministerio de Hidrocarburos',
                'orden'     => 2,
                'activo'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'        => 3,
                'nombre'    => 'Fecha de Inspección Asignada',
                'orden'     => 3,
                'activo'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'        => 4,
                'nombre'    => 'En espera de Respuesta del Ministerio de Hidrocarburos',
                'orden'     => 4,
                'activo'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'        => 5,
                'nombre'    => 'Cliente Aprobado / Cliente Rechazado',
                'orden'     => 5,
                'activo'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
 
    public function down(): void
    {
        Schema::dropIfExists('registro_pasos');
    }
};
