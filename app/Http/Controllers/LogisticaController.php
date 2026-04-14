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
        $vehiculos = Vehiculo::select('id', 'placa', 'carga_max', 'tipo')->get();
        
        $personal = Chofer::with('persona')->get()->sortBy(function($chofer) {
            return $chofer->persona->nombre ?? '';
        });

        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre', 'rif', 'cupo_gasco', 'cupo']);

        $tipoSeleccionado = $request->get('tipo_combustible_id');
        $pedidosPendientes = [];

        // Lógica de Negocio: Solo hay pedidos pendientes si el combustible es DIESEL (ID suele ser 1 o 2, verifica el tuyo)
        // Suponiendo que el ID de Diesel es el que el usuario elija y tenga pedidos asociados:
        if ($tipoSeleccionado) {
            $pedidosPendientes = Pedido::where('estado', 'pendiente')
                ->with('cliente')
                ->get(); 
            // Nota: Si en el futuro agregas MGO al portal, aquí filtrarías por tipo.
        }

        return view('admin.logistica.create', compact(
            'tipos', 'vehiculos', 'personal', 'clientes', 'pedidosPendientes', 'tipoSeleccionado'
        ));
    }

    public function store(Request $request)
    {
        // Cambiamos 'clientes' por 'items' para que coincida con la vista Alpine.js
        $request->validate([
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'fecha_programada'    => 'required|date',
            'items'               => 'required|array|min:1', 
        ]);

        try {
            $viaje = $this->logisticaService->procesarPlanificacion($request->all());
            
            // Redirigimos a 'logistica.index' que es el nombre real en web.php
            return redirect()->route('logistica.index')->with('success', 'Planificación guardada con éxito.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}