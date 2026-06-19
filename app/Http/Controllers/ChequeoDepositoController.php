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

        // Filtro histórico por sede si el usuario lo selecciona
        if ($request->filled('id_sede')) {
            $query->where('chequeos_depositos.id_sede', $request->id_sede);
        }

        $chequeos = $query->orderBy('chequeos_depositos.fecha', 'desc')
            ->orderBy('chequeos_depositos.created_at', 'desc')
            ->paginate(15);

        // Se obtienen las sedes para poder pintar el filtro en la vista index
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

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}