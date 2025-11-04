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
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AccesoController;
use App\Http\Controllers\InspeccionController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\AforoController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DataDeletionController;
use App\Http\Controllers\ViajesController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\PlanificacionMantenimientoController;

use App\Models\Deposito;
use App\Models\MovimientoCombustible;

// Agrega otros controladores según los modelos y tablas

Auth::routes();

Route::get('/', function () {
    //dd('error  403: Acceso no autorizado. Por favor, inicie sesión.');
    // Si deseas redirigir a una página específica, descomenta la línea siguiente:
    return redirect()->route('login');
});

// routes/web.php

// 1. Ruta GET: Muestra la vista con la política y el formulario.
Route::get('/politica-eliminacion-datos', [DataDeletionController::class, 'showRequestForm'])->name('data.deletion.form');

// 2. Ruta POST: Procesa el envío del formulario.
// Usamos el mismo endpoint que definimos antes para simplificar.
Route::post('/solicitud-eliminacion-datos', [DataDeletionController::class, 'submitRequest'])->name('data.deletion.submit');

Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);

Route::middleware(['auth'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

// Route::get('/checklist-salida', [InspectionController::class, 'showChecklistForm'])->name('checklist.show');
// Route::post('/checklist-salida', [InspectionController::class, 'processChecklist'])->name('checklist.process');


    Route::get('clientes/dashboard', [ClienteController::class, 'dashboard'])->name('clientes.dashboard')->middleware('role:3');
    Route::get('combustible/dashboard', [MovimientoCombustibleController::class, 'index'])->name('combustible.dashboard')->middleware('role:2');
    
    // Rutas para la carga dinámica de modelos
    Route::get('/marcas/get-modelos', [MarcaController::class, 'getModelos'])->name('marcas.getModelos');


    // Formulario de inspección (usando el ID del checklist y del vehículo)
Route::get('/vehiculos/inspeccion/{vehiculo_id}/{tipo}', [InspeccionController::class, 'create'])->name('inspeccion.create');


// Guardar la inspección
Route::post('vehiculos/updatev', [VehiculoController::class, 'updatev'])->name('vehiculos.updatev');
e('inspeccion.store');
Route::post('/inspecciones', [InspeccionController::class, 'store'])->name('inspeccion.store');
Route::get('/inspecciones', [InspeccionController::class, 'list'])->name('inspeccion.list');
Route::get('/inspecciones-dashboard', [InspeccionController::class, 'index'])->name('inspeccion.index');

Route::get('search/global', [SearchController::class, 'globalSearch'])->name('search.global');

// Ruta para ver el detalle de la inspección
Route::get('/inspecciones/{inspeccion_id}', [InspeccionController::class, 'show'])->name('inspeccion.show');

// Ruta para generar el PDF
Route::get('/inspecciones/{inspeccion_id}/pdf', [InspeccionController::class, 'exportPdf'])->name('inspeccion.pdf');


    // 2. Rutas Específicas para las Acciones de Ciclo de Vida (POST)
    
    // Ruta para cambiar el estatus de un ticket (Ej: de Abierto a En Proceso)
    // Se usa PUT o POST (aquí usamos PUT para ser más RESTful)
    Route::put('reportes/{reporte}/estatus', [ReporteController::class, 'updateStatus'])
        ->name('reportes.update.estatus'); 

    Route::get('/depositos/{deposito}/aforo', [AforoController::class, 'showAforoTable'])->name('depositos.aforo.show');

    Route::get('/depositos/{deposito}/aforo/exportar', [AforoController::class, 'exportAforoTable'])->name('depositos.aforo.export');

    // Ruta para generar la Orden de Trabajo a partir de la reporte
    // Se usa POST porque es una acción que crea un nuevo recurso (la OT) o cambia el estado
    Route::post('reportes/{reporte}/generarot', [ReporteController::class, 'generarOT'])
        ->name('reportes.generarot');   

Route::get('inventario/entry', [inventarioController::class, 'entry'])->name('inventario.entry');
    Route::get('inventario/adjustment', [InventarioController::class, 'adjustment'])->name('inventario.adjustment');
    Route::get('choferes/importar', [ChoferController::class, 'showImportForm'])->name('choferes.show-import-form');
    Route::post('choferes/importar', [ChoferController::class, 'importar'])->name('choferes.importar');
    // Rutas para las solicitudes de insumos
    Route::get('/inventario/solicitudes', [InventarioController::class, 'requests'])->name('inventario.requests');
    Route::post('/inventario/solicitudes/{id}/approve', [InventarioController::class, 'approve'])->name('inventario.requests.approve');
    Route::post('/inventario/solicitudes/{id}/reject', [InventarioController::class, 'reject'])->name('inventario.requests.reject');
    Route::post('/inventario/solicitudes/{id}/dispatch', [InventarioController::class, 'dispatch'])->name('inventario.requests.dispatch');
 
    Route::get('/vehiculos/import', [VehiculoController::class, 'importForm'])->name('vehiculos.import');
    Route::post('/vehiculos/import', [VehiculoController::class, 'importSave'])->name('vehiculos.import.save');

    Route::post('/inventario/import/excel', [InventarioController::class, 'import'])->name('inventario.import');
    Route::get('/inventario/export/excel', [InventarioController::class, 'export'])->name('inventario.export');

    Route::post('ordenes/supplies', [OrdenController::class, 'storeSupply'])->name('ordenes.supplies.store');
    Route::get('/ot/create/{vehiculo_id}', [OrdenController::class, 'create'])->name('ot.create');

    Route::put('ordenes/supplies/{id}', [OrdenController::class, 'updateSupply'])->name('ordenes.supplies.update');
    Route::delete('ordenes/supplies/{id}', [OrdenController::class, 'deleteSupply'])->name('ordenes.supplies.delete');

    Route::post('/ordenes/{orden}/cerrar', [OrdenController::class, 'cerrarOrden'])->name('ordenes.cerrar');
    Route::post('/ordenes/{orden}/anular', [OrdenController::class, 'anularOrden'])->name('ordenes.anular');
    Route::post('/ordenes/{orden}/reactivar', [OrdenController::class, 'reactivarOrden'])->name('ordenes.reactivar');

    Route::get('clientes/import', [ClienteController::class, 'import'])->name('clientes.import');
    Route::post('clientes/handle', [ClienteController::class, 'handleImport'])->name('clientes.handleImport');

    Route::get('/usuarios/importar', [UserController::class, 'import'])->name('usuarios.importar');
    Route::post('/usuarios/importarP', [UserController::class, 'handleImport'])->name('usuarios.importarprocess');
    Route::put('/depositos/ajustedinamic', [DepositoController::class, 'ajusteDinamic'])->name('deposito.ajusteD');
                
   
  Route::get('/permisos', [AccesoController::class, 'index'])->name('permisos.index');


    // Rutas para la gestión de permisos específicos (Usuario Individual)
    Route::get('usuarios/{usuario}/permissions', [UserController::class, 'editPermissions'])->name('usuarios.edit_permissions');
    Route::put('usuarios/{usuario}/permissions', [UserController::class, 'updatePermissions'])->name('usuarios.update_permissions');

    // Rutas para la gestión de perfiles (CRUD + Permisos Base)
    // Usamos 'except' para indicar que la edición de permisos reemplaza al 'edit' genérico.
    Route::resource('perfiles', PerfilController::class)->except(['edit', 'update']); 
    
    // Ruta para la edición de permisos base de un perfil
    Route::get('perfiles/{perfil}/permissions', [PerfilController::class, 'editPermissions'])->name('perfiles.edit_permissions');
    Route::put('perfiles/{perfil}/permissions', [PerfilController::class, 'updatePermissions'])->name('perfiles.update_permissions');

    
    // Las rutas de la API para obtener y actualizar permisos
    Route::get('/api/permisos/{user}/get', [AccesoController::class, 'getPermissionsForUser'])->name('permisos.get');
    Route::post('/api/permisos/{user}/update', [AccesoController::class, 'updatePermissions'])->name('permisos.update');

    Route::get('ordenes/search-supplies', [OrdenController::class, 'searchSupplies'])->name('ordenes.search-supplies');
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
        'reportes' => ReporteController::class
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
    
    
    //Route::get('/ordenes/report/pdf', [OrdenController::class, 'reportPdf'])->name('ordenes.report.pdf');
    Route::get('/vehiculos/report/pdf', [VehiculoController::class, 'reportPdf'])->name('vehiculos.report.pdf');

    // Rutas para gestión de perfiles y permisos
    Route::post('/perfiles/{perfil}/permisos', [PerfilController::class, 'updatePermisos'])->name('perfiles.updatePermisos'); 
    Route::post('/pedidos', [PedidoController::class, 'crearPedido'])->name('pedidos.store');
    // Rutas de Combustible (Pedidos y Despachos)
    Route::prefix('combustible')->name('combustible.')->group(function () {
        // Rutas para los movimientos de combustible
        Route::get('/recarga', [MovimientoCombustibleController::class, 'createRecarga'])->name('recarga');
        Route::post('/recargaStore', [MovimientoCombustibleController::class, 'storeRecarga'])->name('storeRecarga');

        Route::get('/index', [MovimientoCombustibleController::class, 'index'])->name('index');
        Route::get('/list', [MovimientoCombustibleController::class, 'list'])->name('list');
        Route::get('/despacholist', [MovimientoCombustibleController::class, 'despachoList'])->name('despachos.list');
        
        Route::get('/pedidos', [MovimientoCombustibleController::class, 'pedidos'])->name('pedidos');
        
        Route::post('/pedidos/{id}/aprobar', [MovimientoCombustibleController::class, 'aprobar'])->name('aprobar');
        Route::post('/pedidos/{id}/rechazar', [MovimientoCombustibleController::class, 'rechazar'])->name('rechazar');
        
        Route::get('/compra/crear', [MovimientoCombustibleController::class, 'createCompra'])->name('createCompra');
        Route::post('/solicitud', [MovimientoCombustibleController::class, 'storeCompra'])->name('storeCompra');
        Route::get('/compras',[MovimientoCombustibleController::class, 'comprasList'])->name('compras');

        // Ruta para aprobar un pedido
        Route::post('/pedido/{id}/aprobar', [PedidoController::class, 'aprobar'])->name('pedido.aprobar');

        // Ruta para crear un despacho
        Route::post('/pedido/{id}/despachar', [PedidoController::class, 'despachar'])->name('pedido.despachar');

        Route::get('/aprobados', [MovimientoCombustibleController::class, 'despachos'])->name('aprobados');
        Route::post('/despachos/{id}/despachar', [MovimientoCombustibleController::class, 'despachar'])->name('despachar');
        // Nuevas rutas para el despacho de combustible
        Route::get('/despacho', [MovimientoCombustibleController::class, 'createDespacho'])->name('despacho');
        Route::post('/despacho', [MovimientoCombustibleController::class, 'storeDespacho'])->name('storeDespacho');

        // Nuevas rutas para el despacho de combustible
        Route::get('/precarga', [MovimientoCombustibleController::class, 'createPrecarga'])->name('precarga');
        Route::post('/precarga', [MovimientoCombustibleController::class, 'storePrecarga'])->name('storePrecarga');
        Route::post('/aprobado', [MovimientoCombustibleController::class, 'storeAprobado'])->name('storeAprobado');

    });

    Route::get('/viajes/calendario', [ViajesController::class, 'calendar'])->name('viajes.calendario');
  
    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/alertas/read/{id}', [AlertaController::class, 'markAsRead'])->name('alertas.read');

    
    Route::resource('viajes', ViajesController::class)->only(['create','store', 'index', 'show']);
    Route::get('viajes/dashboard', [ViajesController::class, 'dashboard'])->name('viajes.dashboard');
    Route::get('viaje/list', [ViajesController::class, 'list'])->name('viajes.list');
    Route::get('/viajes/{id}/assign', [ViajesController::class, 'assign'])->name('viajes.assign');
Route::put('/viajes/{id}/assign', [ViajesController::class, 'processAssignment'])->name('viajes.processAssignment');    
    Route::get('viajes/{viaje}/viaticos/edit', [ViajesController::class, 'editViaticos'])->name('viajes.viaticos.edit');
    Route::put('viajes/{viaje}/viaticos', [ViajesController::class, 'updateViaticos'])->name('viajes.viaticos.update');
    Route::delete('/viajes/{id}', [ViajesController::class, 'destroy'])->name('viajes.destroy');
    // Muestra el formulario de edición
Route::get('/viajes/{id}/edit', [ViajesController::class, 'edit'])->name('viaje.edit');

// Ruta AJAX para actualizar un campo del Viaje
Route::put('/viajes/{id}/update-field', [ViajesController::class, 'updateField'])->name('viaje.update.field');

// Ruta AJAX para actualizar un campo de Despacho
// Usamos el ID del viaje en la ruta para mantener el contexto
Route::put('/viajes/{viajeId}/despachos/{despachoId}', [ViajesController::class, 'updateDespacho'])->name('despacho.update.field');

// Procesa la actualización del formulario (PUT/PATCH)
Route::put('/viajes/{id}', [ViajesController::class, 'update'])->name('viaje.update');
    // Nueva ruta para el resumen de programación
    Route::get('/viajes/resumen-programacion/{id?}', [ViajesController::class, 'resumenProgramacion'])->name('viajes.resumenProgramacion');
    Route::get('viajes/report/index', [ViajesController::class, 'reportsIndex'])->name('reportes.viajes');
    Route::put('viajes/report/generate', [ViajesController::class, 'generateReport'])->name('viajes.report.generate');
    Route::get('viaticos/tabulador', [ViajesController::class, 'tabuladorIndex'])->name('viaticos.tabulador');
    Route::put('viaticos/tabulador/update', [ViajesController::class, 'tabuladorUpdate'])->name('viaticos.tabulador.update');
    Route::put('viaticos/parametros/update', [ViajesController::class, 'parametrosUpdate'])->name('viaticos.parametros.update');

    Route::post('/send-telegram-photo', [TelegramController::class, 'sendPhoto'])
    ->name('telegram.send.photo');
    Route::post('/send-telegram-message', [TelegramController::class, 'sendMessage'])->name('telegram.send.message');

    Route::get('eventos', [ViajesController::class, 'getCombinedEventos'])->name('eventos');

     Route::get('/mantenimiento/planificacion', [PlanificacionMantenimientoController::class, 'index'])
         ->name('mantenimiento.planificacion.index');
    
    Route::get('/api/mantenimiento/eventos', [PlanificacionMantenimientoController::class, 'getEventos'])
         ->name('mantenimiento.planificacion.eventos');

    Route::post('/api/mantenimiento/planificar', [PlanificacionMantenimientoController::class, 'store'])
         ->name('mantenimiento.planificacion.store');

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


