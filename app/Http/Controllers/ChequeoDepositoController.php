<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
use App\Models\TipoCombustible;
use App\Models\Sedes;
use App\Repositories\ChequeoDepositoRepository;
use App\Services\AforoCalculoService;
use Illuminate\Http\Request;
use Exception;

class ChequeoDepositoController extends Controller
{
    protected $chequeoRepo;
    protected $aforoService;

    /**
     * Inyección directa de las clases concretas en el constructor.
     * Laravel las resuelve automáticamente sin necesidad de providers.
     */
    public function __construct(
        ChequeoDepositoRepository $chequeoRepo,
        AforoCalculoService $aforoService
    ) {
        $this->chequeoRepo = $chequeoRepo;
        $this->aforoService = $aforoService;
    }

    /**
     * Muestra el formulario de varillaje cargando los tanques de la sede.
     */
    public function create(Request $request)
    {
        // 1. Alistar todas las sedes operativas para el dropdown superior
        $sedes = Sedes::orderBy('nombre', 'asc')->get();

        // 2. Capturar los tanques de la sede seleccionada (si aplica)
        // Inicializamos como una colección vacía por si no han seleccionado nada todavía
        $depositos = collect(); 

        if ($request->filled('id_sede')) {
            // Buscamos los depósitos que pertenezcan a esa sede específica
            // Usamos ->get() para traerlos todos
            $depositos = Deposito::where('id_sede', $request->id_sede)
                                ->orderBy('serial', 'asc')
                                ->get();
        }

        // 3. Enviar todo al Blade que acabamos de acomodar
        return view('combustibles.chequeos_depositos.create', compact('sedes', 'depositos'));
    }

    /**
     * Procesa la medición de la vara, cubica los litros y persiste el lote.
     */
    /**
     * Procesa la medición de la vara, cubica los litros y persiste el lote.
     */
    public function store(Request $request)
    {
        // 1. Validación estricta del lote de varillaje
        $data = $request->validate([
            'id_sede' => 'required|exists:sedes,id',
            'turno' => 'required|string|in:Matutino,Nocturno',
            'fecha' => 'required|date|before_or_equal:today',
            'observaciones' => 'nullable|string|max:500',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_deposito' => 'required|exists:depositos,id',
            'detalles.*.centimetros_medidos' => 'required|numeric|min:0',
        ]);

        try {
            $detallesProcesados = [];

            // 2. Bucle de cubicación usando el AforoCalculoService
            foreach ($data['detalles'] as $detalle) {
                $deposito = Deposito::findOrFail($detalle['id_deposito']);

                $litrosCalculados = $this->aforoService->calcularLitros(
                    $deposito, 
                    (float) $detalle['centimetros_medidos']
                );

                $detallesProcesados[] = [
                    'id_deposito'         => $detalle['id_deposito'],
                    'centimetros_medidos' => $detalle['centimetros_medidos'],
                    'litros_calculados'   => $litrosCalculados,
                ];
            }

            // 3. Separamos los datos para cumplir con la firma exacta del repositorio
            $datosCabecera = [
                'id_sede'       => $data['id_sede'],
                'turno'         => $data['turno'],
                'fecha'         => $data['fecha'],
                'observaciones' => $data['observaciones'], // Incluido por si lo agregas al create de la cabecera
                'id_usuario'    => auth()->id() ?? 1,
            ];

            // 4. Llamada corregida al repositorio con sus dos parámetros correspondientes
            $this->chequeoRepo->guardarChequeoCompleto($datosCabecera, $detallesProcesados);

            return redirect()->route('chequeos.index')
                ->with('success', '¡Varillaje procesado y cubicado con éxito a nivel de milímetros!');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error crítico en la auditoría de varillaje: ' . $e->getMessage()]);
        }
    }
}