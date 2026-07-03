<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Sedes;
use App\Models\Deposito;
use App\Models\HistorialLlenadoCupoPrepagado;
use App\Services\DepositoService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class LlenadoCupoPrepagadoController extends Controller
{
    protected $llenadoService;

    public function __construct(DepositoService $llenadoService)
    {
        $this->llenadoService = $llenadoService;
    }

    /**
     * Muestra el historial de los llenados realizados bajo esta modalidad.
     */
    public function index(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        
        $llenados = HistorialLlenadoCupoPrepagado::with(['cliente', 'sede', 'deposito', 'tipoCombustible'])
            ->when($request->id_sede, function ($query, $id_sede) {
                return $query->where('id_sede', $id_sede);
            })
            ->latest()
            ->paginate(20);

        return view('combustibles.llenados_prepagados.index', compact('llenados', 'sedes'));
    }

    /**
     * Muestra el formulario para registrar un nuevo llenado de vehículo.
     */
    public function create()
    {
        $sedes = Sedes::orderBy('nombre')->get();
        // Traemos los clientes aprobados (status = 2)
        $clientes = Cliente::where('status', 2)->orderBy('nombre')->get();
        // Traemos únicamente los tanques que el administrador habilitó para esta modalidad
        $tanques = Deposito::where('llena_cupo_prepagado', 1)->orderBy('serial')->get();

        return view('combustibles.llenados_prepagados.create', compact('sedes', 'clientes', 'tanques'));
    }

    public function store(Request $request)
    {
        $messages = [
            'id_sede.required'     => 'Debe seleccionar la sede de Impordiesel donde se realiza el despacho.',
            'id_sede.exists'       => 'La sede seleccionada no es válida.',
            'cliente_id.required'  => 'Debe seleccionar un cliente de la lista.',
            'cliente_id.exists'    => 'El cliente seleccionado no es válido.',
            'id_deposito'          => 'Debe seleccionar el tanque del cual se realizó el llenado.',
            'id_deposito.exists'   => 'El tanque seleccionado no se encuentra registrado.',
            'litros.required'      => 'La cantidad de litros despachados es obligatoria.',
            'litros.numeric'       => 'Los litros deben ser un valor numérico.',
            'litros.min'           => 'La cantidad de litros a registrar debe ser mayor a cero.',
        ];

        $validator = Validator::make($request->all(), [
            'id_sede'     => 'required|exists:sedes,id',
            'cliente_id'  => 'required|exists:clientes,id',
            'id_deposito' => 'required|exists:depositos,id',
            'litros'      => 'required|numeric|min:0.01',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        try {
            $this->llenadoService->registrarLlenado(
                (int) $request->input('cliente_id'),
                (int) $request->input('id_deposito'),
                (float) $request->input('litros')
            );

            Session::flash('success', '¡Llenado de vehículo registrado y procesado exitosamente!');
            
            return redirect()->route('combustibles.llenados_prepagados.index');

        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
            
            return redirect()->back()->withInput();
        }
    }
}