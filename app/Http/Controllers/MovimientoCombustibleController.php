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
                                ->get();
        $clientes = Cliente::all();

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
            'resguardo'
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
        $vehiculos_cisterna = Vehiculo::all();//where('es_cisterna', 1)->get();
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
        // Se obtienen todos los depósitos y proveedores para los dropdowns del formulario.
        $depositos = Deposito::all();
        $proveedores = Proveedor::all();
        $hoy = now()->format('Y-m-d\TH:i'); // Obtiene la fecha actual en formato YYYY-MM-DD
        
        return view('combustible.recarga', compact('depositos', 'proveedores', 'hoy'));
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
        $clientes = Cliente::all();
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
     public function list()
    {
        // Obtiene todos los movimientos, ordenados por fecha de creación (más recientes primero)
        // y con las relaciones de depósito, cliente, etc. precargadas.
        $movimientos = MovimientoCombustible::with(['deposito', 'cliente', 'proveedor', 'cisterna', 'vehiculo'])
                                            ->orderBy('created_at', 'desc')
                                            ->get();

        return view('combustible.list', ['movimientos' => $movimientos]);
    }

     /**
     * Muestra el panel de pedidos pendientes para aprobación y rechazo.
     *
     * @return \Illuminate\View\View
     */
    public function pedidos()
    {
        $clientes = Cliente::all();
        
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
        $plantas = Planta::all(['id', 'nombre', 'alias']); 
        $choferes = Chofer::whereNotNull('documento_vialidad_numero')   
                                      ->where('cargo', 'CHOFER')
                                      ->with('persona')
                                      ->get();

        $ayudantes = Chofer::whereNull('documento_vialidad_numero')   
                                      ->with('persona')
                                      ->get();

        $vehiculos = Vehiculo::where('es_flota', 1)->whereIn('tipo', [3,2])->whereIn('estatus', [1,2])->get();
        

        return view('combustible.compra', compact('proveedores', 'plantas', 'choferes','vehiculos','ayudantes'));
    }


        public function createFlete()
    {
        // Data de prueba o real para los selectores
        $proveedores = Proveedor::all(['id', 'nombre']);
        $plantas = Planta::all(['id', 'nombre', 'alias']); 
        $choferes = Chofer::whereNotNull('documento_vialidad_numero')   
                                      ->where('cargo', 'CHOFER')
                                      ->with('persona')
                                      ->get();
        $destino = TabuladorViatico::where('id','>',5)->pluck('destino')->unique();

        $ayudantes = Chofer::whereNull('documento_vialidad_numero')   
                                      ->with('persona')
                                      ->get();

        $vehiculos = Vehiculo::where('es_flota', 1)->whereIn('tipo', [3,2])->whereIn('estatus', [1,2])->get();
        

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
                'flete' => $request->es_flete,
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
            $cantidad = $request->cantidad_litros;
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
                'usuario_id' => $userId
                
            ]);
           dd($viaje);
            if(is_null($request->otro_chofer)){
                $chofer=Chofer::find($request->chofer_id);
                $ayudante=Chofer::find($request->ayudante);
            }else{
                $chofer=$request->otro_chofer;
                $ayudante=$request->otro_ayudante;
            }
           // dd($viaje);

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


            DB::commit();

            // 5. NOTIFICACIÓN DE PLANIFICACIÓN EXITOSA
            $this->enviarNotificaciones($viaje, $solicitud, $chofer,$ayudante);

            return redirect()->route('viajes.list')->with('success', 'Solicitud de combustible creada y viaje de carga planificado y asignado con éxito (ID Viaje: ' . $viaje->id . ').');
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
            'planta_destino_id' => 'required|exists:plantas,id',
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
            $planta = Planta::find($request->planta_destino_id);
            $plantaDestino = TabuladorViatico::find($planta->id_tabulador_viatico);

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
                'destino_ciudad' => $plantaDestino->destino.' -> '.$request->destino_ciudad ?? 'N/A', 
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
                return back()->withInput()->with('error', 'No se encontró una tarifa de viáticos para esa ciudad.');
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


            DB::commit();

            // 5. NOTIFICACIÓN DE PLANIFICACIÓN EXITOSA
            
        $mensaje = "✅ Planificación de Flete de Combustible CREADA:\n"
                 . "Carga: {$viaje->cantidad_litros} Litros\n"
                 . "Ruta: PDVSA {$viaje->destino_ciudad}\n"
                 . "Fecha: {$viaje->fecha_salida}\n"
                 . "Unidad Asignada: {$vehiculo->flota}\n"
                 . "Chofer: {$chofer->persona()->first()->nombre }\n"
                 . ($ayudante ? "Ayudante: {$ayudante->persona()->firts()->nombre }" : "Ayudante: No Asignado");

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
    protected function enviarNotificaciones(Viaje $viaje, CompraCombustible $solicitud, Chofer $chofer, ?Chofer $ayudante): void
    {
        if(isset($chofer->id)){
            $choferP = Persona::find($chofer->persona_id);
            $choferU=User::find($chofer->user_id);
            $ayudanteP = null;
            $ayudanteU=null;
            if ($ayudante) {
                $ayudanteP = Persona::find($ayudante->persona_id);
                $ayudanteU=User::find($ayudante->user_id);
            }
        }else{

        }
        $vehiculo=Vehiculo::find($viaje->vehiculo_id);
        $vehiculo=$vehiculo->flota ?? $viaje->otro_vehiculo;
        $chofer=$choferP->nombre??$chofer;
        $ayudante=$ayudanteP->nombre??$ayudante;

        $mensaje = "✅ Planificación de Carga de Combustible CREADA:\n"
                 . "Carga: {$solicitud->cantidad_litros} Litros\n"
                 . "Ruta: PDVSA {$viaje->destino_ciudad}\n"
                 . "Fecha: {$viaje->fecha_salida}\n"
                 . "Unidad Asignada: {$vehiculo}\n"
                 . "Chofer: { $chofer}\n"
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
}
