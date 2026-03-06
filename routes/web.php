<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// IMPORTACIÓN DE CONTROLADORES
use App\Http\Controllers\Admin\ClienteController as AdminClienteController;
use App\Http\Controllers\Cliente\ClienteController as PortalClienteController;
use App\Http\Controllers\{
    DashboardController, VehiculoController, MarcaController, ModeloController,
    OrdenController, TanqueController, MovimientoCombustibleController,
    InventarioController, ProveedorController, PerfilController, UserController,
    DepositoController, AlmacenController, ChoferController,
    AlertaController, AccesoController, InspeccionController, PedidoController,
    ReporteController, AforoController, SearchController, DataDeletionController,
    ViajesController, TelegramController, PlanificacionMantenimientoController,
    CaptacionController, ReportController, ClienteActivosController
};

/* --- Rutas Públicas y Auth --- */
Auth::routes();
Route::get('/', function () { return redirect()->route('login'); });

/**
 * PASO 1: REGISTRO PÚBLICO
 * Estas rutas deben estar fuera de 'auth' para que el botón "Registrar" del login funcione.
 */
Route::get('/registro-cliente', [CaptacionController::class, 'showRegistrationForm'])->name('cliente.register');
Route::post('/registro-cliente', [CaptacionController::class, 'store'])->name('cliente.register.store');


/* --- Rutas Protegidas --- */
Route::middleware(['auth'])->group(function () {

    /**
     * PASO 2: SEGURIDAD INICIAL (Cambio de Contraseña)
     * Estas rutas NO llevan el middleware 'check.password' para permitir el acceso al formulario.
     */
    Route::get('/password/change', [UserController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/update', [UserController::class, 'updatePassword'])->name('password.update');


    /**
     * ACCESO CONTROLADO: Requiere haber cambiado la clave (must_change_password = 0)
     */
    Route::middleware(['check.password'])->group(function () {

        // Dashboard Unificado (Decide si va al Paso 3-9 o al Dashboard Final)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /**
         * MÓDULOS DE ADMINISTRACIÓN (Perfiles 1 y 2: Admin y Super)
         */
        Route::middleware(['role:1,2'])->group(function () {
            
            // --- SECCIÓN: GESTIÓN DE CLIENTES (ADMIN) ---
            Route::prefix('admin-clientes')->name('clientes.')->group(function () {
                // Usamos el AdminClienteController que es el que tiene la lógica de Dashboard Admin
                Route::get('/panel-control', [AdminClienteController::class, 'index'])->name('index'); 
                Route::get('/{id}/expediente', [AdminClienteController::class, 'show'])->name('show');
        
                // OJO: En tu controlador el método se llama updatePaso, no updateStep.
                Route::patch('/{id}/avanzar', [AdminClienteController::class, 'updatePaso'])->name('avanzar'); 
                Route::patch('/{id}/toggle-status', [AdminClienteController::class, 'toggleStatus'])->name('toggleStatus');
        
                Route::post('/asignar-activos', [ClienteActivosController::class, 'asignarActivos'])->name('activos.asignar');
            });

            // --- CRUDs MAESTROS ---
            $resourceControllers = [
                'vehiculos'  => VehiculoController::class,
                'marcas'     => MarcaController::class,
                'modelos'    => ModeloController::class,
                'choferes'   => ChoferController::class,
                'ordenes'    => OrdenController::class,
                'tanques'    => TanqueController::class,
                'depositos'  => DepositoController::class,
                'almacenes'  => AlmacenController::class,
                'inventario' => InventarioController::class,
                'proveedores'=> ProveedorController::class,
                'usuarios'   => UserController::class,
            ];

            foreach ($resourceControllers as $prefix => $controller) {
                $name = str_replace('-', '', $prefix);
                Route::resource($prefix, $controller)->names($name);
            }
        });

        /**
         * ZONA CLIENTES (Solo Perfil 3 - Portal del Usuario Externo)
         */
        Route::middleware(['role:3'])->prefix('mi-cuenta')->name('portal.clientes.')->group(function () {
            
            // Dashboard dinámico del cliente (Paso 2 al 10)
            Route::get('/resumen', [PortalClienteController::class, 'index'])->name('index');
            
            // Rutas de Carga de Documentos (Paso 2)
            Route::post('/subir-documento', [PortalClienteController::class, 'uploadDoc'])->name('upload.doc');
            Route::post('/finalizar-carga', [CaptacionController::class, 'finalizarCargaDocs'])->name('finalizar.paso2');
            
            // Perfil y Expediente propio
            Route::get('/mi-perfil', [PortalClienteController::class, 'perfil'])->name('perfil');
        });
    });
});