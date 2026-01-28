<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCombustible;
use App\Models\Deposito;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\DespachoViaje;
use App\Models\Parametro;
use App\Models\Vehiculo;
use App\Models\Pedido;
use App\Models\TabuladorViatico;
use App\Models\VehiculoPrecargado;
use App\Models\Planta;
use App\Models\Chofer;
use App\Models\CompraCombustible;
use App\Models\Viaje;
use App\Models\User;
use App\Models\Persona;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;  
use App\Traits\GenerateAlerts;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmNotificationService;
use App\Services\TelegramNotificationService; 
use App\Models\ViaticoViaje;  

/**
 * Controlador para gestionar los movimientos de combustible (recarga y despacho).
 */
class MovimientoCombustibleController extends Controller
{
    use GenerateAlerts;

    protected $fcmService;
    protected $telegramService;

    public function __construct(
        FcmNotificationService $fcmService, 
        TelegramNotificationService $telegramService
    ) {
        $this->fcmService = $fcmService;
        $this->telegramService = $telegramService;
    }



    public function index()
    {
        // 1. Indicadores de clientes
        // Obtenemos todos los clientes con parent 0.
        $clientesPadre = Cliente::where('parent', 0)
                                ->select('nombre', 'disponible', 'cupo','id')
                                ->orderBy('nombre', 'asc')->get();
        $clientes = Cliente::orderBy('nombre', 'asc')->get();

        $user = Auth::user();
        
        // 2. Gráficas de disponibilidad de clientes.
        // Los datos para la gráfica los podemos pasar directamente del controlador a la vista.
        $disponibilidadData = $clientesPadre->map(function ($cliente) {
            if($cliente->cupo>0){
                $disponible=round(($cliente->disponible / $cliente->cupo) * 100,2);
                
            }else{
                $disponible=0;
                
            }

            return [
                    'nombre' => $cliente->nombre,
                    'disponible'=>$disponible,
                ];
        });

        // 3. Indicadores de pedidos pendientes y en proceso.
        $pedidosPendientes = Pedido::where('estado', 'pendiente')->count();
        $pedidosEnProceso = Pedido::where('estado', 'en_proceso')->count();

        // 4. Niveles de los depósitos.
        $tipoDeposito = Deposito::select('producto')->distinct()->get();
        foreach($tipoDeposito as $t){
            $t->total = Deposito::where('producto',$t->producto)->sum('nivel_actual_litros');
            $t->producto = $t->producto;
            $t->capacidad = Deposito::where('producto',$t->producto)->sum('capacidad_litros');
            $t->nivel = $t->capacidad > 0 ? round(($t->total / $t->capacidad) * 100,2) : 0;
            $t->depositos = Deposito::where('producto',$t->producto)->get();
            foreach($t->depositos as $d){
                $d->nivel = $d->capacidad_litros > 0 ? round(($d->nivel_actual_litros / $d->capacidad_litros) * 100,2) : 0;
            }
        }
       // dd($tipoDeposito);
       $resguardo=Parametro::where('nombre','resguardo')->first()->valor;
        $totalCombustible = Deposito::whereIn('serial',['1','2','3','4'])->sum('nivel_actual_litros');
        $tanque00=Deposito::where('serial','00')->first();
        $capacidadTotal = Deposito::whereIn('serial',['1','2','3','4'])->sum('capacidad_litros');
        $totalCombustible=$totalCombustible-$resguardo;
        $nivelPromedio = $capacidadTotal > 0 ? ($totalCombustible / $capacidadTotal) * 100 : 0;
        $nivelPromedio = round($nivelPromedio, 2);

         $pedidos = Pedido::whereNotIn('estado', ['entregado', 'cancelado','completado']);

           // Obtiene la colección de pedidos después de aplicar los filtros.
        $pedidosCollection = $pedidos->get();

        // Mapea la colección a la estructura de datos para el dashboard.
        $pedidos = $pedidosCollection->map(function ($pedido) {
            return [
                'id' => $pedido->id,
                'cantidad' => number_format($pedido->cantidad_solicitada, 2, ',', '.') . ' L',
                'cliente' => $pedido->cliente->nombre,
                'estado' => ucwords($pedido->estado),
                'observacion' => $pedido->observaciones,
                'fecha' => $pedido->fecha_solicitud->format('d/m/Y H:i'),
                'tipo' => 'pedido', // Identificador para el front-end
            ];
        })->toArray();

        $tanquesDisponibles = Deposito::select('nivel_actual_litros as disponible', 'serial as nombre','capacidad_litros as capacidad','id')->get();

        // 5. Camiones cargados.
        // Asumimos que tienes un campo 'estado' en la tabla de vehículos o una relación
        // que te permite saber si un camión está cargado.
        // Por ejemplo, un estado 'cargado' o 'en_ruta_con_combustible'.
        $camionesCargados = VehiculoPrecargado::where('estatus', 0)->count();
        $vehiculosDisponibles = Vehiculo::where('estatus', 1)->where('id_cliente',$user->cliente_id)->get();
        $vehiculos = Vehiculo::where('estatus', 1)->where('es_flota',true)->get();

        $fechaInicio = Carbon::now()->subDays(30)->toDateString();
        $resumen = MovimientoCombustible::where('created_at', '>=', $fechaInicio)
            ->groupBy('tipo_movimiento')
            ->select('tipo_movimiento', DB::raw('SUM(cantidad_litros) as total_litros'))
            ->get();

        // 3. Formatear el resultado para un objeto de resumen
        $totales = ['entradas' => 0.0, 'salidas' => 0.0,'periodo_inicio' => $fechaInicio,'periodo_fin' => Carbon::now()->toDateString(),];

        foreach ($resumen as $movimiento) {
            $tipo = strtolower($movimiento->tipo_movimiento);
            if (str_contains($tipo, 'entrada')) {
                $totales['entradas'] = (float) $movimiento->total_litros;
            } elseif (str_contains($tipo, 'salida')) {
                $totales['salidas'] = (float) $movimiento->total_litros;
            }
        }
        $viajesHoy = Viaje::whereDate('fecha_salida', now())->count();
        // 1. Filtrar los Viajes por el mes actual
        $viajesDelMes = Viaje::whereMonth('fecha_salida', now()->month)->whereYear('fecha_salida', now()->year)
        ->where('destino_ciudad', 'NOT LIKE', 'FLETE%'); // Excluir los viajes de tipo FLETE

        // 2. Sumar la columna 'litros' de la relación 'despachos' (HasMany)
        //    y asignarle un alias (ej: 'total_litros_despachados')
        $ventasMes = $viajesDelMes->withSum('despachos', 'litros')
                                // Luego seleccionamos todas las sumas generadas y las sumamos globalmente
                                ->get()
                                ->sum('despachos_sum_litros');

        $comprasMes = CompraCombustible::whereMonth('fecha', now()->month)->whereYear('fecha', now()->year)->sum('cantidad_litros');
        // Pasamos todos los datos a la vista.
        return view('combustible.index', compact(
            'clientes', 
            'disponibilidadData',
            'pedidosPendientes', 
            'pedidosEnProceso', 
            'tipoDeposito', 
            'camionesCargados',
            'totalCombustible',
            'capacidadTotal',
            'nivelPromedio',
            'pedidos',
            'vehiculosDisponibles',
            'tanquesDisponibles',
            'vehiculos',
            'totales',
            'tanque00',
            'resguardo',
            'viajesHoy',
            'ventasMes',
            'comprasMes'
        ));
    }

     public function generateInventoryCaption(): string
    {
        // 1. Obtener todos los depósitos/tanques
        // Asumiendo que el modelo Deposito tiene campos para 'serial', 'nivel' (cm) y 'stock' (litros)
        $tanques = Deposito::orderBy('serial', 'asc')->get(); 

        $totalVenta = 0;
        $resguardoLitros = Parametro::where('nombre','resguardo')->first()->valor; // Valor fijo según tu requerimiento, se recomienda moverlo a un Parametro
        
        $tanquesDetalles = [];
        
        foreach ($tanques as $tanque) {
            // El 'nivel' puede ser la medida en cm (como en tu ejemplo)
            $nivel_cm = number_format($tanque->nivel_cm, 1, ',', '.'); 
            // El 'stock' es el volumen en litros
            $stock_litros = number_format($tanque->nivel_actual_litros , 2, ',', '.');
            
            // Asumo que solo los tanques tipo 'DSL' y '00' son relevantes para el inventario de venta.
            // Si quieres filtrar, puedes añadir: ->whereIn('tipo', ['DSL', '00']) en la consulta.

            $tanquesDetalles[] = "Tanque {$tanque->serial}\n{$nivel_cm} cm = {$stock_litros} lts.";
            if($tanque->serial !=='00'){
                $totalVenta += $tanque->nivel_actual_litros;
            }
        }

        // Restamos el resguardo (si aplica)
        $disponiblesParaVenta = $totalVenta - $resguardoLitros;
        
        // 2. Construir el mensaje formateado en Markdown para Telegram
        $date = Carbon::now()->isoFormat('HH:mm a'); // Hora actual
        $today = Carbon::now()->isoFormat('D [de] MMMM [del] YYYY');

        $caption = "*Reporte de Inventario de Combustible - Impordiesel*\n";
        $caption .= "Generado el: {$today} a las {$date}\n\n";
        
        $caption .= "Inventario área de almacenamiento:\n";
        $caption .= implode("\n\n", $tanquesDetalles) . "\n\n";

        // Líneas de resumen
        if($resguardoLitros > 0){
            $caption .= "*En resguardo:* " . number_format($resguardoLitros, 0, ',', '.') . " lts.\n";
        }
        $caption .= "*Disponibles para la venta:* " . number_format($disponiblesParaVenta, 2, ',', '.') . " lts.";

        return $caption;
    }

    public function storeAprobado(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'cantidad_aprobada' => 'required|numeric|min:0.01',
                'observaciones_admin' => 'nullable|string',
            ]);

            // Obtener el cliente
            $cliente = Cliente::findOrFail($request->cliente_id);

            // Verificar si el cliente tiene saldo suficiente (opcional, pero recomendado)
            if ($validatedData['cantidad_aprobada'] > $cliente->disponible) {
                 Session::flash('error', 'La cantidad aprobada excede el saldo disponible del cliente.');
                return Redirect::back()->withInput()->withErrors(['cantidad_aprobada' => 'La cantidad aprobada excede el saldo disponible del cliente.']);
            }

            // Crear el nuevo pedido con estado 'aprobado'
            $pedido = Pedido::create([
                'cliente_id' => $validatedData['cliente_id'],
                'cantidad_solicitada' => $validatedData['cantidad_aprobada'], // En este caso, solicitada es igual a aprobada
                'cantidad_aprobada' => $validatedData['cantidad_aprobada'],
                'estado' => 'aprobado',
                'observaciones' => 'Pedido creado y aprobado directamente por el administrador.',
                'observaciones_admin' => $validatedData['observaciones_admin'],
                'fecha_solicitud' => now(),
                'fecha_aprobacion' => now(),
            ]);

            try {
                FcmNotificationService::sendPedidoStatusNotification(
                    $pedido,
                    'pendiente',
                    'aprobado',
                    $validatedData['observaciones_admin']
                );
                Log::info("Notificación FCM enviada al cliente {$pedido->cliente_id} por aprobación de pedido");
            } catch (\Exception $e) {
                Log::error("Error enviando notificación FCM: " . $e->getMessage());
                // No fallar la operación principal por error en notificación
            }
            // Actualizar el saldo del cliente (se resta la cantidad aprobada)
            $cliente->disponible -= $validatedData['cantidad_aprobada'];
            $cliente->save();

            DB::commit();
            Session::flash('success', 'Pedido creado y aprobado exitosamente para el cliente ' . $cliente->nombre . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear y aprobar el pedido: " . $e->getMessage());
            Session::flash('error', 'Error al crear y aprobar el pedido. Por favor, revisa los logs.');
        }

        return redirect()->route('combustible.aprobados'); // Redireccionar a la lista de pedidos
    }

public function createPrecarga()
    {
        $depositos = Deposito::all();
        $vehiculos_cisterna = Vehiculo::where('tipo', 2)->get();
        return view('combustible.precarga', compact('depositos', 'vehiculos_cisterna'));
    }
    
    /**
     * Almacena una nueva precarga de combustible.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePrecarga(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'fecha_movimiento' => 'required|date',
                'deposito_id' => 'required|exists:depositos,id',
                'cantidad_litros' => 'required|numeric|min:0.01',
                'vehiculo_id' => 'required|exists:vehiculos,id'
            ]);
            
            $deposito = Deposito::findOrFail($request->input('deposito_id'));
            $vehiculo = Vehiculo::findOrFail($request->input('vehiculo_id'));
            $cantidad = $request->input('cantidad_litros');
            $userId = Auth::id();

            // Validar si la cantidad solicitada supera la disponible en el depósito
            if ($cantidad > $deposito->nivel_actual_litros) {
                Session::flash('error', 'La cantidad a cargar excede el inventario del depósito seleccionado.');
                return Redirect::back();
            }

            // Registrar el movimiento de combustible
            $movimiento = new MovimientoCombustible();
            $movimiento->deposito_id = $deposito->id;
            $movimiento->tipo_movimiento = 'precarga';
            $movimiento->cantidad_litros = $cantidad;
            $movimiento->observaciones = 'Precarga de combustible a cisterna ' . $vehiculo->placa;
            $movimiento->save();

            // Actualizar el saldo del depósito
            $deposito->nivel_actual_litros -= $cantidad;
            $deposito->save();
            
            // Crear registro en la tabla vehiculos_precargados
            $precarga = new VehiculoPrecargado();
            $precarga->id_vehiculo = $vehiculo->id;
            $precarga->cantidad_cargada = $cantidad;
            $precarga->fecha_hora_carga = now();
            $precarga->estatus = 0; // 0 = cargada
            $precarga->tipo_producto = substr($deposito->producto,0,1); // Tipo de producto
            $precarga->save();

            // Generar alerta si el nivel del depósito es bajo
            if ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.1) {
                $this->createAlert([
                    'id_usuario' => $userId,
                    'id_rel' => $deposito->id,
                    'observacion' => 'El nivel del depósito \"' . $deposito->nombre . '\" es crítico: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id),
                    'dias' => 0,
                ]);
            } elseif ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.25) {
                $this->createAlert([
                    'id_usuario' => $userId,
                    'id_rel' => $deposito->id,
                    'observacion' => 'El nivel del depósito \"' . $deposito->nombre . '\" es bajo: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id),
                    'dias' => 0,
                ]);
            }

            DB::commit();
            Session::flash('success', 'Precarga realizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al realizar la precarga: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Session::flash('error', 'Error al realizar la precarga. Por favor, revisa los logs de la aplicación.');
        }

        return Redirect::back();
    }

    /**
     * Muestra el formulario para registrar una recarga de combustible.
     * @return \Illuminate\View\View
     */
    public function createRecarga()
    {
        // Se obtienen todos los depósitos y clientes para los dropdowns del formulario.
        $depositos = Deposito::all();
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $hoy = now()->format('Y-m-d\TH:i'); // Obtiene la fecha actual en formato YYYY-MM-DD
        
        return view('combustible.despacho', compact('depositos', 'clientes', 'hoy'));
    }

    public function createDespachoIndustrial()
    {
        $clientes = Cliente::where('prepagado', '>', 0)->orWhere('periodo', 'P')->orderBy('nombre', 'asc')->get();
        $tanque00 = Deposito::find(3); 
        $t3= Deposito::find(6);
        $hoy = Carbon::now()->format('Y-m-d\TH:i');
        return view('combustible.despacho_industrial', compact('clientes', 'tanque00', 'hoy','t3'));
    }

    /**
     * Almacena una nueva recarga de combustible en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRecarga(Request $request)
    {
        $userId = auth()->id(); // Obtener el ID del usuario autenticado
        // 1. Validación de los datos
        $request->validate([
            'fecha' => 'required|date',
            'deposito_id' => 'required|exists:depositos,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'cantidad_litros' => 'required|numeric|min:1',
            'observaciones' => 'nullable|string'
        ]);

        try {
            // 2. Buscar el depósito para actualizar el nivel
            $deposito = Deposito::findOrFail($request->deposito_id);

            // Verificación para no exceder la capacidad
            $nuevo_nivel = $deposito->nivel_actual_litros + $request->cantidad_litros;
            if ($nuevo_nivel > $deposito->capacidad_litros) {
                Session::flash('error', 'La cantidad de recarga excede la capacidad del depósito. Nivel actual: ' . $deposito->nivel_actual_litros . ' L. Capacidad: ' . $deposito->capacidad_litros . ' L.');
                return Redirect::back()->withInput();
            }
            $inicial=$deposito->nivel_actual_litros;
            $final=$deposito->nivel_actual_litros+$request->cantidad_litros;
            // 3. Crear el registro del movimiento
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = $request->fecha; // Asignar la fecha del formulario
            $movimiento->tipo_movimiento = 'entrada';
            $movimiento->deposito_id = $request->deposito_id;
            $movimiento->proveedor_id = $request->proveedor_id;
            $movimiento->cantidad_litros = $request->cantidad_litros;
            $movimiento->observaciones = $request->observaciones;
            $movimiento->cant_inicial = $inicial;
            $movimiento->cant_final = $final;
            $movimiento->save();
            
            // 4. Actualizar el nivel actual del depósito
            $deposito->nivel_actual_litros = $nuevo_nivel;
            $deposito->save();
            

            Session::flash('success', 'Recarga de combustible registrada exitosamente.');
            return Redirect::route('combustible.recarga');

        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Depósito o proveedor no encontrado.');
            Log::error('Error al registrar recarga: ' . $e->getMessage());
            return Redirect::back()->withInput();
        } catch (\Exception $e) {
            Session::flash('error', 'Hubo un error al procesar la recarga.');
            Log::error('Error al registrar recarga: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }
    
    /**
     * Muestra el formulario para registrar un despacho de combustible.
     * @return \Illuminate\View\View
     */
    public function createDespacho()
    {
        // Se obtienen todos los depósitos, clientes y vehículos para los dropdowns.
        $depositos = Deposito::all();
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $vehiculos = Vehiculo::all();
         // Obtener los vehículos tipo cisterna (asumiendo que tipo = 2)
        $cisternas = Vehiculo::where('tipo', 2)->get();
        $hoy = now()->format('Y-m-d\TH:i'); // Obtiene la fecha y hora actual en formato YYYY-MM-DD HH:MM:SS
        
        return view('combustible.despacho', compact('depositos', 'clientes', 'vehiculos','cisternas', 'hoy'));
    }

    /**
     * Almacena un nuevo despacho de combustible en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDespacho(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'deposito_id' => 'required|exists:depositos,id',
            'cliente_id' => 'nullable|exists:clientes,id', // Opcional si el despacho es a un vehículo interno
            'vehiculo_id' => 'nullable|exists:vehiculos,id',
            'cantidad_litros' => 'required|numeric|min:1',
            'observaciones' => 'nullable|string'
        ]);
        $userId = auth()->id(); // Obtener el ID del usuario autenticado

        try {
            $deposito = Deposito::findOrFail($request->deposito_id);

            // Verificación de que hay suficiente combustible
            if ($deposito->nivel_actual_litros < $request->cantidad_litros) {
                Session::flash('error', 'No hay suficiente combustible en el depósito. Nivel actual: ' . $deposito->nivel_actual_litros . ' L.');
                return Redirect::back()->withInput();
            }
            $inicial=$deposito->nivel_actual_litros;
            $final=$deposito->nivel_actual_litros-$request->cantidad_litros;
            $cliente=Cliente::find($request->cliente_id);
            $vehiculo=Vehiculo::find($request->vehiculo_id);
            $texto='Carga a ';
            if($cliente){
                $texto.=$cliente->nombre.' ';
            }
            if($vehiculo){
                $texto.='unidad '.$vehiculo->flota.' ';
            }

            if($request->cisterna_id){
                $cisterna=Vehiculo::find($request->cisterna_id);
            
                $texto.='unidad '.$cisterna->flota.' ';
            }

            if($request->nombre_despacho){
                $texto.=' '.$request->nombre_despacho.' ';
            }
            // Crear el registro del movimiento
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = $request->fecha;
            $movimiento->tipo_movimiento = 'salida';
            $movimiento->nro_ticket = $request->nro_ticket;
            $movimiento->deposito_id = $request->deposito_id;
            $movimiento->cliente_id = $request->cliente_id;
            $movimiento->vehiculo_id = $request->vehiculo_id;
            $movimiento->cantidad_litros = $request->cantidad_litros;
            $movimiento->observaciones = $texto.$request->observaciones;
            $movimiento->cant_inicial=$inicial;
            $movimiento->cant_final=$final;
            $movimiento->save();

            // Actualizar el nivel actual del depósito
            $deposito->nivel_actual_litros -= $request->cantidad_litros;
            $deposito->save();
// Generar alertas si es necesario
            if ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.1) {
                $this->createAlert([
                    'id_usuario' => $userId, // ID del usuario responsable de la orden.
                    'id_rel' => $deposito->id, // ID de la item.
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es crítico: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id) , // Ruta para ver la orden.
                    'dias' => 0,
                ]);
               
            } elseif ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.25) {
               $this->createAlert([
                    'id_usuario' => $userId, // ID del usuario responsable de la orden.
                    'id_rel' => $deposito->id, // ID de la item.
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es bajo: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id) , // Ruta para ver la orden.
                    'dias' => 0,
                ]);
                
            }   

            Session::flash('success', 'Despacho de combustible registrado exitosamente.');
            return Redirect::route('combustible.despacho');

        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Depósito, cliente o vehículo no encontrado.');
            Log::error('Error al registrar despacho: ' . $e->getMessage());
            return Redirect::back()->withInput();
        } catch (\Exception $e) {
            Session::flash('error', 'Hubo un error al procesar el despacho.');
            Log::error('Error al registrar despacho: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }


    public function updateTicket(Request $request)
{
    try {
        $mov = MovimientoCombustible::findOrFail($request->id);
        $mov->nro_ticket = strtoupper($request->nro_ticket);
        $mov->save();

        return response()->json(['ok' => true]);
    } catch (\Exception $e) {
        return response()->json(['ok' => false], 500);
    }
}

     public function list()
    {
        // Obtiene todos los movimientos, ordenados por fecha de creación (más recientes primero)
        // y con las relaciones de depósito, cliente, etc. precargadas.
        $movimientos = MovimientoCombustible::with(['deposito', 'cliente', 'proveedor', 'cisterna', 'vehiculo'])
                                            ->orderBy('created_at', 'desc')
                                            ->get();

        return view('combustible.list', ['movimientos' => $movimientos]);
    }



    // En MovimientoCombustibleController.php

public function storeDespachoIndustrial(Request $request) 
{
    // Validamos que si no selecciona vehículo, debe proveer los datos para el nuevo
    $request->validate([
        'cliente_id' => 'required|exists:clientes,id',
        'cantidad_litros' => 'required|numeric|min:1',
        'vehiculo_id' => 'required_without_all:nueva_placa,nuevo_modelo',
        'fecha' => 'required|date'
    ]);

    return DB::transaction(function () use ($request) {
        $user = Auth::user(); 
        $tanque00 = Deposito::findOrFail(3); // ID 3 según tu instrucción
        $cantidad = $request->cantidad_litros;
        $token = "8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po"; // Tu token de logística

        if ($tanque00->nivel_actual_litros < $cantidad) {
            throw new \Exception("Stock insuficiente en Tanque 00. Disponible: {$tanque00->nivel_actual_litros} L");
        }
        $cliente = Cliente::findOrFail($request->cliente_id);

        // 1. Gestión del Vehículo (Existente o Nuevo)
        if ($request->filled('nueva_placa')) {
            $vehiculo = Vehiculo::create([
                'placa' => $request->nueva_placa,
                'flota' => $request->nuevo_modelo,
                'id_cliente' => $request->cliente_id,
                'estatus' => 1
            ]);
        } else {
            $vehiculo = Vehiculo::findOrFail($request->vehiculo_id);
        }

        // 2. Registrar Movimiento de Combustible (Salida)
        $mov = MovimientoCombustible::create([
            'tipo_movimiento' => 'salida',
            'deposito_id' => $tanque00->id,
            'cliente_id' => $request->cliente_id,
            'nro_ticket' => $request->nro_ticket,
            'vehiculo_id' => $vehiculo->id,
            'cantidad_litros' => $cantidad,
            'cant_inicial' => $tanque00->nivel_actual_litros,
            'cant_final' => $tanque00->nivel_actual_litros - $cantidad,
            'observaciones' => "Despacho Industrial: " . ($request->observaciones ?? 'Sin notas'),
            'created_at' => $request->fecha
        ]);

        // 3. Registrar en repostaje_vehiculos (Tabla operativa)
        DB::table('repostaje_vehiculos')->insert([
            'id_vehiculo' => $vehiculo->id,
            'id_tanque' => $tanque00->id,
            'id_us' => $user->id,
            'qty' => $cantidad,
            'qtya' => $tanque00->nivel_actual_litros,
            'rest' => $tanque00->nivel_actual_litros - $cantidad,
            'fecha' => $request->fecha,
            'obs' => "Despacho a vehiculo: " . ($request->observaciones ?? 'Sin notas'),
            // => $mov->id,
            'created_at' => now()
        ]);

        // 4. Actualizar Tanque
        $tanque00->decrement('nivel_actual_litros', $cantidad);
        $cliente->decrement('prepagado', $cantidad);

        $fechaFormateada = Carbon::parse($request->fecha)->format('d/m/Y h:i A');
        $ticket = "<b>🎫 TICKET DE DESPACHO INDUSTRIAL</b>\n"
                . "------------------------------------------\n"
                . "<b>📍 Origen:</b> Tanque 00 (Diesel)\n"
                . "<b>🏢 Cliente:</b> {$cliente->nombre}\n"
                . "<b>🚚 Vehículo:</b> {$vehiculo->placa} (" . ($vehiculo->alias ?? 'N/A') . ")\n"
                . "<b>💧 Cantidad:</b> " . number_format($cantidad, 2) . " Lts\n"
                . "<b>🗓️ Fecha:</b> {$fechaFormateada}\n"
                . "------------------------------------------\n"
                . "<b>✅ Despacho Autorizado</b>";

        // 3. Envío de Notificaciones
        $grupoId = "-1002935486238"; 
        
        // Enviar al grupo principal
       // $this->telegramService->sendSimpleMessage($grupoId, $ticket, $token);

        // Enviar a la persona designada (si el cliente tiene un telegram_id vinculado)
        // $usuarioCliente = User::where('id_cliente', $request->cliente_id)->whereNotNull('telegram_id')->first();
        // if ($usuarioCliente) {
        //     $this->telegramService->sendSimpleMessage($usuarioCliente->telegram_id, "🔔 <b>Notificación de Consumo:</b>\n\n" . $ticket, $token);
        // }

        // 4. Alerta de Stock Crítico (Si baja del 10% de su capacidad total)
        $capacidadTotal = $tanque00->capacidad_total_litros ?? 10000; // Asumiendo capacidad
        $porcentajeActual = ($tanque00->nivel_actual_litros / $capacidadTotal) * 100;

        if ($porcentajeActual < 10) {
            $alerta = "⚠️ <b>ALERTA DE INVENTARIO CRÍTICO</b>\n"
                    . "El <b>Tanque 00</b> ha bajado del 10%.\n"
                    . "<b>Nivel Actual:</b> " . number_format($tanque00->nivel_actual_litros, 2) . " Lts.";
            //$this->telegramService->sendSimpleMessage($grupoId, $alerta, $token);
        }
        return redirect()->back()->with('success', 'Ticket enviado y despacho registrado.');
        });
}

  public function storeTraspaso(Request $request)
{
    // 1. Validar entrada
    $request->validate(['cantidad' => 'required|numeric|min:0.01']);

    return DB::transaction(function () use ($request) {
        $t3 = Deposito::where('serial', '3')->first();
        $t00 = Deposito::find(3);

        if ($t3->nivel_actual_litros < $request->cantidad) {
            return back()->with('error', 'Stock insuficiente en Tanque 3');
        }

        // 2. Registrar Salida T3
        MovimientoCombustible::create([
            'deposito_id' => $t3->id,
            'tipo_movimiento' => 'salida',
            'cantidad_litros' => $request->cantidad,
            'created_at' => $request->fechaT,
            'cant_inicial' => $t3->nivel_actual_litros,
            'cant_final' => $t3->nivel_actual_litros - $request->cantidad,
            'observaciones' => 'TRASPASO INTERNO -> T00: ' . $request->observaciones
        ]);
        $t3->decrement('nivel_actual_litros', $request->cantidad);

        // 3. Registrar Entrada T00
        MovimientoCombustible::create([
            'deposito_id' => $t00->id,
            'tipo_movimiento' => 'entrada',
            'created_at' => $request->fechaT,
            'cantidad_litros' => $request->cantidad,
            'cant_inicial' => $t00->nivel_actual_litros,
            'cant_final' => $t00->nivel_actual_litros + $request->cantidad,
            'observaciones' => 'TRASPASO INTERNO <- T3: ' . $request->observaciones
        ]);
        $t00->increment('nivel_actual_litros', $request->cantidad);

        return back()->with('success', 'Traspaso realizado correctamente');
    });
}

    public function createPrepago()
    {
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $hoy = Carbon::now()->format('Y-m-d\TH:i');
        return view('combustible.prepago', compact('clientes', 'hoy'));
    }

    public function storePrepago(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'cantidad_litros' => 'required|numeric|min:1',
            'monto_pagado' => 'nullable|numeric',
            'referencia' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $cliente = Cliente::find($request->cliente_id);

            // Registro del movimiento contable de litros
            $mov = MovimientoCombustible::create([
                'tipo_movimiento' => 'recarga_prepago',
                'deposito_id' => 0,
                'cliente_id' => $request->cliente_id,
                'cantidad_litros' => $request->cantidad_litros,
                'cant_inicial' => $cliente->prepagado,
                'cant_final' => $cliente->prepagado + $request->cantidad_litros,
                'observaciones' => "PAGO ANTICIPADO. Ref: " . $request->referencia,
                'created_at' => $request->fecha ?? now()
            ]);

            // Si tienes una columna 'saldo_litros' en la tabla clientes, la actualizamos
            $cliente->increment('prepagado', $request->cantidad_litros);

            // Notificación a Telegram
            $token = "8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po";
            $ticket = "<b>💰 RECARGA PREPAGADA</b>\n"
                    . "--------------------------------\n"
                    . "<b>🏢 Cliente:</b> {$cliente->nombre}\n"
                    . "<b>💧 Litros Abonados:</b> " . number_format($request->cantidad_litros, 2) . " Lts\n"
                    . "<b>💳 Ref:</b> " . ($request->referencia ?? 'N/A') . "\n"
                    . "--------------------------------\n"
                    . "✅ <i>Saldo actualizado en sistema</i>";

            //$this->telegramService->sendSimpleMessage("-1002935486238", $ticket, $token);

            return redirect()->route('combustible.createDespachoIndustrial')->with('success', 'Abono de combustible registrado con éxito.');
        });
    }

    public function dashboardEstadistico(Request $request)
    {
        $view = $request->get('view', 'mes');
        $date = $request->get('date', now()->format('Y-m-d'));
        $cliente_id = $request->get('cliente_id');
        $fecha = \Carbon\Carbon::parse($date);
        $saldoInicialPeriodo = 0;
        if ($view == 'hoy') {
            $fechaInicio = $fecha->copy()->startOfDay();
            $fechaFin = $fecha->copy()->endOfDay();
            $label = $fecha->translatedFormat('d \d\e F, Y');
        } elseif ($view == 'semana') {
            // HTML5 week input devuelve "2026-W05"
            $fechaInicio = $fecha->copy()->startOfWeek();
            $fechaFin = $fecha->copy()->endOfWeek();
            $label = "Semana del " . $fechaInicio->format('d/m') . " al " . $fechaFin->format('d/m');
        } else { // mes
            $fechaInicio = $fecha->copy()->startOfMonth();
            $fechaFin = $fecha->copy()->endOfMonth();
            $label = ucfirst($fecha->translatedFormat('F Y'));
        }

        $query = MovimientoCombustible::where('tipo_movimiento', 'salida')
            ->where('deposito_id', 3) // Tanque 00
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

            // Si hay cliente seleccionado, filtramos la query base
           if ($cliente_id) {
                $query->where('cliente_id', $cliente_id);
                
                // DISTRIBUCIÓN POR UNIDADES (Para el Gráfico de Pastel)
                $distribucionUnidades = (clone $query)
                    ->select('vehiculo_id', DB::raw('SUM(cantidad_litros) as total'))
                    ->with('vehiculo:id,placa') // Asumiendo que tienes esta relación
                    ->groupBy('vehiculo_id')
                    ->get();

                    $tendenciaDetallada = (clone $query)
                    ->with(['vehiculo:id,placa'])
                    ->orderBy('created_at', 'desc')
                    ->get();

                // MÉTRICA PREDICTIVA: Días de Autonomía
                // Promedio diario del cliente en los últimos 30 días
                $consumo30d = MovimientoCombustible::where('cliente_id', $cliente_id)
                    ->where('tipo_movimiento', 'salida')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->avg('cantidad_litros') ?: 0;
                    
                $clienteSeleccionado = Cliente::find($cliente_id);
                $diasAutonomia = $consumo30d > 0 ? ($clienteSeleccionado->prepagado / $consumo30d) : 0;

                $primerMovimiento = (clone $query)
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($primerMovimiento) {
                    // El 'saldo_anterior' de ese primer registro es el punto de partida
                    $saldoInicialPeriodo = $primerMovimiento->cant_inicial;
                } else {
                    // Si no hubo movimientos en el periodo, el saldo inicial es el saldo actual del cliente
                    $saldoInicialPeriodo = $clienteSeleccionado->prepagado;
                }
            }

        // 1. Métricas Globales
        $stats = (clone $query)->select(
                DB::raw('SUM(cantidad_litros) as total_litros'),
                DB::raw('COUNT(*) as total_despachos'),
                DB::raw('AVG(cantidad_litros) as promedio_ticket')
            )->first();

        // 2. Datos para Gráfico de Pastel (Distribución por Cliente)
        $porCliente = (clone $query)->with('cliente:id,nombre')
            ->select('cliente_id', DB::raw('SUM(cantidad_litros) as total'))
            ->groupBy('cliente_id')
            ->get();

        // 3. Datos para Gráfico de Líneas (Tendencia Diaria)
        if ($view == 'hoy') {
            // Si es hoy, agrupamos por hora (ej: 08:00, 09:00...)
            $tendencia = (clone $query)->select(
                    DB::raw('DATE_FORMAT(created_at, "%H:00") as tiempo'),
                    DB::raw('SUM(cantidad_litros) as total')
                )
                ->groupBy('tiempo')
                ->orderBy('tiempo')
                ->get();
        } else {
            // Si es semana o mes, agrupamos por fecha (ej: 2026-01-27)
            $tendencia = (clone $query)->select(
                    DB::raw('DATE(created_at) as tiempo'),
                    DB::raw('SUM(cantidad_litros) as total')
                )
                ->groupBy('tiempo')
                ->orderBy('tiempo')
                ->get();
        }

        $resumenClientes = Cliente::select('id', 'nombre', 'prepagado')
        ->withCount(['movimientosCombustible as total_despachos' => function($q) {
            $q->where('tipo_movimiento', 'salida');
        }])
        ->withSum(['movimientosCombustible as total_consumido' => function($q) {
            $q->where('tipo_movimiento', 'salida');
        }], 'cantidad_litros')
        ->withAvg(['movimientosCombustible as promedio_consumo' => function($q) {
            $q->where('tipo_movimiento', 'salida');
        }], 'cantidad_litros')->where('prepagado','>',0)->orWhere('periodo','P')
        ->orderBy('total_consumido', 'desc') // Orden de mayor a menor consumo
        ->get();

      

       $clientes = Cliente::whereHas('movimientosCombustible', function($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        })
        ->orderBy('nombre', 'asc')
        ->get(['id', 'nombre']);

//         // Trae solo clientes que tienen relación con movimientos_combustible
// $clientes = Cliente::has('movimientosCombustible')
//     ->orderBy('nombre', 'asc')
//     ->get(['id', 'nombre']);
    
        // Si hay cliente, buscamos sus datos específicos (como el saldo actual)
        $clienteSeleccionado = $cliente_id ? Cliente::find($cliente_id) : null;
        if($cliente_id){
            return view('combustible.estadisticas', compact(
                'stats', 'cliente_id', 'clientes', 'clienteSeleccionado', 
                'view', 'label', 'date', 'fechaInicio', 'fechaFin', 'porCliente', 'tendencia', 
                'resumenClientes', 'distribucionUnidades', 'diasAutonomia','tendenciaDetallada','saldoInicialPeriodo'
            ));
        }
        return view('combustible.estadisticas', compact(
                'stats', 'cliente_id','clientes','clienteSeleccionado', 'view', 'fechaInicio', 'fechaFin','label', 'date', 'porCliente', 'tendencia','resumenClientes'));
    }

    public function historialDespachosIndustrial()
    {
        // Obtenemos los despachos de forma descendente (los más recientes primero)
        $historial = MovimientoCombustible::with(['cliente', 'vehiculo', 'deposito'])
            ->whereIn('deposito_id', [0,3]) // TanqInue 00
             ->whereIn('tipo_movimiento', ['salida','recarga_prepago'])
            ->orWhere('deposito_id',3)->where('observaciones','like','%traspaso%')
            ->orderBy('created_at', 'desc')->get(); // Paginación para no sobrecargar la vista
            $t3=Deposito::find(6);

        
        $hoy = Carbon::now()->format('Y-m-d\TH:i');

        return view('combustible.historial_industrial', compact('historial','t3','hoy'));
    }

    public function resumenDespachos(Request $request)
    {
        $periodo = $request->get('periodo', 'diario'); // Valor por defecto
        $query = MovimientoCombustible::query()
            ->where('deposito_id', 3)
            ->whereIn('tipo_movimiento', ['salida','recarga_prepago']);

        // Filtro dinámico de tiempo
        switch ($periodo) {
            case 'semanal':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mensual':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
            default: // diario
                $query->whereDate('created_at', now()->today());
                break;
        }

        // Agrupación por cliente con conteo y suma
        $resumen = $query->select(
                'cliente_id',
                DB::raw('SUM(cantidad_litros) as total_litros'),
                DB::raw('COUNT(*) as total_despachos'),
                DB::raw('MAX(created_at) as ultimo_despacho')
            )
            ->with('cliente:id,nombre,prepagado')
            ->groupBy('cliente_id')
            ->get();

            $chartData = [
                'categorias' => $resumen->map(fn($item) => $item->cliente->nombre ?? 'N/A')->toArray(),
                'series' => $resumen->map(fn($item) => (float)$item->total_litros)->toArray()
            ];

        return view('combustible.resumen_industrial', compact('resumen', 'periodo', 'chartData'));
    }

     /**
     * Muestra el panel de pedidos pendientes para aprobación y rechazo.
     *
     * @return \Illuminate\View\View
     */
    public function pedidos()
    {
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        
        $pedidos = Pedido::with(['cliente'])
            ->whereIn('estado', ['pendiente'])
            ->orderBy('fecha_solicitud', 'desc')    
            ->get();

        return view('combustible.pedidos', compact('pedidos', 'clientes'));
    }

    /**
     * Procesa la aprobación de un pedido.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aprobar(Request $request, $id)
    {
        try {
            $pedido = Pedido::findOrFail($id);
            $pedido->estado = 'aprobado';
            $pedido->cantidad_aprobada = $request->input('cantidad_aprobada');
            $pedido->observaciones_admin = $request->input('observaciones_admin', $pedido->observaciones_admin);
            $pedido->fecha_aprobacion = Carbon::now();
            $pedido->save();

            Session::flash('success', 'Pedido de combustible aprobado exitosamente.');

            try {
                FcmNotificationService::sendPedidoStatusNotification(
                    $pedido,
                    'pendiente',
                    'aprobado',
                    $pedido->observaciones_admin
                );
                Log::info("Notificación FCM enviada al cliente {$pedido->cliente_id} por aprobación de pedido");
            } catch (\Exception $e) {
                Log::error("Error enviando notificación FCM: " . $e->getMessage());
                // No fallar la operación principal por error en notificación
            }

        } catch (\Exception $e) {
            Session::flash('error', 'Error al aprobar el pedido: ' . $e->getMessage());
        }

        return redirect()->route('combustible.pedidos');
    }
    

    /**
     * Procesa el rechazo de un pedido.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rechazar(Request $request, $id)
    {
        try {
            $pedido = Pedido::findOrFail($id);
            $pedido->estado = 'rechazado';
            $pedido->observaciones_admin = $request->input('observaciones_admin', 'Rechazado por el administrador.');
            $pedido->save();

            Session::flash('success', 'Pedido de combustible rechazado exitosamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al rechazar el pedido: ' . $e->getMessage());
        }

        return redirect()->route('combustible.pedidos');
    }

    /**
     * Muestra el panel de pedidos aprobados listos para despacho.
     *
     * @return \Illuminate\View\View
     */
    public function despachos()
    {
        // Recuperamos los pedidos con estado 'aprobado'
        $pedidos = Pedido::with('cliente')
            ->where('estado', 'aprobado')
            ->orderBy('fecha_aprobacion', 'desc')
            ->get();
        
        // Asumimos que existen los modelos Vehiculo y Deposito
           $vehiculos = Vehiculo::where('estatus', 1)
            ->where('permiso_intt', '!=', 'S/P')
            ->whereNotNull('permiso_intt')
            ->get();
        $depositos = Deposito::all();

        return view('combustible.aprobados', compact('pedidos', 'vehiculos', 'depositos'));
    }

    public function updateMovimientoField(Request $request)
    {
        // Validamos que el campo sea uno de los permitidos para editar
        $request->validate([
            'id' => 'required|exists:movimientos_combustible,id',
            'field' => 'required|in:nro_ticket,observaciones',
            'value' => 'nullable|string'
        ]);

        try {
            $mov = MovimientoCombustible::findOrFail($request->id);
            $field = $request->field;
            
            // Aplicamos estándar de mayúsculas solo al ticket
            $mov->$field = ($field === 'nro_ticket') 
                ? strtoupper($request->value) 
                : $request->value;
                
            $mov->save();

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false], 500);
        }
    }


    public function enviarResumenTelegram(Request $request) 
    {
         $logisticaToken = '8267350827:AAGWkn8hFmqIyQmW1ojlKk-eTfXke5um1Po';
        $periodo = $request->periodo;
        $query = MovimientoCombustible::query()
            ->where('deposito_id', 3)
            ->where('tipo_movimiento', 'salida');

        // Filtro dinámico de tiempo
        switch ($periodo) {
            case 'semanal':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mensual':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
            default: // diario
                $query->whereDate('created_at', now()->today());
                break;
        }

        // Agrupación por cliente con conteo y suma
        $resumen = $query->select(
                'cliente_id',
                DB::raw('SUM(cantidad_litros) as total_litros'),
                DB::raw('COUNT(*) as total_despachos'),
                DB::raw('MAX(created_at) as ultimo_despacho')
            )
            ->with('cliente:id,nombre')
            ->groupBy('cliente_id')
            ->get();

        $mensaje = "📋 <b>RESUMEN DE CONSUMO (" . strtoupper($periodo) . ")</b>\n";
        $mensaje .= "------------------------------------------\n";
        
        foreach($resumen as $item) {
            $mensaje .= "• <b>{$item->cliente->nombre}:</b> " . number_format($item->total_litros, 2) . " L\n";
        }
        
        $mensaje .= "------------------------------------------\n";
        $mensaje .= "<b>TOTAL:</b> " . number_format($resumen->sum('total_litros'), 2) . " Litros";

        $this->telegramService->sendSimpleMessage("-1002935486238", $mensaje, $logisticaToken);

        return back()->with('success', 'Reporte enviado a Telegram correctamente.');
    }

    /**
     * Procesa el despacho de un pedido, actualizando el saldo del cliente.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function despachar(Request $request, $id)
    {
        // 1. Validar los datos de la solicitud
        $request->validate([
            'vehiculo_id' => 'required|exists:vehiculos,id',    
            'deposito_id' => 'required|exists:depositos,id',
        ]);
        
        $userId = Auth::id(); // Usar el Facade Auth para mayor claridad

        try {
            DB::beginTransaction();

            // 2. Obtener todos los modelos necesarios
            $pedido = Pedido::with('cliente')->findOrFail($id);
            $cliente = $pedido->cliente;
            $cantidadDespachar = $pedido->cantidad_aprobada; 
            $vehiculo = Vehiculo::findOrFail($request->input('vehiculo_id'));
            $deposito = Deposito::findOrFail($request->input('deposito_id'));
            
            // 3. Realizar las verificaciones de negocio
            if ($cliente->disponible < $cantidadDespachar) {
                Session::flash('error', 'El cliente no tiene suficiente combustible disponible para este despacho.');
                DB::rollBack();
                return redirect()->back();
            }

            if ($deposito->nivel_actual_litros < $cantidadDespachar) {
                Session::flash('error', 'No hay suficiente combustible en el depósito para completar este despacho.');
                DB::rollBack();
                return redirect()->back();
            }

            // 4. Actualizar los modelos en memoria
            // Actualizar el estado del pedido a 'en_proceso'
            $pedido->estado = 'en_proceso';
            $pedido->vehiculo_id = $vehiculo->id;
            $pedido->deposito_id = $deposito->id;
            $pedido->fecha_completado = Carbon::now();

            // Actualizar el estatus del vehículo
            $vehiculo->estatus = 2;

            // Actualizar el saldo del cliente
           // $cliente->disponible -= $cantidadDespachar;

            // Actualizar el nivel actual del depósito
            $deposito->nivel_actual_litros -= $cantidadDespachar;

            // 5. Crear el registro del movimiento de despacho en memoria
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = Carbon::now();
            $movimiento->tipo_movimiento = 'salida';
            $movimiento->deposito_id = $deposito->id; // Corregido: usar $deposito->id
            $movimiento->cliente_id = $pedido->cliente_id;
            $movimiento->cisterna_id = $vehiculo->id;
            $movimiento->cantidad_litros = $cantidadDespachar;
            $movimiento->observaciones = 'Despacho de pedido ID: ' . $pedido->id;

            // 6. Guardar todos los modelos y el movimiento de manera atómica
            $pedido->save();
            $vehiculo->save();
            //$cliente->save();
            $deposito->save();
            $movimiento->save();


            // 7. Generar alertas si es necesario
            if ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.1) {
                $this->createAlert([
                    'id_usuario' => $userId,
                    'id_rel' => $deposito->id,
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es crítico: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id),
                    'dias' => 0,
                ]);
            } elseif ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.25) {
                $this->createAlert([
                    'id_usuario' => $userId,
                    'id_rel' => $deposito->id,
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es bajo: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id),
                    'dias' => 0,
                ]);
            }

            try {
                FcmNotificationService::sendPedidoStatusNotification(
                    $pedido,
                    'aprobado',
                    'en_proceso',
                    $pedido->observaciones_admin
                );
                Log::info("Notificación FCM enviada al cliente {$pedido->cliente_id} por aprobación de pedido");
            } catch (\Exception $e) {
                Log::error("Error enviando notificación FCM: " . $e->getMessage());
                // No fallar la operación principal por error en notificación
            }

            if(($cliente->disponible - $cantidadDespachar)<($cliente->cupo*0.1)){
            try {
                FcmNotificationService::sendCustomNotification(
                    $pedido,
                    $cliente, 
                    'Baja Disponibilidad', 
                    'Estimado cliente su disponibilidad actual es de '.($cliente->disponible - $cantidadDespachar).' Litros de su cupo de '.$cliente->cupo.' se recomienda tomar previsiones'
                );
                Log::info("Notificación FCM enviada al cliente {$pedido->cliente_id} por aprobación de pedido");
            } catch (\Exception $e) {
                Log::error("Error enviando notificación FCM: " . $e->getMessage());
                // No fallar la operación principal por error en notificación
            }


            }

            DB::commit();
            Session::flash('success', 'Despacho realizado y saldo del cliente actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al despachar el combustible: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Session::flash('error', 'Error al despachar el combustible. Por favor, revisa los logs de la aplicación.');
        }

        return redirect()->route('combustible.aprobados');
    }

    /**
     * Muestra el formulario para crear una nueva solicitud.
     */
    public function createCompra()
    {
        // Data de prueba o real para los selectores
        $proveedores = Proveedor::all(['id', 'nombre']);
        $plantas = Planta::orderBy('nombre', 'asc') // O 'desc'
            ->get(['id', 'nombre', 'alias']);  
        $choferes = Chofer::whereNotNull('documento_vialidad_numero')   
                                      ->where('cargo', 'CHOFER')
                                      ->with('persona')
                                      ->get();

        $ayudantes = Chofer::whereNull('documento_vialidad_numero')   
                                      ->with('persona')
                                      ->get();

        $vehiculos = Vehiculo::where('es_flota', 1)->whereIn('tipo', [3,2])->get();
        

        return view('combustible.compra', compact('proveedores', 'plantas', 'choferes','vehiculos','ayudantes'));
    }


        public function createFlete()
    {
        // Data de prueba o real para los selectores
        $proveedores = Proveedor::all(['id', 'nombre']);
        $prigen = Planta::orderBy('nombre', 'asc') // O 'desc'
            ->get(['id', 'nombre', 'alias'])
            ->toArray(); 

        $clientes = Cliente::orderBy('nombre', 'asc') // O 'alias', lo que prefieras
            ->get(['id', 'nombre', 'alias'])
            ->toArray();
        $plantas = array_merge($prigen, $clientes );
        $choferes = Chofer:://whereNotNull('documento_vialidad_numero')->   
                                      where('cargo', 'CHOFER')
                                      ->with('persona')
                                      ->get();
        $destino = TabuladorViatico::where('id','>',5)->orderBy('destino','asc')->pluck('destino')->unique();

        $ayudantes = Chofer:://whereNull('documento_vialidad_numero')-> 
                            where('cargo', 'like','%AYUDANTE%')->with('persona')->get();
        
        $vehiculos = Vehiculo::where('es_flota', 1)->whereIn('tipo', [3,2])->get();
        

        return view('combustible.flete', compact('proveedores', 'plantas', 'choferes','vehiculos','ayudantes','destino'));
    }


    /**
     * Almacena la solicitud, realiza la planificación y notifica.
     */
    public function storeCompra(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            //'proveedor_id' => 'required|exists:proveedores,id',
            'litros' => 'required|integer|min:100',
            'planta_destino_id' => 'required|exists:plantas,id',
            'fecha' => 'required|date|after_or_equal:today',
            //'vehiculo_id' => 'required|exists:vehiculos,id',
            //'chofer_id' => 'required|exists:choferes,id',
            //'ayudante' => 'nullable|exists:chofere,id'
        ]);
        $flete=$request->flete ?? false;
        DB::beginTransaction();
        try {
            // 1. CREAR LA SOLICITUD DE COMBUSTIBLE
            $solicitud = CompraCombustible::create([
                'proveedor_id' => 1,
                'cantidad_litros' => $request->litros,
                'planta_destino_id' => $request->planta_destino_id,
                'fecha' => $request->fecha,
                'estatus' => 'PENDIENTE_ASIGNACION',
                'tipo' => $request->tipo,
                'flete' => $flete,
                'vehiculo_id' => $request->vehiculo_id,
                'cisterna' => $request->cisterna_id,
                'observaciones' => $request->observaciones,
                'otro_vehiculo' => $request->otro_vehiculo ?? null,
                'otro_chofer' => $request->otro_chofer ?? null,
                'otro_ayudante' => $request->otro_ayudante ?? null,
                //'usuario_solicitante_id' => Auth::id(),
            ]);

 
            // 2. PLANIFICACIÓN Y ASIGNACIÓN DE RECURSOS
            $planta = Planta::find($request->planta_destino_id);
            $destino = TabuladorViatico::find($planta->id_tabulador_viatico);
            //dd($destino);
            $cantidad = $request->litros;
            $fecha = $solicitud->fecha;


            // 3. CREAR LA PLANIFICACIÓN (Viaje)
            $viaje = Viaje::create([
                'solicitud_combustible_id' => $solicitud->id,
                'vehiculo_id' => $request->vehiculo_id,
                'chofer_id' => $request->chofer_id,
                'ayudante' => $request->ayudante ?? null, // Ayudante es opcional
                'destino_ciudad' => $destino->destino ?? 'N/A', 
                'fecha_salida' => $fecha,
                'litros' =>$request->litros,
                'otro_vehiculo' => $request->otro_vehiculo ?? null,
                'otro_chofer' => $request->otro_chofer ?? null,
                'otro_ayudante' => $request->otro_ayudante ?? null,
                'status' => 'Programado',
                'obervaciones' => null,
                'usuario_id' => $userId
                
            ]);
                $chofer=Chofer::find($request->chofer_id);
                $ayudante=Chofer::find($request->ayudante);
            
           // dd($viaje);
           switch ($request->tipo) {
                case 'INDUSTRIAL':
                    $tipoProducto = 2;
                    break;
                case 'M.G.O.':
                    $tipoProducto = 1;
                    break;
                default:
                    $tipoProducto = 2; // Valor por defecto
            }
            $producto=Producto::find($tipoProducto);
            $producto->stock+=$request->litros;
            $producto->save();

             DespachoViaje::create([
                    'viaje_id' => $viaje->id,
                    'otro_cliente' => 'PDVSA '.$destino->destino,
                    'litros' => $request->litros
                ]);

            // 4. ACTUALIZAR LA SOLICITUD
            $solicitud->update([
                'estatus' => 'PROGRAMADO',
                'viaje_id' => $viaje->id,
            ]);

            $tabulador = TabuladorViatico::where('destino', $destino->id)->first();

            if(!is_null($tabulador)){ $this->generarCuadroViaticos($viaje, $tabulador, 1); }

            DB::commit();

            // 5. NOTIFICACIÓN DE PLANIFICACIÓN EXITOSA
            $this->enviarNotificaciones($viaje, $solicitud, $chofer,$ayudante);
           // dd('finalizado OK');
            return redirect()->route('viajes.resumenProgramacion',$viaje->id)->with('success', 'Solicitud de combustible creada y viaje de carga planificado y asignado con éxito (ID Viaje: ' . $viaje->id . ').');
            //return redirect()->route('combustible.compras')->with('success', 'Solicitud de combustible creada y viaje de carga planificado y asignado con éxito (ID Viaje: ' . $viaje->id . ').');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en el flujo de Compra/Planificación de Combustible: " . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error en el sistema al procesar la solicitud.');
        }
    }


    public function storeFlete(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            //'proveedor_id' => 'required|exists:proveedores,id',
            'cantidad_litros' => 'required|integer|min:100',
            //'planta_destino_id' => 'required|exists:plantas,id',
            'fecha_salida' => 'required|date|after_or_equal:today',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'chofer_id' => 'required|exists:choferes,id',
            //'ayudante' => 'nullable|exists:chofere,id'
        ]);
        DB::beginTransaction();
        try {
            // 1. CREAR LA SOLICITUD DE COMBUSTIBLE
            // $solicitud = CompraCombustible::create([
            //     'proveedor_id' => 1,
            //     'cantidad_litros' => $request->cantidad_litros,
            //     'planta_destino_id' => $request->planta_destino_id,
            //     'fecha' => $request->fecha_salida,
            //     'estatus' => 'PENDIENTE_ASIGNACION',
            //     'tipo' => $request->tipo ?? 'INDUSTRIAL',
            //     'vehiculo_id' => $request->vehiculo_id,
            //     //'observaciones' => $request->observaciones
            //     //'usuario_solicitante_id' => Auth::id(),
            // ]);

            // 2. PLANIFICACIÓN Y ASIGNACIÓN DE RECURSOS
            //$planta = Planta::find($request->planta_destino_id);
            //$plantaDestino = TabuladorViatico::find($planta->id_tabulador_viatico);

            //dd($destino);
            $vehiculo = Vehiculo::find($request->vehiculo_id);
            $chofer = Chofer::find($request->chofer_id);
            $ayudante = null;
            if(!empty($request->ayudante)){
                $ayudante = Chofer::find($request->ayudante);
            }


            // 3. CREAR LA PLANIFICACIÓN (Viaje)
            $viaje = Viaje::create([
               // 'solicitud_combustible_id' => $solicitud->id,
                'vehiculo_id' => $request->vehiculo_id,
                'chofer_id' => $request->chofer_id,
                'ayudante' => $request->ayudante ?? null, // Ayudante es opcional
                'destino_ciudad' => 'FLETE -> '. $request->planta_destino_id.' -> '.$request->destino_ciudad ?? 'FLETE N/A', 
                'fecha_salida' => $request->fecha_salida,
                'status' => 'Programado',
                'usuario_id' => $userId
                
            ]);


           foreach ($request->despachos as $index => $despacho) {
                if (empty($despacho['cliente']) && empty($despacho['otro_cliente'])) {
                    return back()->withInput()->withErrors([
                        "despachos.$index.cliente_id" => 'Debe seleccionar un cliente o especificar "Otro Cliente".',
                        "despachos.$index.otro_cliente" => 'Debe seleccionar un cliente o especificar "Otro Cliente".',
                    ]);
                }
            }

            // 2. Buscar tarifa en el Tabulador para el destino principal
            $tabulador = TabuladorViatico::where('destino', $request->destino_ciudad)->first();

            if (!$tabulador) {
             //   return back()->withInput()->with('error', 'No se encontró una tarifa de viáticos para esa ciudad.');
            }

            $cantidadDespachos = count($request->despachos);
            $totalLitros = 0;
            foreach ($request->despachos as $despachoData) {
                DespachoViaje::create([
                    'viaje_id' => $viaje->id,
                    'otro_cliente' => $despachoData['otro_cliente'] ?? null,
                    'litros' => $despachoData['litros'],
                ]);
                $totalLitros += $despachoData['litros'];
            }

            $this->generarCuadroViaticos($viaje, $tabulador, 1);
            DB::commit();

            // 5. NOTIFICACIÓN DE PLANIFICACIÓN EXITOSA
            
        $mensaje = "✅ Planificación de Flete de Combustible CREADA:\n"
                 . "Carga: {$viaje->cantidad_litros} Litros\n"
                 . "Ruta: PDVSA {$viaje->destino_ciudad}\n"
                 . "Fecha: {$viaje->fecha_salida}\n"
                 . "Unidad Asignada: {$vehiculo->flota}\n"
                 . "Chofer: {$chofer->persona()->first()->nombre }\n"
                 . ($ayudante ? "Ayudante: {$ayudante->persona()->first()->nombre }" : "Ayudante: No Asignado");

        // 1. Notificación a Telegram (Ejemplo de Alerta General)
            // El servicio TelegramNotificationService debe tener un método como sendNotification
            $this->telegramService->sendMessage($mensaje);
        
            return redirect()->route('viajes.list')->with('success', 'Solicitud de Flete creada y viaje de carga planificado y asignado con éxito (ID Viaje: ' . $viaje->id . ').');
            //return redirect()->route('combustible.compras')->with('success', 'Solicitud de combustible creada y viaje de carga planificado y asignado con éxito (ID Viaje: ' . $viaje->id . ').');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en el flujo de Compra/Planificación de Combustible: " . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error en el sistema al procesar la solicitud.');
        }
    }

    /**
     * Función para enviar notificaciones a los involucrados.
     * @param Viaje $viaje
     * @param SolicitudCombustible $solicitud
     * @param Chofer $chofer
     * @param Ayudante|null $ayudante
     */
    protected function enviarNotificaciones(Viaje $viaje, CompraCombustible $solicitud, ?Chofer $chofer, ?Chofer $ayudante): void
    {
            $chofer=null;
            $choferU=null;
            if(!is_null($chofer)){
                $choferP = Persona::find($chofer->persona_id) ?? null;
                $choferU=User::find($chofer->user_id) ??null;
                $chofer=$choferP->nombre;

            }else{
                $chofer=$viaje->otro_chofer;

            }
            $ayudanteP = null;
            $ayudanteU=null;
            if ($ayudante) {
                $ayudanteP = Persona::find($ayudante->persona_id)??null;
                $ayudanteU=User::find($ayudante->user_id)??null;
                $ayudante=$ayudanteP->nombre;
            }else{
                $ayudante=$viaje->otro_ayudante;
            }

        $vehiculo=Vehiculo::find($viaje->vehiculo_id)??null;
        if($vehiculo){
            $vehiculo=$vehiculo->flota;
        }else{
            $vehiculo=$viaje->otro_vehiculo;
        }
        $fecha=date('d/m/Y',strtotime($viaje->fecha_salida));

        $mensaje = "✅ Planificación de Carga de Combustible CREADA:\n"
                 . "Carga: {$solicitud->cantidad_litros} Litros\n"
                 . "Ruta: PDVSA {$viaje->destino_ciudad}\n"
                 . "Fecha: {$fecha}\n"
                 . "Unidad Asignada: {$vehiculo}\n"
                 . "Chofer: {$chofer}\n"
                 . ($ayudante ? "Ayudante: {$ayudante }" : "Ayudante: No Asignado")
                 . "\n\n{$solicitud->observaciones}";

        // 1. Notificación a Telegram (Ejemplo de Alerta General)
        try {
            // El servicio TelegramNotificationService debe tener un método como sendNotification
            $this->telegramService->sendMessage($mensaje);
        } catch (\Exception $e) {
            Log::error("Error enviando notificación a Telegram: " . $e->getMessage());
        }

        // 2. Notificación FCM (Alertas y fcmNotification)
        // Podrías enviar la notificación al token del chofer y a los usuarios de logística
         try {
            $logisticaTokens=User::whereIn('perfil_id', [1,2,6,7,8,11,12,18] )->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
             $tokens = [];
             // Asume que el modelo Chofer tiene el token_fcm relacionado con su usuario
             if ($choferU && $choferU->token_fcm) {
                 $tokens[] = $choferU->token_fcm;
             }
             if ($ayudanteU && $ayudanteU->token_fcm) {
                 $tokens[] = $ayudanteU->token_fcm;
             }  
             // Tokens de usuarios de logística/administración
             $tokens = array_merge($tokens, $logisticaTokens);
            if (!empty($tokens)) {
                  $this->fcmService->sendNotification(
                     $tokens, 
                     "Carga de Combustible Planificada (ID Viaje: {$viaje->id})", 
                     "{$chofer->persona->nombre} Tienes asignada una carga de {$solicitud->cantidad_litros} para el {$viaje->fecha_salida}."
                 );
             }
         } catch (\Exception $e) {
             Log::error("Error enviando notificación FCM: " . $e->getMessage());
        }

        // 3. (Opcional) Implementación de Alertas Web
        // Esto se manejaría generalmente con Eventos de Laravel y un listener de Broadcast.
        // Alert::create(['mensaje' => "Nueva Planificación de Combustible: ID {$viaje->id}", 'tipo' => 'info']);
    }

    private function generarCuadroViaticos(Viaje $viaje, TabuladorViatico $tabulador,$cantidadDespachos): void
    {
        $fecha_salida = $viaje->fecha_salida;
        $viatico=false;
        $totalPersonas = 1  + $viaje->custodia_count;
        $parametros = Parametro::all()->keyBy('nombre')
            ->map(function($item) {
                return $item->valor;
            });
            //dd($parametros);
            // Lista de conceptos a generar (usando el Tabulador)
        $conceptos = [
            // Pagos Fijos
            ['concepto' => 'Pago Chofer', 'monto' => $tabulador->pago_chofer * $cantidadDespachos, 'cantidad' => $cantidadDespachos, 'editable' => false],
            ['concepto' => 'Pago Ayudantes', 'monto' => $tabulador->pago_ayudante * $cantidadDespachos, 'cantidad' => $cantidadDespachos, 'editable' => false],
        ];      
            // Viáticos de Comida (por persona, por día)
            if($tabulador->viatico_desayuno > 0 ){
                $viatico=true;
                $conceptos[] = ['concepto' => 'Viático Desayuno', 'monto' => $tabulador->viatico_desayuno , 'cantidad' => $totalPersonas, 'editable' => true];
            }
            if($tabulador->viatico_almuerzo > 0 ){
                $viatico=true;
                $conceptos[] = ['concepto' => 'Viático Almuerzo', 'monto' => $tabulador->viatico_almuerzo , 'cantidad' => $totalPersonas, 'editable' => true];
            }
            if($tabulador->viatico_cena > 0 ){
                $viatico=true;
                $conceptos[] = ['concepto' => 'Viático Cena', 'monto' => $tabulador->viatico_cena, 'cantidad' => $totalPersonas, 'editable' => true];
            }
            // Pernocta y Peajes
            if($tabulador->costo_pernocta > 0 ){
                $viatico=true;
                $conceptos[] =['concepto' => 'Costo Pernocta', 'monto' => $tabulador->costo_pernocta, 'cantidad' => $totalPersonas, 'editable' => true];
            }
            if($tabulador->peajes > 0 ) {
                $conceptos[] =['concepto' => 'Peajes (Ida y Vuelta)', 'monto' => $tabulador->peajes * $parametros['peaje'] , 'cantidad' => 1, 'editable' => true];
            }
            if($viatico==true){
                $viaje->has_viatico=true;
                $viaje->save();
            };
        // Guardar cada concepto en la tabla 'viaticos_viaje'
        foreach ($conceptos as $item) {
            if ($item['cantidad'] > 0) {
                ViaticoViaje::create([
                    'viaje_id' => $viaje->id,
                    'concepto' => $item['concepto'],
                    'monto_base' => $item['monto'],
                    'cantidad' => $item['cantidad'],
                    'es_editable' => $item['editable'],
                ]);
            }
        }
    }

}
