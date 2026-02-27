<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    DashboardController, VehiculoController, MarcaController, ModeloController,
    OrdenController, TanqueController, MovimientoCombustibleController,
    InventarioController, ProveedorController, PerfilController, UserController,
    DepositoController, ClienteController, AlmacenController, ChoferController,
    AlertaController, AccesoController, InspeccionController, PedidoController,
    ReporteController, AforoController, SearchController, DataDeletionController,
    ViajesController, TelegramController, PlanificacionMantenimientoController,
    CaptacionController, ReportController
};

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/
Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

// Otros Públicos (Mantenidos)
Route::get('/politica-eliminacion-datos', [DataDeletionController::class, 'showRequestForm'])->name('data.deletion.form');
Route::post('/solicitud-eliminacion-datos', [DataDeletionController::class, 'submitRequest'])->name('data.deletion.submit');
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /**
     * ZONA CERO: Dashboard Unificado
     * Esta es la ruta a la que llegará el usuario tras el login.
     */
    Route::get('/dashboard', function () {
        // Si no existe el controlador DashboardController@index, podemos usar una vista simple por ahora
        return view('dashboard'); 
    })->name('dashboard');

    Route::get('/home', function () { return redirect()->route('dashboard'); });

    /**
     * Perfil y Contraseña
     */
    Route::get('/usuarios/perfil', [UserController::class, 'show'])->name('perfil.show');
    Route::get('/password/change', [UserController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [UserController::class, 'updatePassword'])->name('password.update_change');

    /**
     * Módulos de Administración (Solo para Perfiles 1 y 2)
     * Aquí agrupamos todo lo que NO es del cliente.
     */
    Route::middleware(['CheckUserRole:1,2'])->group(function () {
        
        // Gestión de Clientes (Vista para Admin)
        Route::patch('clientes/{id}/toggle-status', [ClienteController::class, 'toggleStatus'])->name('clientes.toggleStatus');
        
        // Combustible, Viajes, etc. (Mantengo tus lógicas originales pero sin el middleware de pasos)
        Route::prefix('combustible')->name('combustible.')->group(function () {
            Route::get('/index', [MovimientoCombustibleController::class, 'index'])->name('index');
            Route::get('/recarga', [MovimientoCombustibleController::class, 'createRecarga'])->name('recarga');
            Route::post('/recargaStore', [MovimientoCombustibleController::class, 'storeRecarga'])->name('storeRecarga');
            // ... (Resto de rutas de combustible iguales)
        });

        // Recursos Genéricos
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
            'inventario' => InventarioController::class,
            'proveedores' => ProveedorController::class,
            'usuarios' => UserController::class,
            'reportes' => ReporteController::class
        ];

        foreach ($resourceControllers as $prefix => $controller) {
            $name = str_replace('-', '', $prefix);
            Route::get("$prefix/list", [$controller, 'list'])->name("$name.list");
            Route::resource($prefix, $controller)->names($name);
        }
    });

    /**
     * Módulos Compartidos o API Interna
     */
    Route::get('/marcas/get-modelos', [MarcaController::class, 'getModelos'])->name('marcas.getModelos');
    Route::get('search/global', [SearchController::class, 'globalSearch'])->name('search.global');

});

/*
|--------------------------------------------------------------------------
| Notas de Limpieza:
| Se eliminó el Middleware 'access.step' y 'StepAccess' para evitar bucles.
| Se eliminaron las rutas de 'captacion' que dependían de la tabla eliminada.
|--------------------------------------------------------------------------
*/