<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Viaje;
use App\Models\TabuladorViatico;
use App\Models\ViaticoViaje;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Parametro;
use App\Models\Chofer;
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
        $choferes = Chofer::with('persona')->get();
        $vehiculos = Vehiculo::where('estatus', 1)->where('es_flota',true)->get(['id', 'placa', 'flota']);
        $destino = TabuladorViatico::pluck('destino')->unique();
        
        return view('viajes.create', compact('choferes', 'vehiculos', 'destino'));
    }

     public function assign($id)
    {
        $viaje = Viaje::findOrFail($id);
        
        // Cargar los recursos necesarios para la asignación
        // Asumiendo que Chofer::with('persona') es la forma correcta de cargar los choferes disponibles
        $choferes = Chofer::with('persona')->get(); 
        $vehiculos = Vehiculo::where('estatus', 1)->where('es_flota',true)->get(['id', 'placa', 'flota']);
        
        return view('viajes.assign', compact('viaje', 'choferes', 'vehiculos'));
    }

     public function processAssignment(Request $request, $id)
    {
        $viaje = Viaje::findOrFail($id);

        $validated = $request->validate([
            'chofer_id' => 'required|exists:choferes,id', // Asumo que el ID en Viaje apunta a choferes.id
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'ayudante' => 'nullable|integer|min:0',
            'custodia_count' => 'nullable|integer|min:0',
        ]);
        
        // Actualizar el viaje con los recursos asignados
        $viaje->update([
            'chofer_id' => $validated['chofer_id'],
            'vehiculo_id' => $validated['vehiculo_id'],
            'ayudante' => $validated['ayudante'] ?? 0,
            'custodia_count' => $validated['custodia_count'] ?? 0,
            'status' => 'PENDIENTE_VIATICOS', // Cambia el estado para el siguiente paso (Viáticos)
        ]);

        return redirect()->route('viaje.list')->with('success', 
            "Asignación de recursos completada para el viaje a {$viaje->destino_ciudad}. Estado: PENDIENTE DE VIÁTICOS.");
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
        $fecha_salida = $viaje->fecha_salida;
        $totalPersonas = 1  + $viaje->custodia_count;
        $parametros = Parametro::all()->keyBy('nombre')
            ->map(function($item) {
                return $item->valor;
            });
dd($tabulador->peajes);
        // Lista de conceptos a generar (usando el Tabulador)
        $conceptos = [
            // Pagos Fijos
            ['concepto' => 'Pago Chofer', 'monto' => $tabulador->pago_chofer, 'cantidad' => 1, 'editable' => false],
            ['concepto' => 'Pago Ayudantes', 'monto' => $tabulador->pago_ayudante, 'cantidad' => 1, 'editable' => false],
            
            // Viáticos de Comida (por persona, por día)
            ['concepto' => 'Viático Desayuno', 'monto' => $tabulador->viatico_desayuno , 'cantidad' => $totalPersonas, 'editable' => true],
            ['concepto' => 'Viático Almuerzo', 'monto' => $tabulador->viatico_almuerzo , 'cantidad' => $totalPersonas, 'editable' => true],
            ['concepto' => 'Viático Cena', 'monto' => $tabulador->viatico_cena, 'cantidad' => $totalPersonas, 'editable' => true],
            
            // Pernocta y Peajes
            ['concepto' => 'Costo Pernocta', 'monto' => $tabulador->costo_pernocta, 'cantidad' => $totalPersonas, 'editable' => true],
            ['concepto' => 'Peajes (Ida y Vuelta)', 'monto' => $tabulador->peajes * $parametros->peaje , 'cantidad' => 1, 'editable' => true], // Asumimos peajes ida y vuelta
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
            //'chofer_id' => 'required|exists:choferes,id',
            // ... otras validaciones
        ]);

        // 2. Buscar tarifa en el Tabulador
        $tabulador = TabuladorViatico::where('destino', $request->destino_ciudad)->first();

        if (!$tabulador) {
            return back()->with('error', 'No se encontró una tarifa de viáticos para esa ciudad.');
        }
        if($request->chofer_id == null){
            $request->merge(['status' => 'PENDIENTE_ASIGNACION']);
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
        if (!Auth::user()->canAccess('update', 8)) { // Asumiendo que usa Spatie/Roles o similar
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
        if (!Auth::user()->canAccess('update', 8)) {
            abort(403, 'Acceso no autorizado.');
        }

        $viaje = Viaje::findOrFail($viajeId);
        $viaje->status = 'COMPLETADO';
        $viaje->save();
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

        return redirect()->route('viaje.list');
    }

    
    public function reportsIndex()
    {
        // Cargar datos para los filtros
        $choferes = User::where('id_perfil', 4)->get(['id', 'name']);
        $ciudades = Viaje::distinct()->pluck('destino_ciudad');

        return view('viajes.reports_index', compact('choferes', 'ciudades'));
    }

    /**
     * Procesa la solicitud de reporte y devuelve los datos filtrados.
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'chofer_id' => 'nullable|exists:users,id',
            'destino_ciudad' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $query = Viaje::with(['chofer', 'viaticos']);

        // Aplicar filtros dinámicos
        if ($request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->fecha_fin) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }
        if ($request->chofer_id) {
            $query->where('chofer_id', $request->chofer_id);
        }
        if ($request->destino_ciudad) {
            $query->where('destino_ciudad', $request->destino_ciudad);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $viajes_reporte = $query->get();

        // Calcular el Gran Total de Viáticos
        $granTotalViaticos = $viajes_reporte->sum(function($viaje) {
            return $viaje->viaticos->sum(function($viatico) {
                // Usar el monto ajustado si existe, sino usar el monto base
                $monto = $viatico->monto_ajustado ?? $viatico->monto_base;
                return $monto * $viatico->cantidad;
            });
        });

        // Cargar datos para mantener los filtros
        $choferes = User::where('id_perfil', 4)->get(['id', 'name']);
        $ciudades = Viaje::distinct()->pluck('destino_ciudad');

        return view('viajes.reports_index', [
            'viajes_reporte' => $viajes_reporte,
            'granTotalViaticos' => $granTotalViaticos,
            'choferes' => $choferes,
            'ciudades' => $ciudades,
            'filtros' => $request->all(), // Devolver los filtros aplicados
        ]);
    }

      public function tabuladorIndex()
    {
        // Se carga todo el tabulador para la edición en línea
        $tabulador = TabuladorViatico::orderBy('id')->get();
        $parametros = Parametro::all()->keyBy('nombre')
            ->map(function($item) {
                return $item->valor;
            });
            
         // Convertir valores numéricos a float
        return view('viajes.tabulador', compact('tabulador', 'parametros'));
    }

    /**
     * Procesa la solicitud de edición en línea del Tabulador de Viáticos.
     */
    public function tabuladorUpdate(Request $request)
    {
        // Validar que la solicitud contenga los datos esperados
        $validated = $request->validate([
            'id' => 'required|exists:tabulador_viaticos,id',
            'field' => 'required|string', // Nombre del campo a editar
            'value' => 'nullable|numeric|min:0', // El nuevo valor
        ]);

        // Mapeo para seguridad: asegurar que solo se editen campos de tarifas
        $allowedFields = [
            'pago_chofer_ejecutivo', 'pago_chofer', 'pago_ayudante', 
            'viatico_desayuno', 'viatico_almuerzo', 'viatico_cena', 'costo_pernocta', 'peajes'
        ];

        $field = $validated['field'];
        $value = $validated['value'] ?? 0;

        if (!in_array($field, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Campo no editable.'], 403);
        }
        
        // Ejecutar la actualización
        try {
            $tabulador = TabuladorViatico::find($validated['id']);
            $tabulador->{$field} = $value;
            $tabulador->save();

            return response()->json(['success' => true, 'message' => 'Actualización exitosa.', 'new_value' => number_format($value, 2)], 200);

        } catch (\Exception $e) {
            \Log::error('Error actualizando tabulador: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error de servidor al guardar.'], 500);
        }
    }


     public function parametrosUpdate(Request $request)
    {
        // Validar que la solicitud contenga los datos esperados
        $validated = $request->validate([
            'id' => 'required|exists:parametros,id',
            'field' => 'required|string', // Nombre del campo a editar
            'value' => 'nullable|numeric|min:0', // El nuevo valor
        ]);

        // Mapeo para seguridad: asegurar que solo se editen campos de tarifas
        $allowedFields = ['peaje', 'desayuno', 'almuerzo'];
        $field = $validated['field'];
        $value = $validated['value'] ?? 0;

        if (!in_array($field, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Campo no editable.'], 403);
        }
        
        // Ejecutar la actualización
        try {
            $parametro = Parametro::find($validated['id']);
            $parametro->valor = $value;
            $parametro->save();

            return response()->json(['success' => true, 'message' => 'Actualización exitosa.', 'new_value' => number_format($value, 2)], 200);

        } catch (\Exception $e) {
            \Log::error('Error actualizando tabulador: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error de servidor al guardar.'], 500);
        }
    }

}
