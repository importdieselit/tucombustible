<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// IMPORTACIÓN DE CONTROLADORES
use App\Http\Controllers\Admin\ClienteController as AdminClienteController;
use App\Http\Controllers\ClienteController as PortalClienteController;
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
 * RECUPERACIÓN DE CONTRASEÑA (Flujo Directo reutilizando Cambio Obligatorio)
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
     * Esta zona es accesible incluso si check.password está activo, 
     * ya que es el destino del middleware.
     */
    Route::get('/password/change', [UserController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/update', [UserController::class, 'updatePassword'])->name('password.update');

    /**
     * ACCESO CONTROLADO: Requiere haber cambiado la clave (must_change_password = 0)
     */
    Route::middleware(['check.password'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /**
         * MÓDULOS DE ADMINISTRACIÓN (Perfiles 1 y 2: Admin y Super)
         */
        Route::middleware(['role:1,2'])->group(function () {
            
            Route::prefix('admin-clientes')->name('clientes.')->group(function () {
                Route::get('/panel-control', [AdminClienteController::class, 'index'])->name('index'); 
                Route::get('/{id}/expediente', [AdminClienteController::class, 'show'])->name('show');
                Route::post('/{id}/avanzar', [AdminClienteController::class, 'updatePaso'])->name('avanzar'); 
                Route::post('/{id}/toggle-status', [AdminClienteController::class, 'toggleStatus'])->name('toggleStatus');
                Route::post('/asignar-activos', [ClienteActivosController::class, 'asignarActivos'])->name('activos.asignar');
            });

            // CRUDs MAESTROS Y RECURSOS
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

            // Reportes y Herramientas Admin
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
            
            // RUTA PARA DESCARGA DE PLANILLAS EN ZIP
            Route::get('/descargar-formatos', [PortalClienteController::class, 'descargarFormatos'])->name('descargar.formatos');
        });
    });
});

/* |--------------------------------------------------------------------------
| RUTAS PROVISIONALES - DESARROLLO LOCAL
|-------------------------------------------------------------------------- */
Route::middleware(['auth', 'check.password', 'role:1,2'])->group(function () {
    Route::get('/admin/principal-provisional', function() {
        return view('admin.dashboard_principal_provisional'); 
    })->name('admin.dashboard_principal_provisional');
});