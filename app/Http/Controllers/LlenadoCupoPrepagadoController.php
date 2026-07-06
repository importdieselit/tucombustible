<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cliente;
use App\Models\Sedes;
use App\Models\Deposito;
use App\Models\HistorialLlenadoCupoPrepagado;
use App\Models\ChoferCliente;
use App\Models\PlacaVehiculo;
use App\Repositories\HistorialLlenadoRepository;
use App\Services\DepositoService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class LlenadoCupoPrepagadoController extends Controller
{
    protected $llenadoService;
    protected $historialRepo;

    public function __construct(DepositoService $llenadoService, HistorialLlenadoRepository $historialRepo)
    {
        $this->llenadoService = $llenadoService;
        $this->historialRepo = $historialRepo;
    }

    /**
     * Muestra el historial de los llenados realizados con filtros dinámicos en tiempo real.
     */
    public function index(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        
        // Iniciamos el query con sus relaciones para evitar el problema de consultas N+1
        $query = HistorialLlenadoCupoPrepagado::with(['cliente', 'sede', 'deposito', 'chofer', 'placa']);

        // 1. Filtro Cruzado: Por Nombre o RIF del cliente
        if ($request->filled('search_cliente')) {
            $search = $request->input('search_cliente');
            $query->whereHas('cliente', function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('rif', 'LIKE', "%{$search}%");
            });
        }

        // 2. Filtro Cruzado: Por Sede Emisora
        if ($request->filled('id_sede')) {
            $query->where('id_sede', $request->input('id_sede'));
        }

        // 3. Filtro Cruzado: Rango de fechas (Desde / Hasta)
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        // Carga paginada ordenando por lo más reciente
        $llenados = $query->orderBy('created_at', 'desc')->paginate(20);

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
        $choferes = ChoferCliente::where('activo', 1)->get(); 
        $placas = PlacaVehiculo::where('activo', 1)->get();

        return view('combustibles.llenados_prepagados.create', compact('sedes', 'clientes', 'tanques', 'choferes', 'placas'));
    }

    /**
     * Procesa la inserción segura del llenado restando cupo e inventario físico.
     */
    public function store(Request $request)
    {
        // 1. Reglas base manteniendo TUS inputs originales del formulario
        $rules = [
            'id_sede'           => 'required|exists:sedes,id',
            'cliente_id'        => 'required|exists:clientes,id', // Tu input original
            'id_deposito'       => 'required|exists:depositos,id',
            'litros'            => 'required|numeric|min:0.01',
        ];

        $messages = [
            'id_sede.required'           => 'Debe seleccionar la sede de Impordiesel donde se realiza el despacho.',
            'id_sede.exists'             => 'La sede seleccionada no es válida.',
            'cliente_id.required'        => 'Debe seleccionar un cliente de la lista.',
            'cliente_id.exists'          => 'El cliente seleccionado no es válido.',
            'id_deposito.required'       => 'Debe seleccionar el tanque del cual se realizó el llenado.',
            'id_deposito.exists'         => 'El tanque seleccionado no se encuentra registrado.',
            'litros.required'            => 'La cantidad de litros despachados es obligatoria.',
            'litros.numeric'             => 'Los litros deben ser un valor numérico.',
            'litros.min'                 => 'La cantidad de litros a registrar debe ser mayor a cero.',
        ];

        // 2. Validación en la tabla 'choferes_clientes'
        if ($request->input('chofer_cliente_id') === 'nuevo') {
            $rules['nuevo_chofer_nombre'] = 'required|string|max:255';
            // Ajuste: Validación estricta numérica y max 8 dígitos
            $rules['nuevo_chofer_cedula'] = 'required|numeric|digits_between:5,8|unique:choferes_clientes,cedula'; 
            
            $messages['nuevo_chofer_nombre.required'] = 'El nombre del nuevo chofer es obligatorio.';
            $messages['nuevo_chofer_cedula.required'] = 'La cédula del nuevo chofer es obligatoria.';
            $messages['nuevo_chofer_cedula.numeric']  = 'La cédula debe contener solo números.';
            $messages['nuevo_chofer_cedula.digits_between'] = 'La cédula no puede superar los 8 dígitos.';
            $messages['nuevo_chofer_cedula.unique']   = 'Este chofer ya se encuentra registrado en la tabla choferes_clientes.';
        } else {
            $rules['chofer_cliente_id'] = 'required|exists:choferes_clientes,id';
            $messages['chofer_cliente_id.required'] = 'Debe seleccionar un chofer autorizado o registrar uno nuevo.';
            $messages['chofer_cliente_id.exists']   = 'El chofer seleccionado no pertenece a un registro válido.';
        }

        // 3. Validación en la tabla 'placas_vehiculos'
        if ($request->input('placa_vehiculo_id') === 'nuevo') {
            $rules['nueva_placa_numero'] = 'required|string|max:15|unique:placas_vehiculos,placa'; 
            
            $messages['nueva_placa_numero.required'] = 'El número de la nueva placa es obligatorio.';
            $messages['nueva_placa_numero.unique']   = 'Esta placa ya se encuentra registrada en la tabla placas_vehiculos.';
        } else {
            $rules['placa_vehiculo_id'] = 'required|exists:placas_vehiculos,id'; 
            $messages['placa_vehiculo_id.required'] = 'Debe seleccionar la placa del vehículo o registrar una nueva.';
            $messages['placa_vehiculo_id.exists']   = 'La placa seleccionada no es válida.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                            ->withErrors($validator)
                            ->withInput();
        }

        try {
            DB::beginTransaction();

            $choferId = $request->input('chofer_cliente_id');
            $placaId = $request->input('placa_vehiculo_id');

            // 4. Inserción limpia en la tabla 'choferes_clientes'
            if ($choferId === 'nuevo') {
                $nuevoChofer = ChoferCliente::create([
                    'cliente_id'      => $request->input('cliente_id'), // <-- CORREGIDO: cliente_id
                    'nombre_completo' => mb_strtoupper($request->input('nuevo_chofer_nombre'), 'UTF-8'), 
                    'cedula'          => $request->input('nuevo_chofer_cedula'),
                    'activo'          => 1 
                ]);
                $choferId = $nuevoChofer->id;
            }

            // 5. Inserción limpia en la tabla 'placas_vehiculos'
            if ($placaId === 'nuevo') {
                $nuevaPlaca = PlacaVehiculo::create([
                    'cliente_id' => $request->input('cliente_id'), // <-- CORREGIDO: cliente_id
                    'placa'      => mb_strtoupper($request->input('nueva_placa_numero'), 'UTF-8'), 
                    'activo'     => 1 
                ]);
                $placaId = $nuevaPlaca->id;
            }

            // 6. Envío seguro de parámetros al servicio del tanque
            $this->llenadoService->registrarLlenado(
                (int) $request->input('cliente_id'),
                (int) $request->input('id_deposito'),
                (float) $request->input('litros'),
                (int) $choferId,
                (int) $placaId
            );

            DB::commit();

            Session::flash('success', '¡Llenado procesado exitosamente! Se guardaron los nuevos registros asociados.');
            return redirect()->route('combustibles.llenados_prepagados.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Session::flash('error', $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
}