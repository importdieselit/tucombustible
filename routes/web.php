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
use App\Http\Controllers\CaptacionController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

// Captación - Registro Público (Sin Auth)
Route::get('captacion/crear', [CaptacionController::class,'create'])->name('captacion.create');
Route::post('captacion/store', [CaptacionController::class,'store'])->name('captacion.store');
Route::get('captacion/thanks', [CaptacionController::class,'thanks'])->name('captacion.thanks');

// Otros Públicos
Route::get('/politica-eliminacion-datos', [DataDeletionController::class, 'showRequestForm'])->name('data.deletion.form');
Route::post('/solicitud-eliminacion-datos', [DataDeletionController::class, 'submitRequest'])->name('data.deletion.submit');
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);
Route::post('/telegram/webhooklogistica', [TelegramController::class, 'handleLogisticaWebhook']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Auth)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /**
     * CORRECCIÓN PERFIL: Ruta dedicada para el perfil del usuario actual.
     * Se coloca antes que cualquier otra ruta de usuarios para evitar conflictos.
     */
    Route::get('/usuarios/perfil', [UserController::class, 'show'])->name('perfil.show');

    // --- Gestión de Contraseña Obligatoria ---
    Route::get('/password/change', [UserController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [UserController::class, 'updatePassword'])->name('password.update_change');

    // Captación - Módulo Admin
    Route::prefix('admin/captacion')->group(function () {
        Route::get('/', [CaptacionController::class, 'index'])->name('captacion.index');
        Route::get('/{cliente}/show', [CaptacionController::class, 'show'])->name('captacion.show');
        Route::get('/{cliente}/edit', [CaptacionController::class, 'edit'])->name('captacion.edit');
        Route::put('/{cliente}/update', [CaptacionController::class, 'update'])->name('captacion.update');
        Route::post('/{cliente}/enviar-planillas', [CaptacionController::class, 'enviarPlanillas'])->name('captacion.enviar_planillas');
        Route::post('/validar-documento/{documento}', [CaptacionController::class, 'validarDocumento'])->name('captacion.validar_documentos');
        Route::post('/{id}/rechazar', [CaptacionController::class, 'rechazar'])->name('captacion.rechazar');
        Route::post('/{cliente}/aprobar', [CaptacionController::class, 'aprobar'])->name('captacion.aprobar');
        Route::post('/subir-documento/{id}', [CaptacionController::class, 'uploadDocument'])->name('captacion.subir_documento');
        Route::post('/finalizar-carga/{id}', [CaptacionController::class, 'finalizarCarga'])->name('captacion.finalizar_carga');
    });

    // Dashboards
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('clientes/dashboard', [ClienteController::class, 'dashboard'])->name('clientes.dashboard');
    Route::get('combustible/dashboard', [MovimientoCombustibleController::class, 'index'])->name('combustible.dashboard');

    // --- Middleware de Control de Acceso Paso a Paso ---
    Route::middleware(['access.step'])->group(function () {

        // Rutas para Prospectos (Carga de documentos)
        Route::get('/completar-expediente', [CaptacionController::class, 'completarExpediente'])->name('captacion.completar');
        
        // Inspecciones y Vehículos
        Route::get('/marcas/get-modelos', [MarcaController::class, 'getModelos'])->name('marcas.getModelos');
        Route::get('/vehiculos/inspeccion/{vehiculo_id}/{tipo}', [InspeccionController::class, 'create'])->name('inspeccion.create');
        Route::put('vehiculos/updatev/{id}', [VehiculoController::class, 'updatev'])->name('vehiculos.updatev');
        Route::post('/inspecciones', [InspeccionController::class, 'store'])->name('inspeccion.store');
        Route::get('/inspecciones', [InspeccionController::class, 'list'])->name('inspeccion.list');
        Route::get('/inspecciones-dashboard', [InspeccionController::class, 'index'])->name('inspeccion.index');
        Route::get('/inspecciones/{inspeccion_id}', [InspeccionController::class, 'show'])->name('inspeccion.show');
        Route::get('/inspecciones/{inspeccion_id}/pdf', [InspeccionController::class, 'exportPdf'])->name('inspeccion.pdf');
        
        // Búsqueda
        Route::get('search/global', [SearchController::class, 'globalSearch'])->name('search.global');

        // Reportes y OT
        Route::put('reportes/{reporte}/estatus', [ReporteController::class, 'updateStatus'])->name('reportes.update.estatus'); 
        Route::post('reportes/{reporte}/generarot', [ReporteController::class, 'generarOT'])->name('reportes.generarot');   
        
        // Depósitos y Aforo
        Route::get('/depositos/{deposito}/aforo', [AforoController::class, 'showAforoTable'])->name('depositos.aforo.show');
        Route::get('/depositos/{deposito}/aforo/exportar', [AforoController::class, 'exportAforoTable'])->name('depositos.aforo.export');
        Route::put('/depositos/ajustedinamic', [DepositoController::class, 'ajusteDinamic'])->name('deposito.ajusteD');
        Route::put('/depositos/ajusteresguardo', [DepositoController::class, 'ajusteResguardo'])->name('deposito.ajusteR');

        // Inventario y Almacén
        Route::get('inventario/entry', [InventarioController::class, 'entry'])->name('inventario.entry');
        Route::get('inventario/adjustment', [InventarioController::class, 'adjustment'])->name('inventario.adjustment');
        Route::get('/inventario/solicitudes', [InventarioController::class, 'requests'])->name('inventario.requests');
        Route::post('/inventario/solicitudes/{id}/approve', [InventarioController::class, 'approve'])->name('inventario.requests.approve');
        Route::post('/inventario/solicitudes/{id}/reject', [InventarioController::class, 'reject'])->name('inventario.requests.reject');
        Route::post('/inventario/solicitudes/{id}/dispatch', [InventarioController::class, 'dispatch'])->name('inventario.requests.dispatch');
        Route::post('/inventario/import/excel', [InventarioController::class, 'import'])->name('inventario.import');
        Route::get('/inventario/export/excel', [InventarioController::class, 'export'])->name('inventario.export');

        // Importaciones
        Route::get('choferes/importar', [ChoferController::class, 'showImportForm'])->name('choferes.show-import-form');
        Route::post('choferes/importar', [ChoferController::class, 'importar'])->name('choferes.importar');
        Route::get('/vehiculos/import', [VehiculoController::class, 'importForm'])->name('vehiculos.import');
        Route::post('/vehiculos/import', [VehiculoController::class, 'importSave'])->name('vehiculos.import.save');
        Route::get('clientes/import', [ClienteController::class, 'import'])->name('clientes.import');
        Route::post('clientes/handle', [ClienteController::class, 'handleImport'])->name('clientes.handleImport');
        Route::get('/usuarios/importar', [UserController::class, 'import'])->name('usuarios.importar');
        Route::post('/usuarios/importarP', [UserController::class, 'handleImport'])->name('usuarios.importarprocess');

        // Órdenes (OT)
        Route::post('ordenes/supplies', [OrdenController::class, 'storeSupply'])->name('ordenes.supplies.store');
        Route::get('/ot/create/{vehiculo_id}', [OrdenController::class, 'create'])->name('ot.create');
        Route::put('ordenes/supplies/{id}', [OrdenController::class, 'updateSupply'])->name('ordenes.supplies.update');
        Route::delete('ordenes/supplies/{id}', [OrdenController::class, 'deleteSupply'])->name('ordenes.supplies.delete');
        Route::post('/ordenes/{orden}/cerrar', [OrdenController::class, 'cerrarOrden'])->name('ordenes.cerrar');
        Route::post('/ordenes/{orden}/anular', [OrdenController::class, 'anularOrden'])->name('ordenes.anular');
        Route::post('/ordenes/{orden}/reactivar', [OrdenController::class, 'reactivarOrden'])->name('ordenes.reactivar');
        Route::get('ordenes/search-supplies', [OrdenController::class, 'searchSupplies'])->name('ordenes.search-supplies');
        Route::post('/ordenes/supplies/receive/{supply}', [OrdenController::class, 'markAsReceived'])->name('ordenes.supplies.receive');
        Route::get('ordenes/compras/{id_order?}/{id?}', [OrdenController::class, 'purchaseOrder'])->name('ordenes.compra');
        Route::post('/compras/actualizar-precio', [OrdenController::class,'actualizarPrecio'])->name('compras.actualizar_precio');
        Route::post('/compras/cambiar-estatus', [OrdenController::class,'cambiarEstatus'])->name('compras.cambiar_estatus');

        // Permisos y Perfiles
        Route::get('/permisos', [AccesoController::class, 'index'])->name('permisos.index');
        Route::get('usuarios/{usuario}/permissions', [UserController::class, 'editPermissions'])->name('usuarios.edit_permissions');
        Route::put('usuarios/{usuario}/permissions', [UserController::class, 'updatePermissions'])->name('usuarios.update_permissions');
        Route::resource('perfiles', PerfilController::class)->except(['edit', 'update']); 
        Route::get('perfiles/{perfil}/permissions', [PerfilController::class, 'editPermissions'])->name('perfiles.edit_permissions');
        Route::put('perfiles/{perfil}/permissions', [PerfilController::class, 'updatePermissions'])->name('perfiles.update_permissions');
        Route::get('/api/permisos/{user}/get', [AccesoController::class, 'getPermissionsForUser'])->name('permisos.get');
        Route::post('/api/permisos/{user}/update', [AccesoController::class, 'updatePermissions'])->name('permisos.update');
        Route::post('/perfiles/{perfil}/permisos', [PerfilController::class, 'updatePermisos'])->name('perfiles.updatePermisos');

        // Combustible (Pedidos y Despachos)
        Route::prefix('combustible')->name('combustible.')->group(function () {
            Route::get('/recarga', [MovimientoCombustibleController::class, 'createRecarga'])->name('recarga');
            Route::post('/recargaStore', [MovimientoCombustibleController::class, 'storeRecarga'])->name('storeRecarga');
            Route::get('/index', [MovimientoCombustibleController::class, 'index'])->name('index');
            Route::get('/list', [MovimientoCombustibleController::class, 'list'])->name('list');
            Route::get('/despacholist', [MovimientoCombustibleController::class, 'despachoList'])->name('despachos.list');
            Route::post('/despacho-industrial/store', [MovimientoCombustibleController::class, 'storeDespachoIndustrial'])->name('storeDespachoIndustrial');
            Route::get('/despacho-industrial/create', [MovimientoCombustibleController::class, 'createDespachoIndustrial'])->name('createDespachoIndustrial');
            Route::get('/despacho-industrial/resumen', [MovimientoCombustibleController::class, 'resumenDespachos'])->name('resumenDesp');
            Route::post('/storeTraspaso', [MovimientoCombustibleController::class, 'storeTraspaso'])->name('storeTraspaso');
            Route::post('/update-field', [MovimientoCombustibleController::class, 'updateMovimientoField'])->name('updateMovimientoField');
            Route::post('/update-ticket', [MovimientoCombustibleController::class, 'updateTicket'])->name('updateTicket');
            Route::get('/estadisticas', [MovimientoCombustibleController::class, 'dashboardEstadistico'])->name('estadisticas');
            Route::get('/despacho-industrial/historial', [MovimientoCombustibleController::class, 'historialDespachosIndustrial'])->name('historialIndustrial');
            Route::get('/pedidos', [MovimientoCombustibleController::class, 'pedidos'])->name('pedidos');
            Route::post('/pedidos/{id}/aprobar', [MovimientoCombustibleController::class, 'aprobar'])->name('aprobar');
            Route::post('/pedidos/{id}/rechazar', [MovimientoCombustibleController::class, 'rechazar'])->name('rechazar');
            Route::get('/compra/crear', [MovimientoCombustibleController::class, 'createCompra'])->name('createCompra');
            Route::post('/solicitud', [MovimientoCombustibleController::class, 'storeCompra'])->name('storeCompra');
            Route::get('/compras',[MovimientoCombustibleController::class, 'comprasList'])->name('compras');
            Route::get('/flete/crear', [MovimientoCombustibleController::class, 'createFlete'])->name('createFlete');
            Route::post('/store-flete', [MovimientoCombustibleController::class, 'storeFlete'])->name('storeFlete');
            Route::get('/fletes',[MovimientoCombustibleController::class, 'fleteList'])->name('fletes');
            Route::post('/pedido/{id}/aprobar', [PedidoController::class, 'aprobar'])->name('pedido.aprobar');
            Route::post('/resumen', [MovimientoCombustibleController::class, 'generateInventoryCaption'])->name('resumen');
            Route::post('/pedido/{id}/despachar', [PedidoController::class, 'despachar'])->name('pedido.despachar');
            Route::get('/aprobados', [MovimientoCombustibleController::class, 'despachos'])->name('aprobados');
            Route::post('/despachos/{id}/despachar', [MovimientoCombustibleController::class, 'despachar'])->name('despachar');
            Route::get('/despacho', [MovimientoCombustibleController::class, 'createDespacho'])->name('despacho');
            Route::post('/despacho', [MovimientoCombustibleController::class, 'storeDespacho'])->name('storeDespacho');
            Route::get('/precarga', [MovimientoCombustibleController::class, 'createPrecarga'])->name('precarga');
            Route::post('/precarga', [MovimientoCombustibleController::class, 'storePrecarga'])->name('storePrecarga');
            Route::post('/aprobado', [MovimientoCombustibleController::class, 'storeAprobado'])->name('storeAprobado');
            Route::get('/prepago', [MovimientoCombustibleController::class, 'createPrepago'])->name('createPrepago');
            Route::post('/prepago/store', [MovimientoCombustibleController::class, 'storePrepago'])->name('storePrepago');
        });

        Route::post('/pedidos', [PedidoController::class, 'crearPedido'])->name('pedidos.store');

        // Viajes y Calendario
        Route::get('/viajes/calendario', [ViajesController::class, 'calendar'])->name('viajes.calendario');
        Route::get('/viajes/mgo', [ViajesController::class, 'createMGO'])->name('viajes.mgo');
        Route::resource('viajes', ViajesController::class)->only(['create','store', 'index', 'show']);
        Route::get('viajes/dashboard', [ViajesController::class, 'dashboard'])->name('viajes.dashboard');
        Route::get('viaje/list', [ViajesController::class, 'list'])->name('viajes.list');
        Route::get('/viajes/{id}/assign', [ViajesController::class, 'assign'])->name('viajes.assign');
        Route::put('/viajes/{id}/assign', [ViajesController::class, 'processAssignment'])->name('viajes.processAssignment');    
        Route::get('viajes/{viaje}/viaticos/edit', [ViajesController::class, 'editViaticos'])->name('viajes.viaticos.edit');
        Route::put('viajes/{viaje}/viaticos', [ViajesController::class, 'updateViaticos'])->name('viajes.viaticos.update');
        Route::delete('/viajes/{id}', [ViajesController::class, 'destroy'])->name('viajes.destroy');
        Route::get('/viajes/{id}/edit', [ViajesController::class, 'edit'])->name('viaje.edit');
        Route::post('/viajes/mgo-store', [ViajesController::class, 'storeMGO'])->name('mgo.store');
        Route::put('/viajes/{id}/update-field', [ViajesController::class, 'updateField'])->name('viaje.update.field');
        Route::put('/viajes/{viajeId}/despachos/{despachoId}', [ViajesController::class, 'updateDespacho'])->name('despacho.update.field');
        Route::put('/viajes/{id}', [ViajesController::class, 'update'])->name('viaje.update');
        Route::get('/viajes/resumen-programacion/{id?}', [ViajesController::class, 'resumenProgramacion'])->name('viajes.resumenProgramacion');
        Route::get('viajes/report/index', [ViajesController::class, 'reportsIndex'])->name('reportes.viajes');
        Route::put('viajes/report/generate', [ViajesController::class, 'generateReport'])->name('viajes.report.generate');
        Route::get('viaticos/tabulador', [ViajesController::class, 'tabuladorIndex'])->name('viaticos.tabulador');
        Route::put('viaticos/tabulador/update', [ViajesController::class, 'tabuladorUpdate'])->name('viaticos.tabulador.update');
        Route::put('viaticos/parametros/update', [ViajesController::class, 'parametrosUpdate'])->name('viaticos.parametros.update');
        Route::get('eventos', [ViajesController::class, 'getCombinedEventos'])->name('eventos');

        // Boletas y Nominaciones
        Route::get('/despachos/guia-distribucion/{viajeId}', [ViajesController::class, 'printGuiaDistribucion'])->name('despachos.guia_distribucion');
        Route::get('/despachos/boleta/{viajeId}', [ViajesController::class, 'showBoleta'])->name('despachos.boleta');
        Route::get('/despachos/nominacion/{viajeId}', [ViajesController::class, 'showNominacion'])->name('despachos.nominacion');

        // Mantenimiento
        Route::get('/mantenimiento/planificacion', [PlanificacionMantenimientoController::class, 'index'])->name('mantenimiento.planificacion.index');
        Route::get('/api/mantenimiento/eventos', [PlanificacionMantenimientoController::class, 'getEventos'])->name('mantenimiento.planificacion.eventos');
        Route::post('/api/mantenimiento/planificar', [PlanificacionMantenimientoController::class, 'store'])->name('mantenimiento.planificacion.store');

        // Alertas
        Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
        Route::get('/alertas/read/{id}', [AlertaController::class, 'markAsRead'])->name('alertas.read');

        // Reportes Globales
        Route::get('/reportes-sistema', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export_pdf');
        Route::post('/api/reports/summary', [ReportController::class, 'getSummary'])->name('reports.summary');

        // Telegram
        Route::post('/send-telegram-photo', [TelegramController::class, 'sendPhoto'])->name('telegram.send.photo');
        Route::post('/send-telegram-message', [TelegramController::class, 'sendMessage'])->name('telegram.send.message');

        // --- Loop de Recursos Genéricos (Resource Controllers) ---
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
            'usuarios' => UserController::class, // UserController@index se ejecutará aquí para los admin
            'reportes' => ReporteController::class
        ];

        foreach ($resourceControllers as $prefix => $controller) {
            $name = str_replace('-', '', $prefix);
            Route::get("$prefix/list", [$controller, 'list'])->name("$name.list");
            Route::resource($prefix, $controller)->names($name);
        }

        // Rutas Adicionales de PDF/Docs
        Route::get('/vehiculos/report/pdf', [VehiculoController::class, 'reportPdf'])->name('vehiculos.report.pdf');
        Route::get('/documentacion/vehiculos/', [VehiculoController::class, 'controlDocumentacion'])->name('vehiculos.documentacion');

        // API Interna
        Route::get('/clientes/{id}/vehiculos', function($id) {
            return App\Models\Vehiculo::where('id_cliente', $id)->select('id', 'placa', 'alias')->get();
        });

    }); // Fin Middleware StepAccess
});