<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Viaje;
use App\Models\TabuladorViatico;
use App\Models\ViaticoViaje;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;


class ViajesController extends Controller
{

 public function index()
    {
        return view('viajes.index');
    }

    /**
     * Muestra el listado/historial de viajes.
     */
    public function list()
    {
        // Obtener el estado de filtro de la solicitud
        $status = request('status');

        $query = Viaje::with(['chofer']);

        // Aplicar filtro si existe
        if ($status) {
            $query->where('status', $status);
        }

        // Obtener viajes y paginarlos
        $viajes = $query->latest()->paginate(15);

        return view('viajes.list', compact('viajes'));
    }

    /**
     * Muestra el formulario para crear un nuevo viaje.
     */
    public function create()
    {
        // En un escenario real, aquí se cargan dinámicamente:
        $choferes = User::whereHasRole('chofer')->get(['id', 'name']);
        $vehiculos = Vehiculo::where('status', 'available')->get(['id', 'placa', 'modelo']);
        $ciudades_tabulador = TabuladorViatico::pluck('ciudad')->unique();

        return view('viajes.create', compact('choferes', 'vehiculos', 'ciudades_tabulador'));
    }

    /**
     * Muestra los detalles de un viaje específico.
     * Carga todas las relaciones necesarias para la vista de detalle.
     */
    public function show(Viaje $viaje)
    {
        // Cargar las relaciones necesarias para la vista show.blade.php
        $viaje->load([
            'chofer', // Necesario para mostrar el nombre
            'vehiculo', // Necesario para placa y modelo
            'viaticos.aprobador' // Necesario para mostrar quién ajustó cada línea de viático
        ]);
        
        return view('viajes.show', compact('viaje'));
    }


    /**
     * Genera el cuadro de viáticos para un viaje recién creado.
     * @param Viaje $viaje
     * @param TabuladorViatico $tabulador
     */
    private function generarCuadroViaticos(Viaje $viaje, TabuladorViatico $tabulador): void
    {
        $dias = $viaje->dias_estimados;
        $totalPersonas = 1 + $viaje->ayudantes_count + $viaje->custodia_count;

        // Lista de conceptos a generar (usando el Tabulador)
        $conceptos = [
            // Pagos Fijos
            ['concepto' => 'Pago Chofer', 'monto' => $tabulador->pago_chofer, 'cantidad' => 1, 'editable' => false],
            ['concepto' => 'Pago Ayudantes', 'monto' => $tabulador->pago_ayudante, 'cantidad' => $viaje->ayudantes_count, 'editable' => false],
            
            // Viáticos de Comida (por persona, por día)
            ['concepto' => 'Viático Desayuno', 'monto' => $tabulador->viatico_desayuno * $dias, 'cantidad' => $totalPersonas, 'editable' => true],
            ['concepto' => 'Viático Almuerzo', 'monto' => $tabulador->viatico_almuerzo * $dias, 'cantidad' => $totalPersonas, 'editable' => true],
            ['concepto' => 'Viático Cena', 'monto' => $tabulador->viatico_cena * $dias, 'cantidad' => $totalPersonas, 'editable' => true],
            
            // Pernocta y Peajes
            ['concepto' => 'Costo Pernocta', 'monto' => $tabulador->costo_pernocta * ($dias > 1 ? $dias - 1 : 0), 'cantidad' => $totalPersonas, 'editable' => true],
            ['concepto' => 'Peajes (Ida y Vuelta)', 'monto' => $tabulador->peajes_por_zona * 2, 'cantidad' => 1, 'editable' => true], // Asumimos peajes ida y vuelta
        ];

        // Guardar cada concepto en la tabla 'viaticos_viaje'
        foreach ($conceptos as $item) {
            if ($item['cantidad'] > 0) {
                ViaticoViaje::create([
                    'viaje_id' => $viaje->id,
                    'concepto' => $item['concepto'],
                    'monto_base' => $item['monto'],
                    'cantidad' => $item['cantidad'],
                    'es_editable' => $item['editable'],
                ]);
            }
        }
    }

    /**
     * Procesa la creación de un nuevo viaje.
     */
    public function store(Request $request)
    {
        // 1. Validar la entrada (omitiendo por brevedad)
        $request->validate([
            'destino_ciudad' => 'required|string',
            'chofer_id' => 'required|exists:users,id',
            // ... otras validaciones
        ]);

        // 2. Buscar tarifa en el Tabulador
        $tabulador = TabuladorViatico::where('ciudad', $request->destino_ciudad)->first();

        if (!$tabulador) {
            return back()->with('error', 'No se encontró una tarifa de viáticos para esa ciudad.');
        }

        // 3. Crear el Viaje
        $viaje = Viaje::create($request->all());

        // 4. Generar el Cuadro de Viáticos automáticamente
        $this->generarCuadroViaticos($viaje, $tabulador);
        
        return redirect()->route('viajes.viaticos.edit', $viaje->id)
                         ->with('success', 'Viaje creado y cuadro de viáticos generado.');
    }

    /**
     * Muestra la vista de edición del cuadro de viáticos para el Coordinador Administrativo.
     * @param int $viajeId
     */
    public function editViaticos($viajeId)
    {
        // 1. Autenticación y Autorización (Perfil Coordinador Administrativo)
        if (!Auth::user()->canAccess('edit', 8)) { // Asumiendo que usa Spatie/Roles o similar
            abort(403, 'Acceso no autorizado.');
        }

        $viaje = Viaje::with('viaticos')->findOrFail($viajeId);
        
        return view('viajes.edit_viaticos', compact('viaje'));
    }

    /**
     * Guarda los ajustes del cuadro de viáticos.
     */
    public function updateViaticos(Request $request, $viajeId)
    {
        // 1. Autorización (Mismo chequeo de perfil)
        if (!Auth::user()->canAccess('edit', 8)) {
            abort(403, 'Acceso no autorizado.');
        }

        $viaje = Viaje::findOrFail($viajeId);
        $adminId = Auth::id();
        $ajustes = $request->input('ajustes', []);

        foreach ($ajustes as $viaticoId => $montoAjustado) {
            $viatico = ViaticoViaje::where('id', $viaticoId)
                                    ->where('viaje_id', $viajeId)
                                    ->where('es_editable', true) // Solo actualiza los conceptos editables
                                    ->first();
            
            if ($viatico) {
                $viatico->monto_ajustado = $montoAjustado;
                $viatico->ajustado_por = $adminId;
                $viatico->save();
            }
        }

        // 2. Opcional: Actualizar el estado del viaje si se completaron los viáticos
        // $viaje->status = 'VIATICOS_APROBADOS';
        // $viaje->save();

        return back()->with('success', 'Cuadro de viáticos actualizado y guardado.');
    }
}
