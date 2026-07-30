<?php

namespace App\Http\Controllers;

use App\Models\ReversoCombustible;
use App\Models\SaldoPendienteCliente;
use App\Models\Cliente;
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

    /**
     * Historial de Reversos Registrados
     */
    public function index(Request $request)
    {
        $query = ReversoCombustible::with(['sede', 'cliente', 'tipoCombustible', 'user']);

        if ($request->filled('sede_id')) {
            $query->where('sede_id', $request->sede_id);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $reversos = $query->latest()->paginate(15);
        $sedes = Sedes::orderBy('nombre')->get();
        $clientes = Cliente::orderBy('nombre')->get();

        return view('combustibles.reversos_combustibles.index', compact('reversos', 'sedes', 'clientes'));
    }

    /**
     * Formulario de Registro de Reverso
     */
    public function create()
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $clientes = Cliente::orderBy('nombre')->get();
        $tiposCombustible = TipoCombustible::all();

        return view('combustibles.reversos_combustibles.create', compact('sedes', 'clientes', 'tiposCombustible'));
    }

    /**
     * Procesar y Guardar Reverso
     */
    public function store(Request $request)
    {
        // Validación interna directamente en el controlador
        $data = $request->validate([
            'sede_id'             => 'required|integer|exists:sedes,id',
            'cliente_id'          => 'required|integer|exists:clientes,id',
            'tipo_combustible_id' => 'required|integer|exists:tipos_combustible,id',
            'cantidad_litros'     => 'required|numeric|gt:0',
            'motivo_reverso'      => 'nullable|string|max:255',
        ], [
            'sede_id.required'             => 'Debe seleccionar la sede correspondiente.',
            'cliente_id.required'          => 'Debe seleccionar un cliente.',
            'tipo_combustible_id.required' => 'Debe seleccionar el tipo de combustible.',
            'cantidad_litros.required'     => 'Ingrese la cantidad de litros a reversar.',
            'cantidad_litros.gt'           => 'La cantidad de litros a reversar debe ser mayor a 0.',
        ]);

        try {
            $data['user_id'] = auth()->id();

            $reversoId = $this->combustibleService->registrarReversoCombustible($data);

            return redirect()->route('combustibles.reversos_combustibles.index')
                ->with('success', "Reverso de Combustible #{$reversoId} registrado exitosamente. Se ha generado el saldo a favor para el cliente.");

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar el reverso: ' . $e->getMessage());
        }
    }

    /**
     * Consulta de Saldos Pendientes / A Favor de Clientes
     */
    public function show(Request $request)
    {
        $clientes = Cliente::orderBy('nombre')->get();

        // 1. SALDOS ACTUALES DISPONIBLES (Consolidado Neto por Cliente y Producto)
        $saldosConsolidadosQuery = SaldoPendienteCliente::selectRaw('
                cliente_id,
                tipo_combustible_id,
                SUM(CASE WHEN tipo_accion = "acumulado" THEN cantidad_litros ELSE 0 END) as total_acumulado,
                SUM(CASE WHEN tipo_accion = "consumido" THEN cantidad_litros ELSE 0 END) as total_consumido,
                SUM(CASE WHEN tipo_accion = "acumulado" THEN cantidad_litros ELSE -cantidad_litros END) as saldo_neto
            ')
            ->groupBy('cliente_id', 'tipo_combustible_id')
            ->having('saldo_neto', '>', 0);

        if ($request->filled('cliente_id')) {
            $saldosConsolidadosQuery->where('cliente_id', $request->cliente_id);
        }

        $saldosConsolidados = $saldosConsolidadosQuery->with(['cliente', 'tipoCombustible'])
            ->orderBy('saldo_neto', 'desc')
            ->paginate(10, ['*'], 'page_consolidados');

        // 2. HISTORIAL DE MOVIMIENTOS (TABLA ORIGINAL DE AUDITORÍA)
        $queryMovimientos = SaldoPendienteCliente::with(['cliente', 'tipoCombustible', 'user']);

        if ($request->filled('cliente_id')) {
            $queryMovimientos->where('cliente_id', $request->cliente_id);
        }

        $saldos = $queryMovimientos->latest()->paginate(15, ['*'], 'page_movimientos');

        return view('combustibles.reversos_combustibles.show', compact('saldos', 'saldosConsolidados', 'clientes'));
    }
}