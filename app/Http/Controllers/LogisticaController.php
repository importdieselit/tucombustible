<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LogisticaService;
use App\Models\Vehiculo;
use App\Models\Chofer; // Este es el que vincula con personas
use App\Models\Cliente;
use App\Models\TipoCombustible;
use App\Models\Pedido;
use App\Models\Viaje;
use Exception;

class LogisticaController extends Controller
{
    protected $logisticaService;

    public function __construct(LogisticaService $logisticaService)
    {
        $this->logisticaService = $logisticaService;
    }

    public function index()
    {
        // Traemos los viajes con sus relaciones para la tabla principal
        $viajes = Viaje::with(['tipoCombustible', 'detalles.cliente'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.logistica.index', compact('viajes'));
    }

    /**
     * El "Constructor de Carga"
     */
    public function create(Request $request)
    {
        $tipos = TipoCombustible::all();

        // 1. Vehículos (tal cual lo solicitaste, sin filtros de estatus)
        $vehiculos = Vehiculo::select('id', 'placa', 'carga_max', 'tipo')->get();

        // 2. Personal (Chóferes y Ayudantes)
        // Cargamos la relación 'persona' para tener nombres y apellidos
        $personal = Chofer::with('persona')
            ->get()
            ->sortBy(function($chofer) {
                return $chofer->persona->nombre ?? '';
            });

        // 3. Clientes para carga manual (usamos el RIF y Disponible que pide la gerencia)
        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre', 'rif', 'disponible', 'cupos_max']);

        // 4. Pedidos pendientes (Carga automática)
        $tipoSeleccionado = $request->get('tipo_combustible_id');
        $pedidosPendientes = [];
        if ($tipoSeleccionado) {
            $pedidosPendientes = Pedido::where('estado', 'pendiente')
                ->where('tipo_combustible_id', $tipoSeleccionado)
                ->with('cliente')
                ->get();
        }

        return view('admin.logistica.create', compact(
            'tipos', 'vehiculos', 'personal', 'clientes', 'pedidosPendientes', 'tipoSeleccionado'
        ));
    }

    public function store(Request $request)
    {
        // Validación ajustada a tus tablas
        $request->validate([
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'vehiculo_id'         => 'required|exists:vehiculos,id',
            'chofer_id'           => 'required|exists:choferes,id', // ID de la tabla choferes
            'ayudante_id'         => 'nullable|exists:choferes,id', // ID de la tabla choferes
            'fecha_programada'    => 'required|date',
            'clientes'            => 'required|array|min:1',
        ]);

        try {
            $viaje = $this->logisticaService->procesarPlanificacion($request->all());
            return redirect()->route('logistica.planificacion')->with('success', 'Planificación guardada.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}