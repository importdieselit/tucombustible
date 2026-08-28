<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VehiculoPrecargadoService;
use App\Models\Sedes;
use App\Models\Vehiculo;
use App\Models\TipoCombustible;
use Illuminate\Support\Facades\Session;
use Exception;

class VehiculoPrecargadoController extends Controller
{
    protected $precargaService;

    public function __construct(VehiculoPrecargadoService $precargaService)
    {
        $this->precargaService = $precargaService;
    }

    public function index(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $precargasActivas = $this->precargaService->obtenerActivas($request->id_sede);

        return view('combustibles.precargas_vehiculos.index', compact('precargasActivas', 'sedes'));
    }

    public function create(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $vehiculos = Vehiculo::whereIn('tipo', [1, 2, 5])->orderBy('placa')->get();
        $tiposCombustible = TipoCombustible::all();

        return view('combustibles.precargas_vehiculos.create', compact('sedes', 'vehiculos', 'tiposCombustible'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_sede'             => 'required|exists:sedes,id',
            'id_vehiculo'         => 'required|exists:vehiculos,id',
            'id_tipo_combustible' => 'required|exists:tipos_combustible,id',
            'cantidad_litros'     => 'required|numeric|gt:0',
            'observaciones'       => 'nullable|string|max:500',
        ], [
            'id_sede.required'             => 'Debe seleccionar la sede correspondiente.',
            'id_vehiculo.required'         => 'Debe seleccionar un vehículo.',
            'id_tipo_combustible.required' => 'Debe seleccionar el tipo de combustible.',
            'cantidad_litros.required'     => 'La cantidad de litros es obligatoria.',
            'cantidad_litros.gt'           => 'La cantidad de litros debe ser mayor a cero.',
        ]);

        $data['id_usuario'] = auth()->id();

        try {
            $this->precargaService->registrarPrecarga($data);

            Session::flash('success', '¡Precarga de vehículo registrada exitosamente!');
            return redirect()->route('combustibles.precargas_vehiculos.index');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function finalizar(int $id)
    {
        try {
            $this->precargaService->finalizarPrecarga($id);
            Session::flash('success', '¡Precarga marcada como despachada/utilizada exitosamente!');
        } catch (Exception $e) {
            Session::flash('error', 'Error al procesar la precarga: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function historico(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $historico = $this->precargaService->obtenerHistorico($request->id_sede);

        return view('combustibles.precargas_vehiculos.historico', compact('historico', 'sedes'));
    }
}