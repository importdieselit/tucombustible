<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// IMPORTACIÓN DE CONTROLADORES
use App\Http\Controllers\Admin\ClienteController as AdminClienteController;
use App\Http\Controllers\Admin\PedidoAdminController; 
use App\Http\Controllers\ClienteController as PortalClienteController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\{
    DashboardController, VehiculoController, MarcaController, ModeloController,
    OrdenController, TanqueController, MovimientoCombustibleController,
    InventarioController, ProveedorController, PerfilController, UserController,
    DepositoController, AlmacenController, ChoferController,
    AlertaController, AccesoController, InspeccionController, PedidoController,
    ReporteController, AforoController, SearchController, DataDeletionController,
    ViajesController, TelegramController, PlanificacionMantenimientoController,
    ReportController, ClienteActivosController
};

/* --- Rutas Públicas y Auth --- */
Auth::routes();
Route::get('/', function () { return redirect()->route('login'); });

/**
 * RECUPERACIÓN DE CONTRASEÑA
 */
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/reset/validar', [ForgotPasswordController::class, 'checkEmail'])->name('password.email.check');

/**
 * PASO 1: REGISTRO PÚBLICO
 */
Route::get('/registro-cliente', [PortalClienteController::class, 'showRegistrationForm'])->name('cliente.register');
Route::post('/registro-cliente', [PortalClienteController::class, 'store'])->name('cliente.register.store');
Route::get('/obtener-ciudades/{estado_id}', [PortalClienteController::class, 'getCiudades'])->name('ciudades.get');

/* --- Rutas Protegidas --- */
Route::middleware(['auth'])->group(function () {

    /**
     * PASO 2: SEGURIDAD INICIAL (Cambio de Contraseña Obligatorio)
     */
    Route::get('/password/change', [UserController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/update', [UserController::class, 'updatePassword'])->name('password.update');

    /**
     * ACCESO CONTROLADO: Requiere haber cambiado la clave
     */
    Route::middleware(['check.password'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /**
         * GESTIÓN DE ACTIVOS (PLACAS Y CHOFERES)
         */
        Route::post('/activos/asignar', [ClienteActivosController::class, 'asignarActivos'])->name('cliente.activos.asignar');

        /**
         * MÓDULOS DE ADMINISTRACIÓN (Perfiles 1: Superusuario y 2: Administrador)
         */
        Route::middleware(['role:1,2'])->group(function () {
            
            Route::prefix('admin-clientes')->name('clientes.')->group(function () {
                Route::get('/panel-control', [AdminClienteController::class, 'index'])->name('index'); 
                Route::get('/{id}/expediente', [AdminClienteController::class, 'show'])->name('show');
                Route::post('/{id}/avanzar', [AdminClienteController::class, 'updatePaso'])->name('avanzar'); 
                Route::post('/{id}/toggle-status', [AdminClienteController::class, 'toggleStatus'])->name('toggleStatus');
            });

            Route::prefix('admin-pedidos')->name('admin.pedidos.')->group(function () {
                Route::get('/', [PedidoAdminController::class, 'index'])->name('index');
                Route::post('/{id}/actualizar-estado', [PedidoAdminController::class, 'updateEstado'])->name('updateEstado');
            });

            // CRUDs MAESTROS
            $resourceControllers = [
                'vehiculos'                   => VehiculoController::class,
                'marcas'                      => MarcaController::class,
                'modelos'                     => ModeloController::class,
                'choferes'                    => ChoferController::class,
                'ordenes'                     => OrdenController::class,
                'tanques'                     => TanqueController::class,
                'depositos'                   => DepositoController::class,
                'almacenes'                   => AlmacenController::class,
                'inventario'                  => InventarioController::class,
                'proveedores'                 => ProveedorController::class,
                'usuarios'                    => UserController::class,
                'inspecciones'                => InspeccionController::class,
                'pedidos'                     => PedidoController::class,
                'movimientos-combustible'     => MovimientoCombustibleController::class,
                'planificacion-mantenimiento' => PlanificacionMantenimientoController::class,
            ];

            foreach ($resourceControllers as $prefix => $controller) {
                $name = str_replace('-', '', $prefix);
                Route::resource($prefix, $controller)->names($name);
            }

            Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
            Route::get('/aforos', [AforoController::class, 'index'])->name('aforos.index');
            Route::get('/search', [SearchController::class, 'query'])->name('search');
        });

        /**
         * ZONA CLIENTES (Solo Perfil 3)
         */
        Route::middleware(['role:3'])->prefix('mi-cuenta')->name('portal.clientes.')->group(function () {
            Route::get('/resumen', [PortalClienteController::class, 'index'])->name('index');
            Route::post('/subir-documento', [PortalClienteController::class, 'uploadDoc'])->name('upload.doc');
            Route::post('/finalizar-carga', [PortalClienteController::class, 'finalizarCargaDocs'])->name('finalizar.paso2');
            Route::get('/mi-perfil', [PortalClienteController::class, 'perfil'])->name('perfil');
            Route::get('/descargar-formatos', [PortalClienteController::class, 'descargarFormatos'])->name('descargar.formatos');
            Route::post('/nueva-solicitud', [PedidoController::class, 'store'])->name('pedidos.store');

            // RUTAS DE GESTIÓN DE SUCURSALES (FASE 3)
            Route::prefix('sucursales')->name('sucursales.')->group(function () {
                Route::post('/{id}/toggle', [SucursalController::class, 'toggleStatus'])->name('toggle');
                Route::get('/{id}/expediente', [SucursalController::class, 'showExpediente'])->name('show');
            });
        });
    });
});

/* Rutas de Desarrollo */
Route::middleware(['auth', 'check.password', 'role:1,2'])->group(function () {
    Route::get('/admin/principal-provisional', function() {
        return view('admin.dashboard_principal_provisional'); 
    })->name('admin.dashboard_principal_provisional');
});