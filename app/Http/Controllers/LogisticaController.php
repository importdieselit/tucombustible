<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\LogisticaService;
use App\Models\Vehiculo;
use App\Models\Chofer; 
use App\Models\Cliente;
use App\Models\Muelles;
use App\Models\Buques;
use App\Models\TipoCombustible;
use App\Models\Pedido;
use App\Models\Viaje;
use App\Models\Sedes;
use Exception;

class LogisticaController extends Controller
{
    protected $logisticaService;

    public function __construct(LogisticaService $logisticaService)
    {
        $this->logisticaService = $logisticaService;
    }

    public function index(Request $request)
    {
        // 1. Iniciamos la consulta con las relaciones necesarias
        $query = Viaje::with(['tipoCombustible', 'detalles.cliente', 'sede', 'cisternaAcoplada', 'vehiculo', 'compraCombustible.proveedor']);

        // --- NUEVO: Filtro de Búsqueda por Cliente o RIF ---
        if ($request->filled('search_viaje')) {
            $search = $request->search_viaje;
            $query->whereHas('detalles.cliente', function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                ->orWhere('rif', 'LIKE', "%{$search}%");
            });
        }

        // --- FILTROS EXISTENTES ---
        if ($request->filled('tipo')) {
            $tipoFiltro = $request->tipo;

            // Si viene una compra específica (ej: 4_diesel o 4_mgo)
            if (str_contains($tipoFiltro, '_')) {
                [$tipoPlanificacion, $combustible] = explode('_', $tipoFiltro);
                
                $query->where('tipo_planificacion', $tipoPlanificacion);
                
                // Mapeamos el string al entero correspondiente de tu tabla viajes (1 = Diesel, 2 = MGO)
                $idCombustible = ($combustible === 'diesel') ? 1 : 2;
                $query->where('tipo', $idCombustible);
            } else {
                // Filtro tradicional plano (Despachos y Fletes)
                $query->where('tipo_planificacion', $tipoFiltro);
            }
        }
        
        if ($request->filled('estado')) {
            $query->where('status', $request->estado);
        }

        // --- MEJORA: Filtro por Rango de Fechas ---
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_hasta);
        }

        // 2. Paginación Real (Paginamos de 20 en 20 para que sea cómodo)
        $viajes = $query->orderBy('created_at', 'desc')->paginate(20);

        // 3. Consulta de Pedidos Pendientes (Esta se mantiene igual para el bloque superior)
        $pedidosPendientes = Pedido::with('cliente')
            ->where('estado', 'pendiente') 
            ->orderBy('fecha_solicitud', 'asc')
            ->get();

        return view('admin.logistica.index', compact('viajes', 'pedidosPendientes'));
    }

    /**
     * El "Constructor de Carga" dinámico
     */
    public function create($tipo = 'diesel')
    {
        // 1. Mapeo del parámetro de la URL al ID de la Base de Datos
        $tiposPermitidos = ['diesel' => 1, 'mgo' => 2, 'flete' => 3, 'compra' => 4];
        if (!array_key_exists($tipo, $tiposPermitidos)) {
            abort(404, 'Tipo de planificación no válido.');
        }
        $tipoPlanificacionId = $tiposPermitidos[$tipo];

        // 2. Datasources comunes
        $tipos = TipoCombustible::all();
        $sedes = Sedes::where('estatus', true)->get();
        $vehiculos = Vehiculo::whereIn('tipo', ['1', '3'])
            ->get();
        $cisternas = Vehiculo::whereIn('tipo', ['2', '5'])
            ->get();
        
        $personal = Chofer::with('persona')->get()->sortBy(function($chofer) {
            return $chofer->persona->nombre ?? '';
        });

        // 3. Inicializar colecciones
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $pedidosPendientes = Pedido::where('estado', 'pendiente')->with('cliente')->get();
        $proveedores = collect();
        $muelles = DB::table('muelles')->orderBy('nombre')->get();

        // 4. Carga condicional de datos según el DDL y lógica de negocio
        
        // Clientes: Para Diesel, MGO y Fletes
        if (in_array($tipoPlanificacionId, [1, 2, 3])) {
            $clientes = Cliente::where('status', Cliente::STATUS_APROBADO)
                               ->orderBy('nombre')
                               ->get(['id', 'nombre', 'rif', 'cupo', 'direccion']);
        }

        // Pedidos: Solo para Diesel
        if ($tipoPlanificacionId == 1) {
            $pedidosPendientes = Pedido::where('estado', 'pendiente')
                ->with('cliente')
                ->get(); 
        }

        // Proveedores: Solo para Compras (Corregido según tu DDL)
        if ($tipoPlanificacionId == 4) {
            $proveedores = DB::table('proveedores')
                ->select('id', 'nombre', 'rif')
                ->orderBy('nombre')
                ->get();
        }

        $muelles = DB::table('muelles')->orderBy('nombre')->get();

        $buques = Buques::orderBy('nombre', 'asc')->get();

        return view('admin.logistica.create', compact(
            'tipo', 
            'tipoPlanificacionId', 
            'tipos', 
            'sedes', 
            'vehiculos', 
            'cisternas',
            'personal', 
            'clientes', 
            'pedidosPendientes', 
            'muelles',
            'proveedores',
            'buques'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            // Validaciones base
            'tipo_planificacion'  => 'required|in:1,2,3,4',
            'producto_flete'       => 'required_if:tipo_planificacion,3|string|max:255',
            'tipo_combustible_id' => 'required_unless:tipo_planificacion,3|nullable|exists:tipos_combustible,id',
            'fecha_programada'    => 'required|date',
            'items'               => 'required_if:tipo_planificacion,1,2|array', 

            // --- NUEVAS VALIDACIONES CONDICIONALES ---
            
            // El switch que define la lógica del transporte (1 = Propio, 0 = Externo)
            'es_transporte_propio' => 'required|in:0,1',

            // CASO 1: Si es transporte PROPIO (es_transporte_propio es 1)
            'vehiculo_id'          => 'required_if:es_transporte_propio,1|nullable|exists:vehiculos,id',
            'chofer_id'            => 'required_if:es_transporte_propio,1|nullable|exists:choferes,id',
            'cisterna'             => 'required_if:es_transporte_propio,1|nullable|exists:vehiculos,id',
            'ayudante_id'          => 'nullable|exists:choferes,id',

            // CASO 2: Si es transporte EXTERNO (es_transporte_propio es 0)
            'vehiculo_externo'     => 'required_if:es_transporte_propio,0|nullable|string|max:255',
            'cisterna_externo'     => 'required_if:es_transporte_propio,0|nullable|string|max:255',
            'chofer_externo'       => 'required_if:es_transporte_propio,0|nullable|string|max:255',
            'ayudante_externo'     => 'nullable|string|max:255',
        ]);

        try {
            $viaje = $this->logisticaService->procesarPlanificacion($request->all());
            
            return redirect()->route('logistica.index')->with('success', 'Planificación guardada con éxito.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // Método para ver detalles
    public function show($id)
    {
        $viaje = Viaje::with([
            'vehiculo', 
            'chofer.persona', 
            'ayudante.persona', 
            'sede', 
            'detalles.cliente',
            'detalles.buques',
            'proveedor',
            'chofer.persona',
            'ayudante.persona',
            'compraCombustible',
            'proveedor'
            ])->findOrFail($id);

        // Retornamos una vista parcial que se cargará dentro del modal
        return view('admin.logistica.partials.detalles_modal', compact('viaje'));
    }

    // Método para cancelar
    public function cancelar($id)
    {
        try {
            $viaje = Viaje::findOrFail($id);
            
            if ($viaje->status === 'COMPLETADO') {
                return response()->json(['error' => 'No se puede cancelar una planificación ya completada.'], 422);
            }

            $viaje->update(['status' => 'CANCELADO']);

            return response()->json(['success' => 'Planificación cancelada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cancelar: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        // 1. Cargamos el viaje con todas las relaciones que necesita el formulario para hidratarse
        $viaje = Viaje::with(['detalles.cliente', 'compraCombustible.proveedor', 'vehiculo'])->findOrFail($id);
        
        // 2. Mapeo inverso para obtener el slug del tipo (diesel, mgo, flete, compra)
        $tipoSlugs = [1 => 'diesel', 2 => 'mgo', 3 => 'flete', 4 => 'compra'];
        $tipo = $tipoSlugs[$viaje->tipo_planificacion];
        $tipoPlanificacionId = $viaje->tipo_planificacion;

        // 3. Datasources comunes
        $tipos = TipoCombustible::all();
        $sedes = Sedes::where('estatus', true)->get();
        $vehiculos = Vehiculo::where('estatus', 1)->get(['id', 'placa', 'carga_max', 'tipo', 'flota']);
        // Extraemos las cisternas (Ajusta el where según cómo identifiques tus cisternas en la BD)
        $cisternas = Vehiculo::whereIn('tipo', [2, 4])->where('estatus', 1)->get(['id', 'flota', 'placa', 'carga_max', 'vol']); 
        $personal = Chofer::with('persona')->get();
        $muelles = DB::table('muelles')->orderBy('nombre')->get();
        $buques = Buques::orderBy('nombre', 'asc')->get();
        
        // 4. Datos específicos por tipo
        $clientes = (in_array($tipoPlanificacionId, [1, 2, 3])) ? Cliente::where('status', Cliente::STATUS_APROBADO)->orderBy('nombre')->get() : collect();
        $proveedores = ($tipoPlanificacionId == 4) ? DB::table('proveedores')->get() : collect();
        $pedidosPendientes = ($tipoPlanificacionId == 1) ? Pedido::where('estado', 'pendiente')->with('cliente')->get() : collect();

        return view('admin.logistica.edit', compact(
            'viaje', 'tipo', 'tipoPlanificacionId', 'tipos', 'sedes', 
            'vehiculos', 'cisternas', 'personal', 'clientes', 'muelles', 'proveedores', 'pedidosPendientes', 'buques'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo_planificacion'  => 'required|in:1,2,3,4',
            'fecha_programada'    => 'required|date',
        ]);
        
        try {
            $this->logisticaService->actualizarPlanificacion($id, $request->all());
            return redirect()->route('logistica.index')->with('success', 'Planificación actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }    
}