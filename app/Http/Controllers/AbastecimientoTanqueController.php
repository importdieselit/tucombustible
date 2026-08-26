<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AbastecimientoTanqueService;
use App\Models\Sedes;
use App\Models\VehiculoPrecargado;
use App\Models\CompraCombustible;
use App\Models\AbastecimientoTanque;
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

        // Precargas activas (estatus = 0)
        $precargas = VehiculoPrecargado::with(['vehiculo', 'tipoCombustible', 'sede'])
            ->where('estatus', 0)
            ->get();

        // IDs de compras que ya fueron utilizadas en un abastecimiento previo
        $comprasUtilizadas = AbastecimientoTanque::whereNotNull('id_compra_combustible')
            ->pluck('id_compra_combustible');

        // Solo compras disponibles (no utilizadas en abastecimientos)
        $compras = CompraCombustible::with(['proveedor'])
            ->whereNotIn('id', $comprasUtilizadas)
            ->where('fecha', '>=', now()->subDays(7))
            ->orderByDesc('fecha')
            ->get();

        return view('combustibles.abastecimientos_tanques.create', compact('sedes', 'precargas', 'compras'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_sede'               => 'required|exists:sedes,id',
            'tipo_origen'           => 'required|in:precarga,compra',
            'id_precarga_origen'    => 'required_if:tipo_origen,precarga|nullable|exists:vehiculos_precargados,id',
            'id_compra_combustible' => 'required_if:tipo_origen,compra|nullable|exists:compras_combustible,id',
            'cantidad_litros'       => 'required|numeric|gt:0',
            'observaciones'         => 'nullable|string|max:500',
        ], [
            'id_sede.required'              => 'Debe seleccionar una sede operativa.',
            'tipo_origen.required'          => 'Debe seleccionar el tipo de origen del abastecimiento.',
            'id_precarga_origen.required_if' => 'Debe seleccionar un vehículo precargado origen.',
            'id_compra_combustible.required_if' => 'Debe seleccionar una compra de combustible origen.',
            'cantidad_litros.required'      => 'La cantidad en litros es obligatoria.',
            'cantidad_litros.gt'            => 'La cantidad a trasegar debe ser mayor a cero.',
        ]);

        $data['id_usuario'] = auth()->id();

        try {
            $this->abastecimientoService->registrarAbastecimiento($data);

            Session::flash('success', '¡Abastecimiento de tanque registrado y distribuido exitosamente!');
            return redirect()->route('combustibles.abastecimientos_tanques.index');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}