<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\LogisticaService;
use App\Models\Vehiculo;
use App\Models\Chofer; 
use App\Models\Cliente;
use App\Models\Muelles;
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
        $query = Viaje::with(['tipoCombustible', 'detalles.cliente', 'sede']);

        if ($request->has('tipo')) {
            $query->where('tipo_planificacion', $request->tipo);
        }

        $viajes = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.logistica.index', compact('viajes'));
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
        $vehiculos = Vehiculo::select('id', 'placa', 'carga_max', 'tipo')->where('estatus', 1)->get();
        
        $personal = Chofer::with('persona')->get()->sortBy(function($chofer) {
            return $chofer->persona->nombre ?? '';
        });

        // 3. Inicializar colecciones
        $clientes = collect();
        $pedidosPendientes = collect();
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

        return view('admin.logistica.create', compact(
            'tipo', 
            'tipoPlanificacionId', 
            'tipos', 
            'sedes', 
            'vehiculos', 
            'personal', 
            'clientes', 
            'pedidosPendientes', 
            'muelles',
            'proveedores'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_planificacion'  => 'required|in:1,2,3,4',
            'tipo_combustible_id' => 'required_unless:tipo_planificacion,3|nullable|exists:tipos_combustible,id',
            'fecha_programada'    => 'required|date',
            'items'               => 'required_if:tipo_planificacion,1,2|array', 
        ]);

        try {
            $viaje = $this->logisticaService->procesarPlanificacion($request->all());
            
            return redirect()->route('logistica.index')->with('success', 'Planificación guardada con éxito.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}