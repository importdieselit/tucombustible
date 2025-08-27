<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\TipoVehiculoController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\TanqueController;
use App\Http\Controllers\RepostajeVehiculoController;
use App\Http\Controllers\RepostajeTanqueController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlanMantenimientoController;
use App\Http\Controllers\HistorialMantenimientoController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MovimientoCombustibleController;
use App\Http\Controllers\DepositoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\ChoferController;

// Agrega otros controladores según los modelos y tablas

Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');



    
    // Rutas para la carga dinámica de modelos
    Route::get('/marcas/get-modelos', [MarcaController::class, 'getModelos'])->name('marcas.getModelos');


    Route::get('inventario/entry', [inventarioController::class, 'entry'])->name('inventario.entry');
    Route::get('invantario/adjustment', [InventarioController::class, 'adjustment'])->name('inventario.adjustment');
    Route::get('choferes/importar', [ChoferController::class, 'showImportForm'])->name('choferes.show-import-form');
    Route::post('choferes/importar', [ChoferController::class, 'importar'])->name('choferes.importar');

    // Recursos principales
    $resourceControllers = [
        'vehiculos' => VehiculoController::class,
        'marcas' => MarcaController::class,
        'modelos' => ModeloController::class,
        'choferes' => ChoferController::class,
        'ordenes' => OrdenController::class,
        'tanques' => TanqueController::class,
        'depositos' => DepositoController::class,
        'clientes' => ClienteController::class,
        'almacenes' => AlmacenController::class,
        //'repostaje-tanques' => RepostController::class,
        'inventario' => InventarioController::class,
        'proveedores' => ProveedorController::class,
        //'servicios' => ServicioController::class,
        'perfiles' => PerfilController::class,
        'usuarios' => UserController::class,
        //'plan-mantenimiento' => PlanMantenimientoController::class,
        //'historial-mantenimiento' => HistorialMantenimientoController::class,
        // Agrega aquí otros recursos según tus tablas/modelos
    ];

    // Recorre el array para generar las rutas de recursos y la ruta 'list'
    foreach ($resourceControllers as $prefix => $controller) {
         // Agrega la ruta 'list' para cada recurso
        Route::get(str_replace('-', '', $prefix) . "/list", [$controller, 'list'])->name(str_replace('-', '', $prefix) . '.list');
        // Genera las rutas resource con el prefijo en singular
        
        Route::resource($prefix, $controller)->names([
            'index' => str_replace('-', '', $prefix) . '.index',
            'create' => str_replace('-', '', $prefix) . '.create',
            'store' => str_replace('-', '', $prefix) . '.store',
            'show' => str_replace('-', '', $prefix) . '.show',
            'edit' => str_replace('-', '', $prefix) . '.edit',
            'update' => str_replace('-', '', $prefix) . '.update',
            'destroy' => str_replace('-', '', $prefix) . '.destroy',
        ]);
        
       
    }
    // Ejemplo de rutas adicionales para importación/exportación, reportes, etc.
    Route::post('/inventario/import/excel', [InventarioController::class, 'import'])->name('inventario.import');
    Route::get('/inventario/export/excel', [InventarioController::class, 'export'])->name('inventario.export');

    //Route::get('/ordenes/report/pdf', [OrdenController::class, 'reportPdf'])->name('ordenes.report.pdf');
    Route::get('/vehiculos/report/pdf', [VehiculoController::class, 'reportPdf'])->name('vehiculos.report.pdf');

    // Rutas para gestión de perfiles y permisos
    Route::post('/perfiles/{perfil}/permisos', [PerfilController::class, 'updatePermisos'])->name('perfiles.updatePermisos'); 
    
    // Rutas para los movimientos de combustible
    Route::get('/combustible/recarga', [MovimientoCombustibleController::class, 'createRecarga'])->name('combustible.recarga');
    Route::post('/combustible/recarga', [MovimientoCombustibleController::class, 'storeRecarga'])->name('combustible.storeRecarga');

    Route::get('/combustible/index', [MovimientoCombustibleController::class, 'index'])->name('combustible.index');
    Route::get('/combustible/list', [MovimientoCombustibleController::class, 'list'])->name('combustible.list');
    Route::get('/combustible/despacholist', [MovimientoCombustibleController::class, 'despachoList'])->name('despachos.list');
    
    
    // Nuevas rutas para el despacho de combustible
    Route::get('/combustible/despacho', [MovimientoCombustibleController::class, 'createDespacho'])->name('combustible.despacho');
    Route::post('/combustible/despacho', [MovimientoCombustibleController::class, 'storeDespacho'])->name('combustible.storeDespacho');


    
    // Rutas para historial de mantenimiento
    //Route::get('/vehiculos/{vehiculo}/historial', [HistorialMantenimientoController::class, 'showByVehiculo'])->name('vehiculos.historial');

    // Rutas para repostaje específico
    //Route::get('/tanques/{tanque}/repostajes', [RepostajeTanqueController::class, 'showByTanque'])->name('tanques.repostajes');
    //Route::get('/vehiculos/{vehiculo}/repostajes', [RepostajeVehiculoController::class, 'showByVehiculo'])->name('vehiculos.repostajes');

    Route::get('/routes-list', function () {
        dd(Route::getRoutes());
    });
});

//require __DIR__.'/auth.php';


