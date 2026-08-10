<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
use App\Models\TipoCombustible;
use App\Models\Sedes;
use App\Repositories\ChequeoDepositoRepository;
use App\Services\AforoCalculoService;
use App\Services\DepositoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ChequeoDepositoController extends Controller
{
    protected $chequeoRepo;
    protected $aforoService;
    protected $depositoService;

    public function __construct(ChequeoDepositoRepository $chequeoRepo, AforoCalculoService $aforoService, 
    DepositoService $depositoService)
    {
        $this->chequeoRepo = $chequeoRepo;
        $this->aforoService = $aforoService;
        $this->depositoService = $depositoService;
    }

    public function index(Request $request)
    {
        $query = DB::table('chequeos_depositos')
            ->join('sedes', 'chequeos_depositos.id_sede', '=', 'sedes.id')
            ->leftJoin('users', 'chequeos_depositos.id_usuario', '=', 'users.id')
            ->select('chequeos_depositos.*', 'sedes.nombre as sede_nombre', 'users.name as usuario_nombre');

        // Filtro por sede
        if ($request->filled('id_sede')) {
            $query->where('chequeos_depositos.id_sede', $request->id_sede);
        }

        // 🆕 FILTROS POR RANGO DE FECHAS
        if ($request->filled('fecha_inicio')) {
            $query->where('chequeos_depositos.fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->where('chequeos_depositos.fecha', '<=', $request->fecha_fin);
        }

        // Paginado estricto de 15 en 15
        $chequeos = $query->orderBy('chequeos_depositos.fecha', 'desc')
            ->orderBy('chequeos_depositos.created_at', 'desc')
            ->paginate(15);

        $sedes = Sedes::orderBy('nombre', 'asc')->get();

        return view('combustibles.chequeos_depositos.index', compact('chequeos', 'sedes'));
    }

    public function create(Request $request)
    {
        $sedes = Sedes::orderBy('nombre', 'asc')->get();
        $tiposCombustible = TipoCombustible::all();

        // 2. Capturar los tanques de la sede seleccionada (si aplica)
        // Inicializamos como una colección vacía por si no han seleccionado nada todavía
        $depositos = collect(); 

        if ($request->filled('id_sede')) {
            $depositos = $this->depositoService->obtenerDepositosConUltimaMedicion((int) $request->id_sede);
        }
        return view('combustibles.chequeos_depositos.create', compact('sedes', 'depositos', 'tiposCombustible'));
    }

    public function store(Request $request)
    {
        // 1. Validación estricta del lote de varillaje
        $data = $request->validate([
            'id_sede' => 'required|exists:sedes,id',
            'turno' => 'required|string|in:Matutino,Nocturno',
            'observaciones' => 'nullable|string|max:500',
            'confirmar_duplicado' => 'nullable|boolean',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_deposito' => 'required|exists:depositos,id',
            'detalles.*.id_tipos_combustible'  => 'required|exists:tipos_combustible,id',
            'detalles.*.centimetros_medidos' => 'required|numeric|min:0',
        ]);

        $data['fecha'] = now()->format('Y-m-d');
        $data['id_usuario'] = auth()->id() ?? 1;

        try {
            $this->depositoService->procesarChequeo($data);

            return redirect()->route('combustibles.chequeos_depositos.index')
                ->with('success', '¡Auditoría de varillaje registrada y cubicada correctamente!');

        } catch (Exception $e) {
            if ($e->getMessage() === 'DUPLICADO_DETECTADO') {
                $turno = strtolower($data['turno']);
                return redirect()->back()
                    ->withInput()
                    ->with('confirmar_duplicado_modal', true)
                    ->with('mensaje_duplicado', "Ya existe un chequeo de tanques registrado para el turno {$turno} el día de hoy. ¿Deseas registrar este nuevo chequeo de todas formas?");
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $detalles = DB::table('chequeos_depositos_detalles')
                ->join('depositos', 'chequeos_depositos_detalles.id_deposito', '=', 'depositos.id')
                ->join('tipos_combustible', 'chequeos_depositos_detalles.id_tipos_combustible', '=', 'tipos_combustible.id')
                ->where('chequeos_depositos_detalles.id_chequeo', $id)
                ->select(
                    'chequeos_depositos_detalles.*',
                    'depositos.serial as tanque_serial',
                    'depositos.forma as tanque_forma',
                    'depositos.capacidad_maxima as capacidad_max',
                    'tipos_combustible.nombre as combustible_nombre'
                )
                ->get();

            // Retornamos la sub-vista parcial inyectando los detalles corregidos
            return view('combustibles.chequeos_depositos.partials.detalle_tanques_tabla', compact('detalles'));

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}