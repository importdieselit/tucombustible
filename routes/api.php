<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Apis\AuthController;
use App\Http\Controllers\Apis\ClienteController;
use App\Http\Controllers\Apis\DepositoController;
use App\Http\Controllers\Apis\MovimientoCombustibleController;
use App\Http\Controllers\Apis\TanqueController;
use App\Http\Controllers\Apis\SolicitudCompraController;
use App\Http\Controllers\Apis\ProveedorController;
use App\Http\Controllers\Apis\VehiculoController;
use App\Http\Controllers\Apis\DespachoController;
use App\Http\Controllers\Apis\PedidoController;
use App\Http\Controllers\Apis\RecepcionController;
use App\Http\Controllers\Apis\MecanicoController;
use App\Http\Controllers\Apis\TestFcmController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de autenticación (públicas)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/perfiles', [AuthController::class, 'getPerfiles']);
Route::get('/auth/debug-user', [AuthController::class, 'debugUser']);

Route::post('/test-fcm-notification', [TestFcmController::class, 'sendFcmNotification']);

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Clientes
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/{id}', [ClienteController::class, 'show']);
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::put('/clientes/{id}', [ClienteController::class, 'update']);
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
    Route::get('/cliente/info', [ClienteController::class, 'info']);
    Route::get('/cliente/mis-datos', [ClienteController::class, 'misDatos']);
    Route::put('/cliente/disponible', [ClienteController::class, 'updateDisponible']);
    
    // Depósitos
    Route::get('/depositos/mis-depositos', [DepositoController::class, 'getMisDepositos']);
    Route::get('/depositos/mis-estadisticas', [DepositoController::class, 'getMisEstadisticas']);
    Route::get('/depositos/cliente/{clienteId}', [DepositoController::class, 'getByCliente']);
    Route::get('/depositos/en-alerta', [DepositoController::class, 'getEnAlerta']);
    Route::get('/depositos/estadisticas', [DepositoController::class, 'getEstadisticas']);
    Route::get('/depositos/admin', [DepositoController::class, 'getAllDepositos']);
    Route::get('/depositos', [DepositoController::class, 'index']);
    Route::get('/depositos/{id}', [DepositoController::class, 'show']);
    Route::post('/depositos', [DepositoController::class, 'store']);
    Route::put('/depositos/{id}', [DepositoController::class, 'update']);
    Route::delete('/depositos/{id}', [DepositoController::class, 'destroy']);
    
    // Movimientos de Combustible
    Route::get('/movimientos-combustible', [MovimientoCombustibleController::class, 'index']);
    Route::get('/movimientos-combustible/{id}', [MovimientoCombustibleController::class, 'show']);
    Route::post('/movimientos-combustible', [MovimientoCombustibleController::class, 'store']);
    Route::put('/movimientos-combustible/{id}', [MovimientoCombustibleController::class, 'update']);
    Route::delete('/movimientos-combustible/{id}', [MovimientoCombustibleController::class, 'destroy']);
    
    // Tanques
    Route::get('/tanques', [TanqueController::class, 'index']);
    Route::get('/tanques/{id}', [TanqueController::class, 'show']);
    Route::post('/tanques', [TanqueController::class, 'store']);
    Route::put('/tanques/{id}', [TanqueController::class, 'update']);
    Route::delete('/tanques/{id}', [TanqueController::class, 'destroy']);
    Route::get('/tanques/producto/filtrar', [TanqueController::class, 'porProducto']);
    Route::get('/tanques/productos/lista', [TanqueController::class, 'productos']);
    
    // Solicitudes de Compra
    Route::get('/solicitudes-compra', [SolicitudCompraController::class, 'index']);
    Route::get('/solicitudes-compra/{id}', [SolicitudCompraController::class, 'show']);
    Route::post('/solicitudes-compra', [SolicitudCompraController::class, 'store']);
    Route::put('/solicitudes-compra/{id}', [SolicitudCompraController::class, 'update']);
    Route::put('/solicitudes-compra/{id}/cancelar', [SolicitudCompraController::class, 'cancelar']);
    
    // Proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index']);
    Route::get('/proveedores/{id}', [ProveedorController::class, 'show']);
    Route::post('/proveedores', [ProveedorController::class, 'store']);
    Route::put('/proveedores/{id}', [ProveedorController::class, 'update']);
    Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy']);
    
    // Vehículos
    Route::get('/vehiculos', [VehiculoController::class, 'index']);
    Route::get('/vehiculos/{id}', [VehiculoController::class, 'show']);
    Route::post('/vehiculos', [VehiculoController::class, 'store']);
    Route::put('/vehiculos/{id}', [VehiculoController::class, 'update']);
    Route::delete('/vehiculos/{id}', [VehiculoController::class, 'destroy']);
    Route::get('/vehiculos/marcas', [VehiculoController::class, 'marcas']);
    
    // Despachos
    Route::get('/despachos', [DespachoController::class, 'index']);
    
    // Pedidos - Administrador (deben ir ANTES que las rutas con {id})
    Route::get('/pedidos/pendientes', [PedidoController::class, 'getPedidosPendientes']);
    Route::get('/pedidos/todos', [PedidoController::class, 'getTodosLosPedidos']);
    Route::patch('/pedidos/{id}/aprobar', [PedidoController::class, 'aprobarPedido']);
    Route::patch('/pedidos/{id}/rechazar', [PedidoController::class, 'rechazarPedido']);
    Route::patch('/pedidos/{id}', [PedidoController::class, 'actualizarPedido']);
    
    // Pedidos - Clientes
    Route::get('/pedidos/mis-pedidos', [PedidoController::class, 'getMisPedidos']);
    Route::get('/pedidos/estadisticas', [PedidoController::class, 'getEstadisticasPedidos']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'getPedido']);
    Route::post('/pedidos', [PedidoController::class, 'crearPedido']);
    Route::post('/pedidos/{id}/calificar', [PedidoController::class, 'calificarPedido']);
    Route::post('/pedidos/{id}/cancelar', [PedidoController::class, 'cancelarPedido']);

    // Recepciones
    Route::post('/recepciones', [RecepcionController::class, 'registrarRecepcion']);
    
    // Mecánico
    Route::get('/mecanico/estadisticas', [MecanicoController::class, 'getEstadisticas']);
    Route::get('/mecanico/depositos', [MecanicoController::class, 'getDepositos']);
    Route::get('/mecanico/tanques', [MecanicoController::class, 'getTanques']);
    Route::get('/mecanico/vehiculos', [MecanicoController::class, 'getVehiculos']);
    Route::get('/mecanico/pedidos', [MecanicoController::class, 'getPedidos']);
    Route::get('/mecanico/proveedores', [MecanicoController::class, 'getProveedores']);
    Route::post('/mecanico/egreso-despacho', [MecanicoController::class, 'realizarEgresoDespacho']);
    Route::post('/mecanico/ingreso-recarga', [MecanicoController::class, 'realizarIngresoRecarga']);
    Route::post('/mecanico/check-in-out', [MecanicoController::class, 'realizarCheckInOut']);
    
    Route::get('/despachos/estadisticas', [DespachoController::class, 'estadisticas']);
    Route::get('/despachos/{id}', [DespachoController::class, 'show']);
    Route::post('/despachos', [DespachoController::class, 'store']);
    Route::put('/despachos/{id}', [DespachoController::class, 'update']);
    Route::delete('/despachos/{id}', [DespachoController::class, 'destroy']);


    
    // FCM - Notificaciones Push
    Route::post('/auth/update-fcm-token', [\App\Http\Controllers\Apis\FcmController::class, 'updateToken']);
    Route::get('/auth/fcm-token', [\App\Http\Controllers\Apis\FcmController::class, 'getToken']);
    Route::delete('/auth/fcm-token', [\App\Http\Controllers\Apis\FcmController::class, 'removeToken']);


    
}); 

