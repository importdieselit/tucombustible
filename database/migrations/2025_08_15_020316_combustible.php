    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    // Migración para la tabla de depósitos de combustible
    class Combustible extends Migration
    {
        /**
        * Ejecuta las migraciones.
        */
        public function up(): void
        {
        // Table for fuel tanks
            // Schema::create('depositos', function (Blueprint $table) {
            //     $table->id();
            //     $table->string('nombre', 50)->unique();
            //     $table->integer('capacidad_litros');
            //     $table->integer('nivel_actual_litros')->default(0);
            //     $table->integer('nivel_alerta_litros')->default(0);
            //     $table->timestamps();
            // });

            // // Table for fuel suppliers
            // Schema::create('proveedores', function (Blueprint $table) {
            //     $table->id();
            //     $table->string('nombre', 100);
            //     $table->string('contacto', 50)->nullable();
            //     $table->string('telefono', 20)->nullable();
            //     $table->string('email', 50)->unique()->nullable();
            //     $table->timestamps();
            // });

            // Table for clients
            // Schema::create('clientes', function (Blueprint $table) {
            //     $table->id();
            //     $table->string('nombre', 100);
            //     $table->string('contacto', 50)->nullable();
            //     $table->string('telefono', 20)->nullable();
            //     $table->string('email', 50)->unique()->nullable();
            //     $table->timestamps();
            // });
            
            // // Table for fuel movements (in and out)
            // Schema::create('movimientos_combustible', function (Blueprint $table) {
            //     $table->id();
            //     $table->enum('tipo_movimiento', ['entrada', 'salida']);
            //     $table->unsignedBigInteger('deposito_id'); // Will be a foreign key
            //     $table->unsignedBigInteger('proveedor_id')->nullable(); // Optional foreign key
            //     $table->unsignedBigInteger('cliente_id')->nullable(); // Optional foreign key
            //     $table->unsignedBigInteger('vehiculo_id')->nullable(); // Optional foreign key
            //     $table->integer('cantidad_litros');
            //     $table->text('observaciones')->nullable();
            //     $table->timestamps();
            // });

            // Table for checklist items
            // Schema::create('checklist_items', function (Blueprint $table) {
            //     $table->id();
            //     $table->string('item');
            //     $table->enum('tipo', ['pre-salida', 'otro'])->default('pre-salida');
            //     $table->boolean('activo')->default(true);
            //     $table->timestamps();
            // });

            // // Table for vehicle inspections
            // Schema::create('inspecciones', function (Blueprint $table) {
            //     $table->id();
            //     $table->unsignedBigInteger('vehiculo_id'); // Will be a foreign key
            //     $table->dateTime('fecha')->useCurrent();
            //     $table->unsignedBigInteger('inspector_id')->nullable(); // Will be a foreign key to 'personal'
            //     $table->boolean('aprobado');
            //     $table->text('observaciones')->nullable();
            //     $table->timestamps();
            // });
            
            // // Table for inspection responses
            Schema::create('inspeccion_item_respuestas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inspeccion_id'); // Will be a foreign key
                $table->unsignedBigInteger('checklist_item_id'); // Will be a foreign key
                $table->boolean('respuesta');
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('inspeccion_item_respuestas');
            Schema::dropIfExists('inspecciones');
            Schema::dropIfExists('checklist_items');
            Schema::dropIfExists('movimientos_combustible');
            Schema::dropIfExists('ordenes');
            Schema::dropIfExists('personal');
            Schema::dropIfExists('vehiculos');
            Schema::dropIfExists('clientes');
            Schema::dropIfExists('proveedores');
            Schema::dropIfExists('depositos');
        }        /**
        * Revierte las migraciones.
        */  
    };