<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// IMPORTACIÓN DE CONTROLADORES
use App\Http\Controllers\Admin\ClienteController as AdminClienteController;
use App\Http\Controllers\Admin\PedidoAdminController;
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
 * RECUPERACIÓN DE CONTRASEÑA
 */
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/reset/validar', [ForgotPasswordController::class, 'checkEmail'])->name('password.email.check');

/**
 * REGISTRO PÚBLICO DE CLIENTES
 */
Route::get('/registro-cliente', [PortalClienteController::class, 'showRegistrationForm'])->name('cliente.register');
Route::post('/registro-cliente', [PortalClienteController::class, 'store'])->name('cliente.register.store');
Route::get('/obtener-ciudades/{estado_id}', [PortalClienteController::class, 'getCiudades'])->name('ciudades.get');

/* --- Rutas Protegidas --- */
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /**
     * MÓDULOS DE ADMINISTRACIÓN (Perfiles 1: Superusuario y 2: Administrador)
     */
    Route::middleware(['role:1,2'])->group(function () {

        // --- MÓDULO CLIENTES COMBUSTIBLE ---
        Route::prefix('admin-clientes')->name('clientes.')->group(function () {
            Route::get('/',                    [AdminClienteController::class, 'index'])->name('index');
            Route::get('/crear',               [AdminClienteController::class, 'create'])->name('create');
            Route::post('/',                   [AdminClienteController::class, 'store'])->name('store');
            Route::get('/{id}/expediente',     [AdminClienteController::class, 'show'])->name('show');
            Route::get('/{id}/editar',         [AdminClienteController::class, 'edit'])->name('edit');
            Route::put('/{id}',                [AdminClienteController::class, 'update'])->name('update');

            // Flujo de registro
            Route::post('/{id}/avanzar-paso',  [AdminClienteController::class, 'avanzarPaso'])->name('avanzarPaso');

            // Gestión de status
            Route::post('/{id}/aprobar',       [AdminClienteController::class, 'aprobar'])->name('aprobar');
            Route::post('/{id}/rechazar',      [AdminClienteController::class, 'rechazar'])->name('rechazar');
            Route::post('/{id}/inactivar',     [AdminClienteController::class, 'inactivar'])->name('inactivar');
            Route::post('/{id}/reactivar',     [AdminClienteController::class, 'reactivar'])->name('reactivar');

            // Cupos
            Route::post('/{id}/ajustar-cupo',  [AdminClienteController::class, 'ajustarCupo'])->name('ajustarCupo');

            // Placas
            Route::post('/{id}/placas',                    [AdminClienteController::class, 'registrarPlaca'])->name('placas.store');
            Route::post('/placas/{placaId}/inactivar',     [AdminClienteController::class, 'inactivarPlaca'])->name('placas.inactivar');

            // Choferes
            Route::post('/{id}/choferes',                  [AdminClienteController::class, 'registrarChofer'])->name('choferes.store');
            Route::post('/choferes/{choferId}/inactivar',  [AdminClienteController::class, 'inactivarChofer'])->name('choferes.inactivar');
        });

        // --- MÓDULO CLIENTES LUBRICANTES ---
        Route::prefix('admin-clientes-lubricantes')->name('clientes.lubricantes.')->group(function () {
            Route::get('/',         [AdminClienteController::class, 'indexLubricantes'])->name('index');
            Route::post('/',        [AdminClienteController::class, 'storeLubricante'])->name('store');
            Route::delete('/{id}',  [AdminClienteController::class, 'destroyLubricante'])->name('destroy');
        });

        // --- CRUDs MAESTROS ---
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
            'movimientos-combustible'     => MovimientoCombustibleController::class,
            'planificacion-mantenimiento' => PlanificacionMantenimientoController::class,
        ];

        foreach ($resourceControllers as $prefix => $controller) {
            $name = str_replace('-', '', $prefix);
            Route::resource($prefix, $controller)->names($name);
        }

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/aforos',   [AforoController::class, 'index'])->name('aforos.index');
        Route::get('/search',   [SearchController::class, 'query'])->name('search');

        // Dashboard provisional de desarrollo
        Route::get('/admin/principal-provisional', function () {
            return view('admin.dashboard_principal_provisional');
        })->name('admin.dashboard_principal_provisional');
    });

    /**
     * ZONA CLIENTES (Solo Perfil 3)
     */
    Route::middleware(['role:3'])->prefix('mi-cuenta')->name('portal.clientes.')->group(function () {
        Route::get('/resumen',   [PortalClienteController::class, 'index'])->name('index');
        Route::get('/mi-perfil', [PortalClienteController::class, 'perfil'])->name('perfil');
    });
});