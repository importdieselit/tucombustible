<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\Vehiculo; 
use App\Models\Personal; 
use App\Services\FcmNotificationService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\TipoOrden; 
use App\Models\TipoFalla;   
use App\Models\EstatusData; 
use Carbon\Carbon; 
use Illuminate\Support\Facades\Auth;
use App\Models\InventarioSuministro; 
use App\Traits\GenerateAlerts;
use Illuminate\Support\Facades\Redirect;
use App\Models\Inventario; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\SuministroCompra;
use App\Models\SuministroCompraDetalle;
use App\Notifications\OrdenTrabajoCreada;
use App\Services\TelegramNotificationService;
use App\Models\MantenimientoProgramado;
use App\Models\OrdenFoto;
use App\Models\User;
use Google\Service\Datastore\Sum;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TemparioCategoria;
use App\Models\Trabajos;
use App\Models\TemparioServicio;
use App\Models\TipoRequerimiento;
use Illuminate\Support\Facades\DB;
use App\Models\Proveedor;
use App\Models\TrabajoExterno;
use App\Models\PlanMantenimiento;


class OrdenController extends BaseController
{

    use GenerateAlerts;

    protected $fcmService;
    protected $telegramService;

    public function __construct(
        FcmNotificationService $fcmService, 
        TelegramNotificationService $telegramService, Orden $orden
    ) {
        $this->fcmService = $fcmService;
        $this->telegramService = $telegramService;
        $this->model = $orden;
    }


    /**
     * Muestra el dashboard de órdenes de trabajo.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // --- Datos de prueba para el Dashboard ---

        // Simulación de reportes de falla (timeline)
        $reportes_falla = [
            // (object)['fecha' => '2024-05-15', 'descripcion' => 'Falla en el sistema de frenos del Vehículo 003.'],
            // (object)['fecha' => '2024-05-12', 'descripcion' => 'Motor sobrecalentado en el Vehículo 005.'],
            // (object)['fecha' => '2024-05-10', 'descripcion' => 'Problema eléctrico en luces delanteras del Vehículo 012.'],
        ];
        $ordenes_abiertas = Orden::where('estatus', 2)->count();

        // Simulación de próximos mantenimientos programados
        $mantenimientos_proximos = MantenimientoProgramado::where('fecha', '>=', Carbon::now())->get()->map(function($mantenimiento) {
            return (object)[
                'vehiculo' => $mantenimiento->vehiculo ? $mantenimiento->vehiculo->flota . " ({$mantenimiento->vehiculo->placa})" : 'N/A',
                'tipo_mantenimiento' => $mantenimiento->tipo,
                'fecha_programada' => Carbon::parse($mantenimiento->fecha_programada)->format('Y-m-d'),
            ];
        });

        $ordenes_por_tipo = Orden::select('tipo', DB::raw('count(*) as total'))->where('estatus', 2)
            ->groupBy('tipo')
            ->get();
         
        // Simulación de tiempo promedio de la orden
        // En un entorno real, esto se calcularía en base a las fechas de apertura y cierre.
        $tiempo_promedio_orden = 1.5; // Días

        
        $gasto_mensual = collect([
            // (object)['name' => 'Diciembre', 'y' => 2500],
            // (object)['name' => 'Enero', 'y' => 3100],
            // (object)['name' => 'Febrero', 'y' => 2200],
            
        ]);

        // Simulación de vehículos con más reportes de falla 

        $vehiculos_mas_fallas = Vehiculo::select('flota as name', DB::raw('count(*) as y'))
            ->join('ordenes', 'vehiculos.id', '=', 'ordenes.id_vehiculo')
            ->where('ordenes.fecha_in', '>=', Carbon::now()->subDays(60))
            ->groupBy('vehiculos.flota')
            ->orderByDesc('y')
            ->limit(5)
            ->get();

        //  // Datos de ejemplo para el gráfico de frecuencia de fallas por unidad
        //  $vehiculos_mas_fallas =
        
        // collect([
        //     (object)['name' => 'Flota 003', 'y' => 15],
        //     (object)['name' => 'Flota 005', 'y' => 10],
        //     (object)['name' => 'Flota 013', 'y' => 8],
        //     (object)['name' => 'Otros', 'y' => 12],
        // ]);

        // Simulación de alertas de kilometraje
        $alertas_kilometraje=Vehiculo::where('km_mantt','>',4700)->orWhere('hrs_mantt','>',180)->select('flota as vehiculo','placa','km_mantt as kilometraje')->get();
        // $alertas_kilometraje = [
        //     (object)['vehiculo' => 'Flota 003', 'placa' => 'ABC-123', 'kilometraje' => 105000, 'proximo_mantenimiento' => 100000],
        //     (object)['vehiculo' => 'Flota 005', 'placa' => 'DEF-456', 'kilometraje' => 82000, 'proximo_mantenimiento' => 80000],
        // ];

        return view('orden.index', compact(
            'reportes_falla',
            'mantenimientos_proximos',
            'tiempo_promedio_orden',
            'gasto_mensual',
            'vehiculos_mas_fallas',
            'alertas_kilometraje',
            'ordenes_abiertas',
            'ordenes_por_tipo'
        ));
    }

    public function searchSupplies(Request $request)
    {
        $search = $request->input('query');
        
        $suministros = Inventario::where('descripcion', 'LIKE', "%{$search}%")
                                 ->orWhere('codigo', 'LIKE', "%{$search}%")
                                 ->get();
        
        return response()->json($suministros);
    }

    public function purchaseOrder($id_order=null,$id=null)
    {
        $user=Auth::user();
        $admin = in_array($user->id_perfil, [1,2,7,8,18]);
        if(!is_null($id_order)){
            $orden = Orden::findOrFail($id_order);
            if(!is_null($id)){
                $purchaseOrder=SuministroCompra::find($id);
                $vehiculo=null;
                if(!is_null($orden->id_vehiculo)){
                    $vehiculo=Vehiculo::find($orden->id_vehiculo);
                }
                $purchaseDetail=SuministroCompraDetalle::where('suministro_compra_id',$id)->get();
                return view('orden.compra',compact('orden','purchaseOrder','purchaseDetail','vehiculo','user','admin'));
            }
            $data=SuministroCompra::where('orden_id',$id_order)->get();
            return view('orden.compras',compact('data','orden','user','admin'));
        }else{
            $data = SuministroCompra::where('estatus',1)->with('detalles','orden')->get();
            return view('orden.compras',compact('data','user','admin'));
        }

    }

    public function markAsReceived($id)
    {
        try {
            // Actualizar el estatus del suministro a 1 (Despachado/Recibido)
            $supply = SuministroCompraDetalle::find($id);
            $supply->estatus = 1; 
            $supply->save();

            return response()->json(['success' => true, 'message' => 'Suministro marcado como recibido.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function markRequestReceived($id)
    {
        
        try {
            $supply = SuministroCompraDetalle::find($id);
            // Actualizar el estatus del suministro a 1 (Despachado/Recibido)
            $supply->estatus = 1; 
            $supply->save();

            return response()->json(['success' => true, 'message' => 'Suministro marcado como recibido.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function actualizarPrecio(Request $request)
    {
        $detalle = SuministroCompraDetalle::find($request->id);

        if (!$detalle) {
            return response()->json(['ok' => false, 'msg' => 'Detalle no encontrado']);
        }

        $detalle->costo_unitario_aprobado = $request->precio;
        $detalle->save();


        return response()->json(['ok' => true]);
    }

    public function cambiarEstatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:suministros_compras,id', // O el nombre de tu tabla
            'estatus' => 'required|integer|in:2,3,4',
            'observacion_admin' => 'nullable|string|max:500', // Capturar el nuevo campo
        ]);

        try {
            $orden = SuministroCompra::findOrFail($request->id);
            
            // 1. Actualizar estatus y guardar comentario
            $orden->estatus = $request->estatus;
            
            // Guardar el comentario en una columna de comentarios/auditoría
            // Asumiendo que tienes una columna 'comentario_estatus'
            if ($request->filled('observacion_admin')) {
                $orden->observacion_admin = $request->observacion_admin;
            }

            $orden->save();
            
            $msg = '';

            switch ($request->estatus) {
                case 2:
                    $msg = 'Orden Aprobada con éxito.';
                    break;
                case 3:
                    $msg = 'Orden Rechazada.';
                    break;
                case 4:
                    $msg = 'Orden Marcada como Recibida.';
                    break;
                default:
                    $msg = 'Estatus actualizado.';
                    break;
            }

            return response()->json(['ok' => true, 'msg' => $msg]);

        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => 'Fallo al procesar la orden: ' . $e->getMessage()], 500);
        }
    }

    public function filter(Request $request)
        {
        // 1. Inicializar el Query Builder del modelo correcto
        $query = Orden::query(); 
        
        // 2. Llamar al list() del padre, que ejecutará el applyBusinessFilters(si existe)
        // y luego el filtro de seguridad de cliente.
        return $this->list($query); 
    }

    protected function getDetailsForView($item){

        $insumos= InventarioSuministro::where('id_orden', $item->id)->get();
        $trabajos= Trabajos::where('id_orden', $item->id)->get();
        $trabajosExternos = TrabajoExterno::where('id_orden', $item->id)->with(['proveedor'])->get();
        $fotos= OrdenFoto::where('orden_id',$item->id)->get();
        $requerimientos = SuministroCompra::where('orden_id', $item->id)->with('detalles')->get();
        $estatusData = EstatusData::find($item->estatus);
        $categorias_tempario = TemparioCategoria::orderBy('categoria')->get();
        $personal = Personal::with('persona')->where('cargo', 'Mecánico')->get(); // Cargar relación con Persona para obtener nombres completos
        $inventario = Inventario::all()->keyBy('id_inventario');
        $proveedores = Proveedor::whereIn('id_tipo_proveedor', [2])->orderBy('nombre')->get();


        
        return  [
            'insumos' => $insumos,
            'trabajos' => $trabajos,
            'fotos' => $fotos,
            'requerimientos' => $requerimientos,
            'estatusData' => $estatusData,
            'categorias_tempario' => $categorias_tempario,
            'personal' => $personal,
            'inventario' => $inventario,
            'trabajosExternos' => $trabajosExternos,
            'proveedores' => $proveedores,
        ];  

    }

     protected function applyBusinessFilters(Builder $query): Builder
    {
        $filterKey = request()->get('filter');
        $vehiculoId = request()->get('vehiculo_id'); 
        $startDate = request()->get('start_date'); 
        $endDate = request()->get('end_date');  

        if ($vehiculoId && is_numeric($vehiculoId)) {
            
            $query->ByVehiculo((int)$vehiculoId);
            
            // APLICAMOS EL FILTRO DE FECHA RECIBIDO DEL REPORTE
            if ($startDate && $endDate) {
                // Se asume que la columna de fecha de creación es 'created_at'
                // y que las fechas vienen en formato YYYY-MM-DD
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(), 
                    Carbon::parse($endDate)->endOfDay()
                ]);
            }

            // Salimos para no aplicar otros filtros concurrentemente
            return $query; 
        }
        
        if ($filterKey) {
            switch ($filterKey) {
                
                case 'abiertas':
                    $query->OrdenesAbiertas();
                    break;

                case 'tiempo_alerta':
                    $query->OrdenesFueraTiempo();
                    break;
                
                case 'mantenimiento':
                    // Filtro genérico que solo aplica si Vehiculo tiene columna 'estatus'
                    $query->OrdenesMantenimiento();
                    break;
                case 'programadas':
                    $query->OrdenesProgramadas();
                    break;
                
            }
        }

        return $query; // Devolvemos el Query Builder modificado
    }

    public function finalizarTrabajo($id)
    {
        try {
            $trabajo = Trabajos::findOrFail($id);
            
            if ($trabajo->finalizado) {
                return response()->json(['success' => false, 'message' => 'Este trabajo ya fue cerrado.']);
            }

            $fechaInicio = $trabajo->fecha_inicio ? Carbon::parse($trabajo->fecha_inicio) : Carbon::parse($trabajo->created_at);
            $fechaFin = now();
            
            // Calcular diferencia en formato humano o minutos
            $minutos = $fechaInicio->diffInMinutes($fechaFin);
            $horas = floor($minutos / 60);
            $minRestantes = $minutos % 60;
            $tiempoTexto = ($horas > 0) ? "{$horas}h {$minRestantes}m" : "{$minRestantes}min";

            $trabajo->fecha_fin = $fechaFin;
           // $trabajo->tiempo_ejecucion = $tiempoTexto; // Guardamos el string calculado
            $trabajo->save();

            return response()->json([
                'success' => true,
                'message' => "Trabajo finalizado en: $tiempoTexto"
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Muestra el listado de órdenes de trabajo.
     * @return \Illuminate\View\View
     */
    
    // public function list()
    // {
    //     // En una aplicación real, esto obtendría los datos de la base de datos
    //     // y podría usar paginación.
    //     $data = collect([
    //         (object)['id' => 1, 'nro_orden' => 1001, 'vehiculo' => 'Vehículo 001 (ABC-123)', 'fecha_in' => '2024-05-10', 'tipo' => 'Reparación', 'estatus' => 'Cerrada'],
    //         (object)['id' => 2, 'nro_orden' => 1002, 'vehiculo' => 'Vehículo 008 (DEF-456)', 'fecha_in' => '2024-05-12', 'tipo' => 'Mantenimiento', 'estatus' => 'Abierta'],
    //         (object)['id' => 3, 'nro_orden' => 1003, 'vehiculo' => 'Vehículo 012 (GHI-789)', 'fecha_in' => '2024-05-15', 'tipo' => 'Servicio', 'estatus' => 'Abierta'],
    //     ]);
    //     $estatusData = EstatusData::all()->keyBy('id_estatus');

    //     return view('ordenes.list', compact('data'));
    // }


    private function generateOrdenCode()
    {
        // Obtener la fecha actual
        $today = Carbon::now();
        $year = $today->format('y');
        $day = $today->format('d');
        
        // Mapeo de meses a abreviaturas en español
        $month_map = [
            '01' => 'EN', '02' => 'FE', '03' => 'MA', '04' => 'AB',
            '05' => 'MY', '06' => 'JN', '07' => 'JL', '08' => 'AG',
            '09' => 'SE', '10' => 'OC', '11' => 'NO', '12' => 'DI',
        ];
        $month = $month_map[$today->format('m')];

        // Buscar la última orden del día
        $lastOrden = Orden::whereDate('created_at', $today)
                           ->orderBy('created_at', 'desc')
                           ->first();
        
        // Determinar el número secuencial
        $sequential_number = 1;
        if ($lastOrden) {
            // Extraer el número secuencial del código de la última orden
            $last_nro = substr($lastOrden->nro_orden, 6);
            $sequential_number = intval($last_nro) + 1;
        }

        // Formatear el número secuencial con ceros a la izquierda
        $padded_number = str_pad($sequential_number, 2, '0', STR_PAD_LEFT);

        // Combinar todas las partes
        return "{$year}{$month}{$day}{$padded_number}";
    }


    
    /**
     * Muestra el formulario para crear un nuevo recurso, sobrescribiendo el del BaseController.
     * @return \Illuminate\View\View
     */
    public function create($vehiculo_id=null)
    {
        // En una app real, se obtendrían de la base de datos
        $vehiculo =NULL;
        $vehiculos = Vehiculo::orderBy('flota')->orderBy('placa')->get();
        $personal = Personal::with('persona')->where('cargo', 'Mecánico')->get(); // Cargar relación con Persona para obtener nombres completos
        
        $tipos = TipoOrden::all();
        $tipo_falla= TipoFalla::all();
        $nro_orden = $this->generateOrdenCode();
        $categorias_tempario = TemparioCategoria::orderBy('categoria')->get();
        $servicios_tempario = TemparioServicio::orderBy('servicio')->get();
        $suministros = Inventario::orderBy('descripcion')->get();
        $tipo_req= TipoRequerimiento::orderBy('tipo')->get();
        $talleres = Proveedor::where('id_tipo_proveedor', 2)->orderBy('nombre')->get();

        $estatusOpciones = EstatusData::all()->keyBy('id_estatus');        
        if(!is_null($vehiculo_id)){
            $vehiculo = Vehiculo::findOrFail($vehiculo_id); 
        }
        return view('orden.create', compact('vehiculo', 'tipo_req','talleres', 'vehiculos', 'personal','tipos', 'nro_orden','suministros','estatusOpciones', 'tipo_falla','categorias_tempario','servicios_tempario'));
    }

    /**
     * Muestra el recurso especificado (hoja técnica), sobrescribiendo el del BaseController.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        
        try {
            // Asumiendo que se puede obtener la orden con sus relaciones
            $orden = $this->model->where('id', $id)->with(['vehiculoBelong'])->first();
            $trabajos = Trabajos::where('id_orden', $id)->with(['categoria', 'servicio'])->get();
            $trabajosExternos = TrabajoExterno::where('id_orden', $id)->with(['proveedor'])->get();

            // 1. Extraemos los IDs asegurando que no haya nulos y limpiando el array
            $todosLosIds = $trabajos->pluck('id_mecanico')
                ->flatten()
                ->filter()
                ->unique();

            // 2. Pedimos el Personal e INCLUIMOS la relación 'persona' aquí (Eager Loading)
            $personalRelacionado = Personal::with('persona')
                ->whereIn('id_personal', $todosLosIds)
                ->get()
                ->keyBy('id_personal'); // Esto nos permite buscar por ID rápidamente

            // 3. Asignamos los datos a cada trabajo usando los datos ya cargados en memoria
            $trabajos->each(function ($trabajo) use ($personalRelacionado) {
                // Normalizamos los IDs (por si vienen como string "1,2" o array [1,2])
                $ids = is_array($trabajo->id_mecanico) 
                    ? $trabajo->id_mecanico 
                    : explode(',', (string)$trabajo->id_mecanico);
                if($trabajo->fecha_fin){
                    $fechaInicio = Carbon::parse($trabajo->created_at);
                    $fechaFin = Carbon::parse($trabajo->fecha_fin);
                    $minutos = $fechaInicio->diffInMinutes($fechaFin);
                    $horas = floor($minutos / 60);
                    $minRestantes = $minutos % 60;
                    $tiempoTexto = ($horas > 0) ? "{$horas}h {$minRestantes}m" : "{$minRestantes}min";
                    $trabajo->tiempo_ejecucion = $tiempoTexto;
                // Filtramos la colección que ya tenemos en memoria (sin ir a la base de datos otra vez)
                }else{
                    $trabajo->tiempo_ejecucion = null;
                }
                $trabajo->mecanicos_lista = $personalRelacionado->whereIn('id_personal', $ids)->values();
            });
            $requerimientos = SuministroCompra::where('orden_id', $id)->with('detalles')->get();
            $estatusData = EstatusData::find($orden->estatus);
            $fotos= OrdenFoto::where('orden_id',$id)->get();
            $personal = Personal::with('persona')->where('cargo', 'Mecánico')->get(); // Cargar relación con Persona para obtener nombres completos
            $inventario = Inventario::where('id_almacen',2)->orderBy('descripcion')->get();
            $suministros= InventarioSuministro::where('id_orden', $id)->with('inventario')->get();
            $proveedores = Proveedor::whereIn('id_tipo_proveedor', [2])->orderBy('nombre')->get();
        
            $categorias_tempario = TemparioCategoria::orderBy('categoria')->get();
            

            return view('orden.show', compact('orden', 'requerimientos', 'suministros','proveedores','trabajos','estatusData','fotos', 'trabajos', 'personal', 'inventario','categorias_tempario', 'trabajosExternos'));
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'La orden de trabajo no fue encontrada.');
            return Redirect::route('orden.list');
        }
    }

    public function addTrabajoExterno(Request $request)
    {
        // 1. Validación estricta
        $request->validate([
            'id_orden'    => 'required|exists:ordenes,id',
            'descripcion' => 'required|string|max:1000',
            'fecha'       => 'required|date',
            'costo'       => 'required|numeric|min:0',
            // id_proveedor es requerido a menos que venga un nombre nuevo
            'id_proveedor' => 'required_without:nuevo_proveedor_nombre|nullable|exists:proveedores,id',
            'nuevo_proveedor_nombre' => 'required_without:id_proveedor|nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                
                // 2. Lógica del Proveedor (Seleccionado vs Nuevo)
                $idProveedor = $request->id_proveedor;

                if ($request->filled('nuevo_proveedor_nombre')) {
                    // Usamos firstOrCreate para evitar duplicados por nombre
                    $proveedor = Proveedor::firstOrCreate(
                        ['nombre' => trim($request->nuevo_proveedor_nombre), 'id_tipo_proveedor' => 2],
                    );
                    $idProveedor = $proveedor->id;
                }

                // 3. Creación del registro de Trabajo Externo
                $trabajo = TrabajoExterno::create([
                    'id_orden'     => $request->id_orden,
                    'id_proveedor' => $idProveedor,
                    'id_usuario'   => auth()->id(),
                    'descripcion'  => $request->descripcion,
                    'fecha'        => $request->fecha,
                    'costo'        => $request->costo,
                ]);

                // 4. Respuesta para el AJAX de SweetAlert2
                return response()->json([
                    'success' => true,
                    'message' => 'Servicio externo registrado con éxito.',
                    'data'    => $trabajo
                ]);
            });

        } catch (\Exception $e) {
            // Log del error para depuración
            Log::error("Error en addTrabajoExterno: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    public function anular(Request $request, $id)
    {
        return DB::transaction(function () use ($id, $request) {
            $orden = Orden::findOrFail($id);

            // 1. Reversión de Inventario (Solo insumos entregados)
            foreach ($orden->suministros as $item) {
                // Si el estatus es 'ENTREGADO' (ej: id 2), devolvemos al stock
                if ($item->estatus == 2) { 
                    $producto = Inventario::find($item->id_inventario);
                    if ($producto) {
                        $producto->increment('stock', $item->cantidad);
                    }
                }
                // Opcional: Marcar el suministro como anulado
                $item->update(['estatus' => 0]); 
            }

           

            $vehiculo = $orden->id_vehiculo ? Vehiculo::find($orden->id_vehiculo) : null;
            $vehiculo->update(['estatus' => 1]); // 1 = Disponible

            // Enviar notificación a Telegram
                $mensajeTelegram = "Orden #{$orden->nro_orden} ha sido ANULADA.\n";
                $mensajeTelegram .= "Motivo: {$request->anulacion}\n";
                if ($vehiculo) {
                    $mensajeTelegram .= "Vehículo: {$vehiculo->flota} ({$vehiculo->placa})\n";
                }
                $this->telegramService->sendMessage($mensajeTelegram);


            // 2. Actualizar Orden
            $orden->update([
                'estatus' => 4, // ANULADA
                'motivo_anulacion' => $request->anulacion,
                'fecha_cierre' => now()
            ]);

            if($orden->tipo == 'Mantenimiento' || $orden->tipo == 'Preventivo'){
                $mantenimiento = MantenimientoProgramado::where('orden_id', $orden->id)->first();
                if($mantenimiento){
                    $mantenimiento->estatus = 1; // ANULADA
                    $mantenimiento->save();
                }   
            }

            return response()->json(['success' => true]);
        });
    }



    /**
     * Agregar Trabajo (Desde el Modal)
     */
    public function addTrabajo(Request $request, $id)
    {
        $mecanicos = $request->mecanicos; // [1] o [1, 2, 5]

        // Procesamos la lógica: Si es array y tiene elementos, los unimos por coma
        // Si no, lo dejamos nulo o vacío
        $id_mecanico_formateado = is_array($mecanicos) ? implode(',', $mecanicos) : $mecanicos;
    
        // Tu estándar de creación de trabajos
        $trabajo=Trabajos::create([
            'id_orden' => $id,
            'descripcion' => $request->descripcion,
            'id_mecanico' => $id_mecanico_formateado,
            'id_tempario' => $request->id_tempario,
            'costo' => $request->costo,
            'id_tempario' => $request->id_tempario
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Trabajo agregado',
            'data' => $trabajo
        ]);
    }

    public function deleteTrabajo($id)
    {
        $trabajo= Trabajos::find($id);
        $trabajo->delete();

        return response()->json(['success' => true]);
    }

    
    

    public function addInsumo(Request $request, $id)
    {
        // Lógica para agregar insumo a la orden
        // Esto podría incluir validación, creación de registros en tablas pivot, etc.

        // Ejemplo básico:
        $orden = Orden::findOrFail($id);
        $orden->suministros()->create([
            'id_inventario' => $request->id_inventario,
            'cantidad' => $request->cantidad,
            'precio_unitario' => Inventario::find($request->id_inventario)->costo_div ?? 0,
            'id_orden' => $orden->id,
            'id_auto' => $orden->id_vehiculo,
            'id_usuario' => Auth::id(),
            'id_emisor' => Auth::id(),
            'estatus' => 2, // 2 = Solicitado/En Uso
        ]);

        return back()->with('success', 'Insumo agregado correctamente.');
    }

    public function addManualSupply(Request $request, $id)
    {
        $user=Auth::user();
        
        $orden = Orden::findOrFail($id);
        $hoy = now()->format('Y-m-d');
        if($user->id_perfil==4){
            $stat=2;
            $cantidad_aprobada=0;  
            $costo_aprobado=0;
        }else{
            $stat=3;
            $cantidad_aprobada=$request->cantidad;
            $costo_aprobado=$request->costo;
        }
        
        // 1. Buscar si ya existe una solicitud de compra hoy para esta orden de trabajo
        $compra = SuministroCompra::where('orden_id', $id)
                    ->whereDate('created_at', $hoy)
                    ->where('estatus', $stat) // Solo si sigue abierta/pendiente
                    ->first();

        // 2. Si no existe, la creamos
        if (!$compra) {
            $compra = SuministroCompra::create([
                'orden_id' => $id,
                'observacion' => $request->supplies_observations ?? 'Suministro manual vía Dashboard',
                'usuario_solicitante_id' => auth()->id(),
                'estatus' => $stat,
                'nro_requerimiento' => 'REQ-' . strtoupper(Str::random(5)), // Generar un nro si es nueva
            ]);
        }

        // 3. Crear el detalle
        $detalle = $compra->detalles()->create([
            'inventario_id' => null, // Es manual, no catalogado
            'descripcion' => $request->descripcion,
            'cantidad_solicitada' => $request->cantidad,
            'cantidad_aprobada' => $cantidad_aprobada,
            'costo_unitario_aprobado' => $costo_aprobado,
            'estatus' => $stat, // Solicitado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Suministro añadido al requerimiento #' . $compra->id,
            'data' => [
                'id' => $detalle->id,
                'cantidad' => $detalle->cantidad_solicitada,
                'descripcion' => $detalle->descripcion
            ]
        ]);
    }

    

    /**
     * Eliminar definitivamente (Solo si está anulada)
     */
    public function destroy($id)
    {
        $orden = Orden::findOrFail($id);
        $supply = InventarioSuministro::where('id_orden', $id)->delete();
        $compras = SuministroCompra::where('orden_id', $id)->get();
        foreach($compras as $compra){
            $compra->detalles()->delete();
            $compra->delete();
        };
        
        if($orden->estatus != 4) return response()->json(['success' => false, 'message' => 'Solo órdenes anuladas']);

         if($orden->tipo == 'Mantenimiento' || $orden->tipo == 'Preventivo'){
                $mantenimiento = MantenimientoProgramado::where('orden_id', $orden->id)->first();
                if($mantenimiento){
                    $mantenimiento->delete();
                }   
            }

        $orden->delete(); // Gracias a OnDelete Cascade en DB, borra trabajos y suministros
        return response()->json(['success' => true]);
    }


    /**
     * Sobrescribimos el método `store` para manejar la lógica específica.
     * La validación se haría aquí o en un FormRequest.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function store(Request $request)
    {
        // 1. Validación rápida (Fuera de la transacción)
        $request->validate([
            'id_vehiculo' => 'nullable|exists:vehiculos,id',
            'fotos_orden.*' => 'image|max:5120', // Máximo 5MB por foto
        ]);

        $userId = Auth::id();
        $user = User::find($userId);

        $result = DB::transaction(function () use ($request, $userId, $user) {
            // 2. Carga inicial de datos para evitar consultas repetitivas
            $suministros = json_decode($request->supplies_json, true) ?? [];
            $trabajos = json_decode($request->trabajos_json, true) ?? [];
            
            $orden = Orden::create($request->all());
            $vehiculo = $request->id_vehiculo ? Vehiculo::find($request->id_vehiculo) : null;

            // 3. Procesar Suministros (Agrupando para optimizar)
            $usoInventario = [];
            $solicitudCompra = [];
            foreach ($suministros as $item) {
                str_contains($item['id'], 'MANUAL') ? $solicitudCompra[] = $item : $usoInventario[] = $item;
            }

            // USO (Descuento de Inventario)
            foreach ($usoInventario as $usoItem) {
                $inv = Inventario::find($usoItem['id']);
                $orden->suministros()->create([
                    'id_inventario' => $usoItem['id'],
                    'cantidad' => $usoItem['cantidad'],
                    'precio_unitario' => $inv->costo_div ?? 0,
                    'id_orden' => $orden->id,
                    'id_auto' => $orden->id_vehiculo,
                    'id_usuario' => $userId,
                    'id_emisor' => $userId,
                    'estatus' => 2,
                ]);
                // RECOMENDACIÓN: $inv->decrement('stock', $usoItem['cantidad']);
            }

            // COMPRA (Generar Solicitud)
            $compra = null;
        
            if (count($solicitudCompra) > 0) {
                if($user->id_perfil == 4){
                    $estatus = 2; // Pendiente 
                }else{
                    $estatus = 3; // Aprobada directamente por admin
                }
            
                $compra = SuministroCompra::create([
                    'orden_id' => $orden->id,
                    'observacion' => $request->supplies_observations,
                    'usuario_solicitante_id' => $userId,
                    'estatus' => $estatus
                ]);

                foreach ($solicitudCompra as $solicitudItem) {
                    if($user->id_perfil == 4){
                        $cantidad_aprobada = 0;
                        $costo_aprobado = 0;
                    }else{
                        $cantidad_aprobada = $solicitudItem['cantidad'];
                        $costo_aprobado = $solicitudItem['precio'];
                    }
                    $compra->detalles()->create([
                        'inventario_id' => is_numeric($solicitudItem['id']) ? $solicitudItem['id'] : null,
                        'descripcion' => $solicitudItem['descripcion'],
                        'cantidad_solicitada' => $solicitudItem['cantidad'],
                        'cantidad_aprobada' => $cantidad_aprobada,
                        'costo_unitario_aprobado' => $costo_aprobado,
                        'estatus' => $estatus,
                    ]);
                }
            }

            // 4. Procesar Trabajos (Eager Loading preventivo)
            foreach ($trabajos as $t) {
                $serv = TemparioServicio::with('categoria')->find($t['id_tempario']);
                $concepto = ($serv && $serv->categoria) ? $serv->categoria->categoria . ' - ' . $serv->servicio : $t['concepto'];
                
                $orden->trabajos()->create([
                    'id_orden' => $orden->id,
                    'id_tempario_servicio' => $t['id_tempario'],
                    'descripcion' => $concepto,
                    'id_categoria' => $t['id_categoria'],
                    'costo' => $serv->costo ?? 0,
                    'id_mecanico' => $t['mecanicos'][0] ?? null,
                    'fecha_inicio' => now(),
                ]);
            }

            // 5. Manejar Fotos (Solo guardar en disco y DB, NO enviar a Telegram todavía)
            if ($request->hasFile('fotos_orden')) {
                foreach ($request->file('fotos_orden') as $file) {
                    $path = Storage::disk('public')->put('ordenes_fotos', $file);
                    $orden->fotos()->create([
                        'ruta_archivo' => $path,
                        'descripcion' => "Foto inicial Orden #{$orden->nro_orden}",
                    ]);
                }
            }

            // 6. Actualizar Vehículo
            if ($vehiculo) {
                $kmRecorridos = (int)$request->kilometraje;
                if ($kmRecorridos > $vehiculo->kilometraje) {
                    $dif = $kmRecorridos - $vehiculo->kilometraje;
                    $vehiculo->kilometraje = $kmRecorridos;
                    $vehiculo->km_contador += $dif;
                    $vehiculo->km_mantt += $dif;
                }
                $vehiculo->save();
            }

            return ['orden' => $orden, 'compra' => $compra, 'vehiculo' => $vehiculo];
        });

        // ---------------------------------------------------------
        // 7. PROCESO POST-GUARDADO (FUERA DE LA TRANSACCIÓN)
        // ---------------------------------------------------------
        // Esto evita que si Telegram falla, se revierta la base de datos
        try {
            $orden = $result['orden'];
            $compra = $result['compra'];
            $vehiculo = $result['vehiculo'];
            $flota = $vehiculo ? $vehiculo->flota : 'N/A';

            // Alerta Interna
            $this->createAlert([
                'id_usuario' => $userId,
                'id_rel' => $orden->id,
                'observacion' => "Nueva orden: {$orden->nro_orden} para {$orden->responsable}",
                'accion' => route('ordenes.show', $orden->id),
                'dias' => 0,
            ]);

            // FCM Notification
            FcmNotificationService::enviarNotification(
                "Orden Abierta {$orden->nro_orden}",
                "Vehículo: {$flota}. Responsable: {$orden->responsable}",
                ['id_rel' => $orden->id]
            );

            // Telegram Principal
            $msg = "📝 *Nueva Orden {$orden->nro_orden}*\nVehículo: {$flota}\nFalla: {$orden->descripcion_1}";
            $this->telegramService->sendMessage($msg);

            $user->notify(new OrdenTrabajoCreada($orden));

            // Telegram Compras (Si aplica)
            if ($compra) {
                $userVentas = User::where('name', 'ventas')->first();
                $msgC = "🚨 *Requerimiento Suministros #{$compra->id}*\nOrden: {$orden->nro_orden}\nDestino: {$flota}";
                $this->telegramService->sendMessage($msgC);
                if ($userVentas && $userVentas->telegram_id) {
                    $this->telegramService->sendMessage($msgC, $userVentas->telegram_id);
                }
            }

            // Enviar fotos a Telegram (Lo más lento)
            if ($request->hasFile('fotos_orden')) {
                foreach ($request->file('fotos_orden') as $index => $file) {
                    $this->telegramService->sendPhotoOrden($file, "Evidencia #".($index+1)." Orden {$orden->nro_orden}");
                }
            }

        } catch (\Exception $e) {
            Log::error("Error en notificaciones: " . $e->getMessage());
            // No interrumpimos el flujo porque la orden YA se guardó.
        }

        Session::flash('success', 'Orden creada exitosamente.');
        
        return ($result['compra']) 
            ? redirect()->route('ordenes.compra', ['id_order' => $orden->id, 'id' => $result['compra']->id])
            : redirect()->route('ordenes.list');
    }

    public function addFotos(Request $request, $id)
    {
        $orden = Orden::findOrFail($id);

        if ($request->hasFile('fotos_orden')) {
            try {
                foreach ($request->file('fotos_orden') as $index => $file) {
                                   
                // 1. Guardar en Servidor Físico (Storage)
                    $filename = "orden_{$orden->nro_orden}_" . time() . "_{$index}." . $file->getClientOriginalExtension();
                    $path = $file->storeAs('ordenes_fotos', $filename, 'public');

                    // 2. Guardar en Base de Datos
                    // Ajusta el modelo 'OrdenFoto' según el nombre real de tu tabla de fotos
                    $orden->fotos()->create([
                        'orden_id' => $orden->id,
                        'ruta_archivo' => $path,
                        'descripcion' => "Foto #{$index} Orden #{$orden->nro_orden}"
                    ]);

                    // 3. Enviar a Telegram (Tu lógica existente)
                    if (isset($this->telegramService)) {
                        $this->telegramService->sendPhotoOrden($file, "📸 Evidencia #".($index+1)." \nOrden: {$orden->nro_orden} \nVehículo: {$orden->vehiculoBelong->placa}");
                    }
                }

                return response()->json(['success' => true, 'message' => 'Fotos procesadas correctamente.']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'No se recibieron imágenes.']);
    }

    public function destroyFoto($id)
    {
        try {
            $foto = OrdenFoto::findOrFail($id);

            // Eliminar archivo físico
            if (Storage::disk('public')->exists($foto->url)) {
                Storage::disk('public')->delete($foto->url);
            }

            $foto->delete();

            return response()->json(['success' => true, 'message' => 'Foto eliminada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Almacena un nuevo suministro para una orden.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeSupply(Request $request)
    {
        $request->validate([
            'id_orden' => 'required|integer|exists:ordenes,id',
            'id_inventario' => 'required|integer|exists:inventario,id',
            'cantidad' => 'required|numeric|min:1',
            // Puedes agregar más validaciones aquí
        ]);
    
        try {
            $userId = Auth::id();
            $orden = Orden::findOrFail($request->id_orden);
    
            $supply = InventarioSuministro::create([
                'id_orden' => $orden->id,
                'id_inventario' => $request->id_inventario,
                'cantidad' => $request->cantidad,
                'precio_unitario' => Inventario::find($request->id_inventario)->costo_div ?? 0, // Obtener el costo del inventario
                'id_usuario' => $userId, // Usuario que registra el suministro
                'id_auto' => $orden->id_vehiculo,
                'id_emisor' => $userId,
                'estatus' => 2, // 2 = 'Solicitado'
            ]);
            $result= InventarioSuministro::with('inventario')->where('id_inventario_suministro', $supply->id_inventario_suministro)->first();
    
            Session::flash('success', 'Suministro agregado exitosamente.');

            return response()->json(['success' => true, 'supply' => $result]);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al agregar el suministro.');
            return response()->json(['success' => false, 'message' => 'Error al agregar el suministro. '.$e->getMessage()], 500);
        }
    }

    /**
     * Actualiza un suministro existente.
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSupply(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:1',
            // Puedes agregar más validaciones aquí
        ]);

        try {
            $supply = InventarioSuministro::findOrFail($id);
            $supply->update([
                'cantidad' => $request->cantidad,
                // Puedes actualizar otros campos aquí
            ]);

            Session::flash('success', 'Suministro actualizado exitosamente.');
            return response()->json(['success' => true, 'supply' => $supply]);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Suministro no encontrado.');
            return response()->json(['success' => false, 'message' => 'Suministro no encontrado'], 404);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al actualizar el suministro.');
            return response()->json(['success' => false, 'message' => 'Error al actualizar el suministro.'], 500);
        }
    }

    /**
     * Elimina un suministro.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSupply($id)
    {
            $supply = InventarioSuministro::findOrFail($id);
            $supply->delete();

            Session::flash('success', 'Suministro eliminado exitosamente.');
            return response()->json(['success' => true, 'message' => 'Suministro eliminado.']);
    }

    public function deleteManualSupply($id)
    {
            $compra = SuministroCompraDetalle::findOrFail($id);
            $compra->delete();

            Session::flash('success', 'Requerimiento eliminado exitosamente.');
            return response()->json(['success' => true, 'message' => 'Requerimiento eliminado.']);
    }


    public function cerrarOrden(Request $request, $id)
    {
        try {
            $orden = Orden::findOrFail($id);
            $orden->estatus = 1; // 1 = 'Cerrada'
            $orden->fecha_out = Carbon::now()->toDateString();
            $orden->hora_out = Carbon::now()->toTimeString();
            $orden->id_us_out = Auth::id(); // Usuario que cierra la orden
            $orden->save();

            if($orden->tipo == 'Mantenimiento' || $orden->tipo == 'Preventivo'){
                $mantenimiento = MantenimientoProgramado::where('orden_id', $orden->id)->first();
                if($mantenimiento){
                    $mantenimiento->estatus = 3; // Cerrada
                    $mantenimiento->save();
                }   
            }

            $vehiculo = Vehiculo::find($orden->id_vehiculo);
            if($vehiculo){
                $vehiculo->estatus = 1; // Disponible
                $vehiculo->save();
            }   

            Session::flash('success', 'Orden de trabajo cerrada exitosamente.');
            return response()->json(['success' => true, 'message' => 'Orden de trabajo cerrada exitosamente']);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden de trabajo no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden de trabajo no encontrada.']);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al cerrar la orden de trabajo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cerrar la orden de trabajo: ' . $e->getMessage()], 500);
        }
    }


     public function anularOrden(Request $request, $id)
    {
        try {
            $orden = Orden::findOrFail($id);
            $orden->estatus = 4; // 4 = 'anulada'
            $orden->fecha_out = Carbon::now()->toDateString();
            $orden->hora_out = Carbon::now()->toTimeString();
            $orden->anulacion = $request->input('anulacion'); // Guardar el motivo de anulación
            $orden->id_us_out = Auth::id(); // Usuario que cierra la orden
            $orden->save();

            $vehiculo = Vehiculo::find($orden->id_vehiculo);
            if($vehiculo){
                $vehiculo->estatus = 1; // Disponible
                $vehiculo->save();
            }
             if($orden->tipo == 'Mantenimiento' || $orden->tipo == 'Preventivo'){
                $mantenimiento = MantenimientoProgramado::where('orden_id', $orden->id)->first();
                if($mantenimiento){
                    $mantenimiento->estatus = 1; // Anulada
                    $mantenimiento->save();
                }   
            }

            Session::flash('success', 'Orden de trabajo cerrada exitosamente.');
                   return response()->json(['success' => true, 'message' => 'Orden de trabajo anulada exitosamente']);
     
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden de trabajo no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden de trabajo no encontrada.']);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al cerrar la orden de trabajo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al anular la orden de trabajo: ' . $e->getMessage()], 500);
        }
    }

    public function getTemparioServicios(Request $request) 
    {
        // Obtenemos el ID que enviamos desde el JS como 'catemp'
        $catId = $request->catemp; 

        if (!$catId) {
            return response('<option value="">Seleccione categoría válida</option>');
        }

        // Tu lógica para buscar servicios
        $servicios = TemparioServicio::where('id_tempario_categoria', $catId)->get();

        $output = '<option value="">Seleccione un servicio...</option>';
        foreach ($servicios as $s) {
            $output .= '<option value="' . $s->id_tempario_servicio . '">' . $s->servicio . '</option>';
        }

        return response($output);
    }

    public function verificarOrdenAbierta($id)
    {
        // Buscamos una orden que no esté cerrada ni anulada para ese vehículo
        $orden = Orden::where('id_vehiculo', $id)
                    ->whereIn('estatus', [2, 3]) // Ajusta según tus IDs de estatus
                    ->first();

        if ($orden) {
            return response()->json([
                'existe' => true,
                'url' => route('ordenes.show', $orden->id),
                'nro_orden' => $orden->nro_orden
            ]);
        }

        return response()->json(['existe' => false]);
    }

    public function habilitarUnidad(Request $request, $id)
    {
        $orden = Orden::findOrFail($id);
        
        // Cambiamos el estatus del VEHÍCULO a disponible, pero la ORDEN sigue abierta (estatus 2)
        $vehiculo = $orden->vehiculoBelong;
        $vehiculo->estatus = 1; // Disponible O el ID/texto que uses para disponibilidad
        $vehiculo->save();

        // Registramos el motivo en las observaciones de la orden
        $orden->descripcion .= "\n\n[UNIDAD HABILITADA SIN CERRAR ORDEN - Motivo: " . $request->motivo . "]";
        $orden->save();

        return response()->json(['success' => true, 'message' => 'La unidad ha sido marcada como DISPONIBLE.']);
    }

    public function getPlanApi($id)
    {
        try {
            // Buscamos el plan. Si no existe, fallamos silenciosamente para el JS
            $plan = PlanMantenimiento::findOrFail($id);

            return response()->json([
                'success' => true,
                'titulo'  => $plan->titulo,
                'descripcion' => $plan->descripcion // El campo con el detalle del servicio
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el plan solicitado'
            ], 404);
        }
    }

     public function reactivarOrden(Request $request, $id)
    {
        try {
            $orden = Orden::findOrFail($id);
            if($orden->estatus==3){
                $accion='INICIADA';
            }else{
                $accion='REACTIVADA';
            }

            $orden->estatus = 2; // 2 = 'Abierta'
            if(is_null($orden->nro_orden)){
                $orden->nro_orden = $this->generateOrdenCode();
            }
            $orden->fecha_out = null;
            $orden->hora_out = null;
            $orden->id_us_out =null; // Usuario que cierra la orden
            $orden->save();

            $vehiculo = Vehiculo::find($orden->id_vehiculo);
            

            if($orden->tipo == 'Mantenimiento' || $orden->tipo == 'Preventivo'){
                if($vehiculo){
                    $vehiculo->estatus = 3; // En mantenimiento
                }
                $mantenimiento = MantenimientoProgramado::where('orden_id', $orden->id)->first();
                if($mantenimiento){
                    $mantenimiento->estatus = 2; // Abierto
                    $mantenimiento->save();
                }   
            }else{
                if($vehiculo){
                    $vehiculo->estatus = 5;       
                }
            }
            $vehiculo->save();
            // Enviar notificación a Telegram
         $mensajeTelegram = "Orden #{$orden->nro_orden} ha sido {$accion}.\n";
         if ($vehiculo) {
             $mensajeTelegram .= "Vehículo: {$vehiculo->flota} ({$vehiculo->placa})\n";
         }
         $this->telegramService->sendMessage($mensajeTelegram);

            Session::flash('success', 'Orden de trabajo Reactivada exitosamente.');
            return response()->json(['success' => true, 'message' => 'Orden de trabajo Reactivada exitosamente']);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden de trabajo no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden de trabajo no encontrada.']);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al cerrar la orden de trabajo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cerrar la orden de trabajo: ' . $e->getMessage()], 500);
        }

    }
}
