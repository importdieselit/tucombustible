<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sedes;
use App\Models\TipoCombustible;
use App\Services\DepositoService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class DepositoController extends Controller
{
    protected $depositoService;

    // Constructor limpio, inyectando únicamente nuestro nuevo servicio
    public function __construct(DepositoService $depositoService)
    {
        $this->depositoService = $depositoService;
    }

    public function create()
    {
        // Consultamos los maestros necesarios para los selectores del formulario
        $sedes = Sedes::all(); 
        $tiposCombustible = TipoCombustible::all();

        // Retornamos la vista ubicada en la nueva carpeta con "s"
        return view('combustibles.depositos.create', compact('sedes', 'tiposCombustible'));
    }

    /**
     * El método index() por si quieres dejar el listado listo para después
     */
    public function index()
    {
        return view('combustibles.depositos.index');
    }

    /**
     * Procesa el formulario de creación de un tanque geométrico.
     */
    public function store(Request $request)
    {
        // Validación nativa en el controlador con las reglas exigidas
        $validator = Validator::make($request->all(), [
            'serial' => 'required|string|max:255|unique:depositos,serial',
            'id_sede' => 'required|exists:sedes,id', 
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'capacidad_maxima' => 'required|numeric|min:0',
            'forma' => 'required|in:CH,CV,OH,OV,R,C,E',

            // Reglas de validación condicionales expandidas para el ecosistema completo
            'diametro' => 'required_if:forma,CH,CV,OH,C,E|nullable|numeric|min:0',
            'longitud' => 'required_if:forma,CH,R,OH,OV|nullable|numeric|min:0',
            'ancho'    => 'required_if:forma,R,OH,OV|nullable|numeric|min:0',
            'alto'     => 'required_if:forma,R,CV,OV|nullable|numeric|min:0',
            
            'producto_nombre_legacy' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput()
                             ->with('error', 'Error en la geometría del tanque. Verifica las dimensiones obligatorias.');
        }

        // Ejecutamos la acción a través del servicio
        $this->depositoService->registrarDeposito($validator->validated());

        Session::flash('success', '¡Tanque de infraestructura registrado exitosamente!');
        
        // Redirección apuntando al nuevo namespace de rutas que estás armando
        return redirect()->route('combustibles.dashboard');
    }
}