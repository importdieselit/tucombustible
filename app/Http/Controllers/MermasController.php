<?php

namespace App\Http\Controllers;

use App\Models\Sedes;
use App\Models\TipoCombustible;
use App\Services\CombustibleService;
use Illuminate\Http\Request;

class MermasController extends Controller
{
    protected $combustibleService;

    public function __construct(CombustibleService $combustibleService)
    {
        $this->combustibleService = $combustibleService;
    }

    /**
     * Muestra el histórico de mermas con filtros y totalización
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['sede_id', 'tipo_combustible_id', 'fecha_inicio', 'fecha_fin']);
        $mermas = $this->combustibleService->obtenerHistorialMermas($filtros);
        $totalLitrosMerma = $this->combustibleService->obtenerTotalLitrosMermas($filtros);
        $sedes = Sedes::all();
        $tipos = TipoCombustible::all();

        return view('combustibles.mermas.index', compact('mermas', 'totalLitrosMerma', 'sedes', 'tipos'));
    }
}