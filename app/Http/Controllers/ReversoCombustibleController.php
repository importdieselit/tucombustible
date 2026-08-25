<?php

namespace App\Http\Controllers;

use App\Models\ReversoCombustible;
use App\Models\TipoCombustible;
use App\Models\Sedes;
use App\Services\CombustibleService;
use Illuminate\Http\Request;
use Exception;

class ReversoCombustibleController extends Controller
{
    protected $combustibleService;

    public function __construct(CombustibleService $combustibleService)
    {
        $this->combustibleService = $combustibleService;
    }

    public function index(Request $request)
    {
        $query = ReversoCombustible::with(['sede', 'tipoCombustible', 'user']);

        if ($request->filled('sede_id')) {
            $query->where('sede_id', $request->sede_id);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $reversos = $query->latest()->paginate(15);
        $sedes = Sedes::orderBy('nombre')->get();

        return view('combustibles.reversos_combustibles.index', compact('reversos', 'sedes'));
    }

    public function create()
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $tiposCombustible = TipoCombustible::all();

        return view('combustibles.reversos_combustibles.create', compact('sedes', 'tiposCombustible'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sede_id'             => 'required|integer|exists:sedes,id',
            'tipo_combustible_id' => 'required|integer|exists:tipos_combustible,id',
            'cantidad_litros'     => 'required|numeric|gt:0',
            'motivo_reverso'      => 'nullable|string|max:255',
        ], [
            'sede_id.required'             => 'Debe seleccionar la sede correspondiente.',
            'tipo_combustible_id.required' => 'Debe seleccionar el tipo de combustible.',
            'cantidad_litros.required'     => 'Ingrese la cantidad de litros a reversar.',
            'cantidad_litros.gt'           => 'La cantidad de litros a reversar debe ser mayor a 0.',
        ]);

        try {
            $data['user_id'] = auth()->id();

            $reversoId = $this->combustibleService->registrarReversoCombustible($data);

            return redirect()->route('combustibles.reversos_combustibles.index')
                ->with('success', "Reverso #{$reversoId} registrado e ingresado equitativamente en los depósitos de la sede.");

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar el reverso: ' . $e->getMessage());
        }
    }
}