<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sedes;
use App\Models\Deposito;
use App\Models\TipoCombustible; 
use App\Models\ConsumoOperativo;
use App\Models\Vehiculo;
use App\Services\CombustibleService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Exception;

class ConsumoOperativoController extends Controller
{
    protected $combustibleService;

    public function __construct(CombustibleService $combustibleService)
    {
        $this->combustibleService = $combustibleService;
    }

    public function index(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        
        $query = ConsumoOperativo::with(['sede', 'deposito', 'tipoCombustible', 'user']);

        // 1. Filtro: Por Sede
        if ($request->filled('sede_id')) {
            $query->where('sede_id', $request->input('sede_id'));
        }

        // 2. Filtro: Rango de fechas (Desde / Hasta)
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        // Paginamos de 20 en 20 ordenando por lo más reciente, igual que en prepagados
        $consumos = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('combustibles.consumos_operativos.index', compact('consumos', 'sedes'));
    }

    public function create()
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $vehiculos = Vehiculo::orderBy('placa')->get();
        // Traemos todos los tanques disponibles
        $tanques = Deposito::orderBy('serial')->get();
        
        // Traemos los tipos de combustible (si aplica para tu select)
        $tiposCombustible = TipoCombustible::orderBy('nombre')->get();

        return view('combustibles.consumos_operativos.create', compact('sedes', 'tanques', 'tiposCombustible','vehiculos'));
    }

    /**
     * Procesa el registro seguro del consumo operativo descontando de la fosa.
     */
    public function store(Request $request)
    {
        // 1. Reglas de validación al estilo de tu proyecto
        $rules = [
            'sede_id'             => 'required|exists:sedes,id',
            'deposito_id'         => 'required|exists:depositos,id',
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'cantidad_litros'     => 'required|numeric|min:0.01',
            'vehiculo_id'         => 'nullable|exists:vehiculos,id',
            'equipo_maquinaria'   => 'nullable|string|max:150',
            'observaciones'       => 'nullable|string|max:500',
        ];

        $messages = [
            'sede_id.required'             => 'Debe seleccionar la sede donde se realiza el consumo operativo.',
            'sede_id.exists'               => 'La sede seleccionada no es válida.',
            'deposito_id.required'         => 'Debe seleccionar el tanque (depósito) del cual se extraerá el combustible.',
            'deposito_id.exists'           => 'El tanque seleccionado no se encuentra registrado.',
            'tipo_combustible_id.required' => 'Debe seleccionar el tipo de combustible.',
            'tipo_combustible_id.exists'   => 'El tipo de combustible seleccionado no es válido.',
            'cantidad_litros.required'     => 'La cantidad de litros a consumir es obligatoria.',
            'cantidad_litros.numeric'      => 'La cantidad de litros debe ser un valor numérico.',
            'cantidad_litros.min'          => 'La cantidad de litros a registrar debe ser mayor a cero.',
            'vehiculo_id.exists'           => 'El vehículo seleccionado no se encuentra registrado.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        try {
            // Mapeamos los datos del formulario de manera limpia para el servicio
            $data = [
                'sede_id'             => (int) $request->input('sede_id'),
                'deposito_id'         => (int) $request->input('deposito_id'), // Adaptado a la variable del servicio
                'tipo_combustible_id' => (int) $request->input('tipo_combustible_id'),
                'cantidad_litros'     => (float) $request->input('cantidad_litros'),
                'vehiculo_id'         => $request->filled('vehiculo_id') ? (int) $request->input('vehiculo_id') : null,
                'equipo_maquinaria'   => $request->input('equipo_maquinaria'),
                'observaciones'       => $request->input('observaciones'),
                'user_id'             => auth()->id() ?? 1,
            ];

            // El servicio ya maneja su propia transacción interna DB::transaction()
            $this->combustibleService->registrarConsumoOperativo($data);

            Session::flash('success', '¡Consumo operativo registrado de manera exitosa! Se actualizó la fosa física.');
            return redirect()->route('combustibles.consumos_operativos.index');

        } catch (Exception $e) {
            // Si el servicio tira una excepción, hacemos rollback implícito por el DB::transaction del service
            Session::flash('error', 'Error al procesar el consumo: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
}