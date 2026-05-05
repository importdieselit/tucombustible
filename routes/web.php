<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// IMPORTACIÓN DE CONTROLADORES
use App\Http\Controllers\Admin\ClienteController as AdminClienteController;
use App\Http\Controllers\Admin\PedidoAdminController;
use App\Http\Controllers\ClienteController as PortalClienteController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Notifications\OrdenTrabajoCreada;
use App\Models\User;
use App\Models\Orden;
use App\Http\Controllers\{
    DashboardController, VehiculoController, MarcaController, ModeloController,
    OrdenController, TanqueController, MovimientoCombustibleController,
    InventarioController, ProveedorController, PerfilController, UserController,
    DepositoController, AlmacenController, ChoferController,
    AlertaController, AccesoController, InspeccionController, PedidoController,
    ReporteController, AforoController, SearchController, DataDeletionController,
    ViajesController, TelegramController, PlanificacionMantenimientoController,
    ReportController, ClienteActivosController,NotificationController,LogisticaController
};

/* --- Rutas Públicas y Auth --- */
Auth::routes(['reset' => false]);
Route::get('/', function () { return redirect()->route('login'); });

Route::get('/test-push', function () {
    $user = User::find(1); // Tu usuario
    
    // Buscamos la última orden para tener datos reales que mostrar
    $orden = Orden::with('vehiculoBelong')->latest()->first(); 

    if (!$orden) {
        return "No hay órdenes en la base de datos para probar.";
    }

    // Pasamos la $orden al constructor
    $user->notify(new OrdenTrabajoCreada($orden)); 
    
    return "Notificación de prueba enviada a " . $user->nombre . " con la Orden #" . $orden->id;
});

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

    /**
     * PASO 2: SEGURIDAD INICIAL (Cambio de Contraseña Obligatorio)
     * Esta zona es accesible incluso si check.password está activo, 
     * ya que es el destino del middleware.
     */
    Route::post('/notifications/subscribe', [NotificationController::class, 'subscribe']);
    Route::get('/notifications/subscribe/get', [NotificationController::class, 'subscribe']);
    Route::get('/password/change', [UserController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/update', [UserController::class, 'updatePassword'])->name('password.update');
    Route::put('/depositos', [DepositoController::class, 'index'])->name('depositos');
   
    Route::get('/clientes/{id}/vehiculos', function($id) {
        return App\Models\Vehiculo::where('id_cliente', $id)->select('id', 'placa', 'alias')->get();
    });


    
     // Inspecciones y Vehículos
    Route::get('/marcas/get-modelos', [MarcaController::class, 'getModelos'])->name('marcas.getModelos');
    Route::get('/vehiculos/inspeccion/{vehiculo_id}/{tipo}', [InspeccionController::class, 'create'])->name('inspeccion.create');
    Route::put('vehiculos/updatev/{id}', [VehiculoController::class, 'updateV'])->name('vehiculos.updatev');
    Route::get('/inspecciones/{inspeccion_id}/pdf', [InspeccionController::class, 'exportPdf'])->name('inspecciones.pdf');
    Route::get('/reporte/vehiculos-disponibilidad', [VehiculoController::class, 'reporteDisponibilidad'])->name('vehiculos.reporte.disponibilidad');
    Route::get('/vehiculos/report/pdf', [VehiculoController::class, 'reportPdf'])->name('vehiculos.report.pdf');
    Route::get('/documentacion/vehiculos/', [VehiculoController::class, 'controlDocumentacion'])->name('vehiculos.documentacion');
    Route::post('vehiculos/acoplar', [VehiculoController::class, 'acoplar'])->name('vehiculos.acoplar');
    Route::get('/vehiculos/desacoplar/{id}', [VehiculoController::class, 'desacoplar'])->name('vehiculos.desacoplar');
    

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
    Route::get('/ventas/create', [InventarioController::class, 'ventaCreate'])->name('ventas.create');
    Route::post('/ventas/store', [InventarioController::class, 'ventaStore'])->name('ventas.store');
    Route::post('/clientes/store-ajax', [PortalClienteController::class, 'storeAjax'])->name('clientes.storeAjax');  
    Route::get('/ventas/list', [InventarioController::class, 'ventaList'])->name('ventas.list'); 
    Route::get('/ventas/show/{id}', [InventarioController::class, 'ventaShow'])->name('ventas.show'); 

    // Importaciones
    Route::get('choferes/importar', [ChoferController::class, 'showImportForm'])->name('choferes.show-import-form');
    Route::post('choferes/importar', [ChoferController::class, 'importar'])->name('choferes.importar');
    Route::post('choferes/upload/doc/{id}',[ChoferController::class, 'uploadDocumento'])->name('choferes.upload.doc');
    Route::post('/choferes/update-cargo/{id}', [App\Http\Controllers\ChoferController::class, 'updateCargo'])
    ->name('choferes.update-cargo');
    Route::get('/vehiculos/import', [VehiculoController::class, 'importForm'])->name('vehiculos.import');
    Route::post('/vehiculos/import', [VehiculoController::class, 'importSave'])->name('vehiculos.import.save');
    Route::get('clientes/import', [PortalClienteController::class, 'import'])->name('clientes.import');
    Route::post('clientes/handle', [PortalClienteController::class, 'handleImport'])->name('clientes.handleImport');
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
        Route::post('/ordenes/{id}/trabajo-externo', [OrdenController::class, 'addTrabajoExterno'])->name('ordenes.addTrabajoExterno');
        Route::post('/ordenes/supplies/receive/{supply}', [OrdenController::class, 'markAsReceived'])->name('ordenes.supplies.receive');
        Route::post('/ordenes/compras/receive/{supply}', [OrdenController::class, 'markRequestReceived'])->name('ordenes.compras.receive');
        Route::get('ordenes/compras/{id_order?}/{id?}', [OrdenController::class, 'purchaseOrder'])->name('ordenes.compra');
        Route::post('/compras/actualizar-precio', [OrdenController::class,'actualizarPrecio'])->name('compras.actualizar_precio');
        Route::post('/compras/cambiar-estatus', [OrdenController::class,'cambiarEstatus'])->name('compras.cambiar_estatus');
        Route::post('/get-tempario-servicios', [OrdenController::class, 'getTemparioServicios'])->name('get.tempario_servicios');
        Route::post('/ordenes/{id}/trabajos/add', [OrdenController::class, 'addTrabajo'])->name('ordenes.addTrabajo');
        Route::delete('/ordenes/trabajos/{id}/delete', [OrdenController::class, 'deleteTrabajo'])->name('ordenes.deleteTrabajo');
        Route::post('/ordenes/{id}/addTrabajosMasivo', [OrdenController::class, 'addTrabajosMasivo'])->name('ordenes.addTrabajosMasivo');
        Route::post('/ordenes/trabajo/{id}/finalizar', [OrdenController::class, 'finalizarTrabajo'])->name('ordenes.trabajo.finalizar');
        Route::post('/ordenes/{id}/insumos/add', [OrdenController::class, 'addInsumo'])->name('ordenes.addInsumo');
        Route::get('/vehiculos/{id}/orden-abierta', [OrdenController::class, 'verificarOrdenAbierta'])->name('vehiculos.checkOrden');
        Route::post('ordenes/{id}/add-manual-supply', [OrdenController::class, 'addManualSupply'])->name('ordenes.addManualSupply');
        Route::delete('/ordenes/purchase/{id}/delete', [OrdenController::class, 'deleteManualSupply'])->name('ordenes.deleteManualSupply');
        Route::post('/ordenes/{id}/habilitar-unidad', [OrdenController::class, 'habilitarUnidad'])->name('vehiculos.habilitarUnidad');
        Route::get('/planes-mantenimiento/api/{id}', [OrdenController::class, 'getPlanApi'])->name('planes.api');
        // Rutas para Evidencias Fotográficas
        Route::post('/ordenes/{id}/fotos/add', [OrdenController::class, 'addFotos'])->name('ordenes.fotos.add');
        Route::delete('/ordenes/fotos/{id}/delete', [OrdenController::class, 'destroyFoto'])->name('ordenes.fotos.destroy');
    

        // Permisos y Perfiles
        Route::get('/permisos', [AccesoController::class, 'index'])->name('permisos.index');
        Route::get('usuarios/{usuario}/permissions', [UserController::class, 'editPermissions'])->name('usuarios.edit_permissions');
        Route::put('usuarios/{usuario}/permissions', [UserController::class, 'updatePermissions'])->name('usuarios.update_permissions');
        Route::post('usuarios/{id}/update-single-permission', [UserController::class, 'updateSinglePermission'])->name('usuarios.update_single_permission');
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
        Route::get('viajes/dashboard', [ViajesController::class, 'dashboard'])->name('viajes.dashboard');
        Route::get('/viajes/{id}/assign', [ViajesController::class, 'assign'])->name('viajes.assign');
        Route::put('/viajes/{id}/assign', [ViajesController::class, 'processAssignment'])->name('viajes.processAssignment');    
        Route::get('viajes/{viaje}/viaticos/edit', [ViajesController::class, 'editViaticos'])->name('viajes.viaticos.edit');
        Route::put('viajes/{viaje}/viaticos', [ViajesController::class, 'updateViaticos'])->name('viajes.viaticos.update');
        Route::delete('/viajes/{id}', [ViajesController::class, 'destroy'])->name('viaje.destroy');
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
  

        Route::get('/admin/principal', [DashboardController::class, 'adminPrincipal'])->name('dashboard.admin');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /**
         * MÓDULOS DE ADMINISTRACIÓN (Perfiles 1 y 2: Admin y Super)
         */
        Route::middleware(['role:1,2,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18'])->group(function () {
            
            
            // CRUDs MAESTROS Y RECURSOS
            $resourceControllers = [
                'vehiculos'                   => VehiculoController::class,
                'viajes'                      => ViajesController::class,
                'marcas'                      => MarcaController::class,
                'modelos'                     => ModeloController::class,
                'choferes'                    => ChoferController::class,
                'ordenes'                     => OrdenController::class,
                'tanques'                     => TanqueController::class,
                'depositos'                   => DepositoController::class,
                'almacenes'                   => AlmacenController::class,
                'inventario'                  => InventarioController::class,
                'proveedores'                 => ProveedorController::class,
                'perfiles'                    => PerfilController::class,
                'usuarios'                    => UserController::class,
                'inspecciones'                => InspeccionController::class,
                'pedidos'                     => PedidoController::class,
                'reportes'                    => ReporteController::class,
                'combustible'                 => MovimientoCombustibleController::class
            ]; 

            foreach ($resourceControllers as $prefix => $controller) {
                $name = str_replace('-', '', $prefix);
                Route::get($prefix . "/list", [$controller, 'list'])->name($name.'.list');
                Route::resource($prefix, $controller)->names($name);
            }

            // Reportes y Herramientas Admin
            Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
            Route::get('/aforos', [AforoController::class, 'index'])->name('aforos.index');
            Route::get('/search', [SearchController::class, 'query'])->name('search');
        });

        // --- MÓDULO DE LOGÍSTICA ---
        Route::middleware(['auth', 'role:1,2,6,11,12,18'])->prefix('logistica')->name('logistica.')->group(function () {
            
            Route::get('/planificacion', [LogisticaController::class, 'index'])->name('index');
            Route::get('/crear/{tipo?}', [LogisticaController::class, 'create'])->name('create');
            Route::post('/guardar', [LogisticaController::class, 'store'])->name('store');
            // Rutas para Edición
            Route::get('/{id}', [LogisticaController::class, 'show'])->name('show');
            Route::get('/{id}/editar', [LogisticaController::class, 'edit'])->name('edit');
            Route::put('/{id}/actualizar', [LogisticaController::class, 'update'])->name('update');
            Route::post('/{id}/cancelar', [LogisticaController::class, 'cancelar'])->name('cancelar');
        });

        // --- MÓDULO CLIENTES COMBUSTIBLE ---
        Route::prefix('admin-clientes')->name('clientes.')->group(function () {
            Route::get('/',                    [AdminClienteController::class, 'index'])->name('index');
            Route::get('/crear',               [AdminClienteController::class, 'create'])->name('create');
            Route::post('/',                   [AdminClienteController::class, 'store'])->name('store');
            Route::get('/{id}/expediente',     [AdminClienteController::class, 'show'])->name('show');
            Route::get('/{id}/editar',         [AdminClienteController::class, 'edit'])->name('edit');
            Route::put('/{id}',                [AdminClienteController::class, 'update'])->name('update');
            Route::post('/clientes/{id}/generar-token', [AdminClienteController::class, 'generarToken'])->name('generar-token');

            // Flujo de registro
            Route::post('/{id}/avanzar-paso',  [AdminClienteController::class, 'avanzarPaso'])->name('avanzarPaso');

            // Gestión de status
            Route::post('/{id}/aprobar',       [AdminClienteController::class, 'aprobar'])->name('aprobar');
            Route::post('/{id}/rechazar',      [AdminClienteController::class, 'rechazar'])->name('rechazar');
            Route::post('/{id}/inactivar',     [AdminClienteController::class, 'inactivar'])->name('inactivar');
            Route::post('/{id}/reactivar',     [AdminClienteController::class, 'reactivar'])->name('reactivar');

            // Cupos
            Route::post('/{id}/ajustar-cupo',  [AdminClienteController::class, 'ajustarCupo'])->name('ajustarCupo');
            Route::post('/{id}/gasco-cupo', [AdminClienteController::class, 'asignarCupoGasco'])->name('gasco.asignar');

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

        /**
         * ZONA CLIENTES (Solo Perfil 3)
         */
        Route::middleware(['role:3'])->prefix('mi-cuenta')->name('portal.clientes.')->group(function () {
            Route::get('/resumen',   [PortalClienteController::class, 'index'])->name('index');
            Route::get('/mi-perfil', [PortalClienteController::class, 'perfil'])->name('perfil');
            Route::get('/pedidos',           [PedidoController::class, 'index'])->name('pedidos.index');
            Route::get('/pedidos/nuevo',     [PedidoController::class, 'create'])->name('pedidos.create');
            Route::post('/pedidos/guardar',  [PedidoController::class, 'store'])->name('pedidos.store');
            Route::put('/pedidos/{id}/cancelar', [PedidoController::class, 'cancelar'])->name('pedidos.cancelar');
        });
});