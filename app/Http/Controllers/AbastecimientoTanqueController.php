<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AbastecimientoTanqueService;
use App\Models\Sedes;
use App\Models\Vehiculo;
use App\Models\Deposito;
use App\Models\TipoCombustible;
use Illuminate\Support\Facades\Session;
use Exception;

class AbastecimientoTanqueController extends Controller
{
    protected $abastecimientoService;

    public function __construct(AbastecimientoTanqueService $abastecimientoService)
    {
        $this->abastecimientoService = $abastecimientoService;
    }

    public function index(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $abastecimientos = $this->abastecimientoService->obtenerHistorico($request->id_sede);

        return view('combustibles.abastecimientos_tanques.index', compact('abastecimientos', 'sedes'));
    }

    public function create(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();

        // Solo vehículos de tipo 1, 2 y 5
        $vehiculos = Vehiculo::whereIn('tipo', [1, 2, 5])
            ->orderBy('placa')
            ->get();

        $tiposCombustible = TipoCombustible::all();

        $depositos = Deposito::when($request->id_sede, function ($query, $idSede) {
            return $query->where('id_sede', $idSede);
        })->get();

        return view('combustibles.abastecimientos_tanques.create', compact('sedes', 'vehiculos', 'depositos', 'tiposCombustible'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_sede'             => 'required|exists:sedes,id',
            'id_vehiculo'         => 'required|exists:vehiculos,id',
            'id_deposito'         => 'required|exists:depositos,id',
            'id_tipo_combustible' => 'required|exists:tipos_combustible,id',
            'cantidad_litros'     => 'required|numeric|gt:0',
            'observaciones'       => 'nullable|string|max:500',
        ], [
            'id_sede.required'             => 'Debe seleccionar una sede operativa.',
            'id_vehiculo.required'         => 'Debe seleccionar el vehículo origen.',
            'id_deposito.required'         => 'Debe seleccionar el depósito destino.',
            'id_tipo_combustible.required' => 'Debe seleccionar el tipo de combustible.',
            'cantidad_litros.required'     => 'La cantidad en litros es obligatoria.',
            'cantidad_litros.gt'           => 'La cantidad a trasegar debe ser mayor a cero.',
        ]);

        $data['id_usuario'] = auth()->id();

        try {
            $this->abastecimientoService->registrarAbastecimiento($data);

            Session::flash('success', '¡Abastecimiento de tanque registrado exitosamente!');
            return redirect()->route('combustibles.abastecimientos_tanques.index');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}