<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Viaje;
use App\Models\TabuladorViatico;
use App\Services\FcmNotificationService;
use App\Models\ViaticoViaje;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Parametro;
use App\Models\Chofer;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\DespachoViaje;
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

        $query = Viaje::with(['chofer','cliente']);

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
        $vehiculos = Vehiculo::where('es_flota',true)->get(['id', 'placa', 'flota']);
        $destino = TabuladorViatico::pluck('destino')->unique();
        $clientes = Cliente::where('status',1)->get(['id','nombre','alias']);
        
        return view('viajes.create', compact('choferes', 'vehiculos', 'destino', 'clientes'));
    }

     public function assign($id)
    {
        $viaje = Viaje::findOrFail($id);
        
        // Cargar los recursos necesarios para la asignación
        // Asumiendo que Chofer::with('persona') es la forma correcta de cargar los choferes disponibles
        $choferes = Chofer::with('persona')->get(); 
        $vehiculos = Vehiculo::where('es_flota',true)->where('estatus', 1)->get(['id', 'placa', 'flota']);
        $clientes = Cliente::where('status',1)->get(['id','nombre']);

        //  if($viaje->chofer_id != null){
        //     return redirect()->route('viajes.list')->with('info', 'El viaje ya tiene chofer asignado.');
        //  }
        
        //  if($viaje->vehiculo_id != null){
        //     return redirect()->route('viajes.list')->with('info', 'El viaje ya tiene vehículo asignado.');
        //  }
        
        return view('viajes.assign', compact('viaje', 'choferes', 'vehiculos', 'clientes'));
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
            'status' => 'COMPLETADO', // Cambia el estado para el siguiente paso (Viáticos)
        ]);

        return redirect()->route('viajes.list')->with('success', 
            "Asignación de recursos completada para el viaje a {$viaje->destino_ciudad}. Estado: PENDIENTE DE VIÁTICOS.");
    }

    /**
     * Muestra los detalles de un viaje específico.
     * Carga todas las relaciones necesarias para la vista de detalle.
     */
    public function show(Viaje $viaje)
    {
         // Eager load todas las relaciones necesarias para la vista de detalle
        $viaje->load(['chofer.persona', 'ayudante_chofer.persona', 'vehiculo', 'despachos.cliente', 'viaticos.ajustadoPor']);   
        return view('viajes.show', compact('viaje'));
    }


    /**
     * Genera el cuadro de viáticos para un viaje recién creado.
     * @param Viaje $viaje
     * @param TabuladorViatico $tabulador
     */
    private function generarCuadroViaticos(Viaje $viaje, TabuladorViatico $tabulador,$cantidadDespachos): void
    {
        $fecha_salida = $viaje->fecha_salida;
        $viatico=false;
        $totalPersonas = 1  + $viaje->custodia_count;
        $parametros = Parametro::all()->keyBy('nombre')
            ->map(function($item) {
                return $item->valor;
            });
            //dd($parametros);
            // Lista de conceptos a generar (usando el Tabulador)
        $conceptos = [
            // Pagos Fijos
            ['concepto' => 'Pago Chofer', 'monto' => $tabulador->pago_chofer * $cantidadDespachos, 'cantidad' => $cantidadDespachos, 'editable' => false],
            ['concepto' => 'Pago Ayudantes', 'monto' => $tabulador->pago_ayudante * $cantidadDespachos, 'cantidad' => $cantidadDespachos, 'editable' => false],
        ];    
            // Viáticos de Comida (por persona, por día)
            if($tabulador->viatico_desayuno > 0 ){
                $viatico=true;
                $conceptos[] = ['concepto' => 'Viático Desayuno', 'monto' => $tabulador->viatico_desayuno , 'cantidad' => $totalPersonas, 'editable' => true];
            }
            if($tabulador->viatico_almuerzo > 0 ){
                $viatico=true;
                $conceptos[] = ['concepto' => 'Viático Almuerzo', 'monto' => $tabulador->viatico_almuerzo , 'cantidad' => $totalPersonas, 'editable' => true];
            }
            if($tabulador->viatico_cena > 0 ){
                $viatico=true;
                $conceptos[] = ['concepto' => 'Viático Cena', 'monto' => $tabulador->viatico_cena, 'cantidad' => $totalPersonas, 'editable' => true];
            }
            // Pernocta y Peajes
            if($tabulador->costo_pernocta > 0 ){
                $viatico=true;
                $conceptos[] =['concepto' => 'Costo Pernocta', 'monto' => $tabulador->costo_pernocta, 'cantidad' => $totalPersonas, 'editable' => true];
            }
            if($tabulador->peajes > 0 ) {
                $conceptos[] =['concepto' => 'Peajes (Ida y Vuelta)', 'monto' => $tabulador->peajes * $parametros['peaje'] , 'cantidad' => 1, 'editable' => true];
            }
            if($viatico==true){
                $viaje->has_viatico=true;
                $viaje->save();
            };
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
        // 1. Validación de la entrada
        $request->validate([
            'destino_ciudad' => 'required|string',
            'fecha_salida' => 'required|date',
            // El campo 'despachos' debe ser un array y tener al menos una entrada.
            'despachos' => 'required|array|min:1', 
            // Reglas para cada elemento dentro del array 'despachos'
            'despachos.*.litros' => 'required|numeric|min:0.01',
            // Cada despacho debe tener O cliente_id O otro_cliente.
            'despachos.*.cliente_id' => 'nullable|exists:clientes,id',
            'despachos.*.otro_cliente' => 'nullable|string|max:255',
        ], [
            'despachos.required' => 'Debe agregar al menos un despacho de cliente.',
            'despachos.*.litros.required' => 'La cantidad de litros es requerida para cada despacho.',
            'despachos.*.litros.min' => 'La cantidad de litros debe ser mayor a cero.',
        ]);

        // Validación de exclusividad: Asegurar que se haya llenado Cliente ID o Otro Cliente
        foreach ($request->despachos as $index => $despacho) {
            if (empty($despacho['cliente_id']) && empty($despacho['otro_cliente'])) {
                return back()->withInput()->withErrors([
                    "despachos.$index.cliente_id" => 'Debe seleccionar un cliente o especificar "Otro Cliente".',
                    "despachos.$index.otro_cliente" => 'Debe seleccionar un cliente o especificar "Otro Cliente".',
                ]);
            }
        }

        // 2. Buscar tarifa en el Tabulador para el destino principal
        $tabulador = TabuladorViatico::where('destino', $request->destino_ciudad)->first();

        if (!$tabulador) {
            return back()->withInput()->with('error', 'No se encontró una tarifa de viáticos para esa ciudad.');
        }

        try {
            DB::beginTransaction();
            $status = 'PENDIENTE_ASIGNACION';
            if($request->chofer_id != null && $request->vehiculo_id != null){
                $status = 'PROGRAMADO';
            }
            //
            // 3. Crear el Viaje ÚNICO
            $viaje = Viaje::create([
                'destino_ciudad' => $request->destino_ciudad,
                'fecha_salida' => $request->fecha_salida,
                'status' => $status,
                'chofer_id' => $request->chofer_id ?? 0,
                'vehiculo_id' => $request->vehiculo_id ?? 0,
                'ayudante' => $request->ayudante ?? 0,
                'otro_chofer' => $request->otro_chofer_id ?? null,
                'otro_vehiculo' => $request->otro_vehiculo_id ?? null,
                'otro_ayudante' => $request->otro_ayudante_id ?? null
            ]);

            // 4. Crear los registros de DespachoViaje
            $cantidadDespachos = count($request->despachos);
            $totalLitros = 0;
            foreach ($request->despachos as $despachoData) {
                DespachoViaje::create([
                    'viaje_id' => $viaje->id,
                    'cliente_id' => $despachoData['cliente_id'] ?? null,
                    'otro_cliente' => $despachoData['otro_cliente'] ?? null,
                    'litros' => $despachoData['litros'],
                ]);
                $totalLitros += $despachoData['litros'];
            }
            $data=[
                    'viaje_id' => $viaje->id,
                    'cliente_id' => $despachoData['cliente_id'] ?? null,
                    'otro_cliente' => $despachoData['otro_cliente'] ?? null,
                    'litros' => $despachoData['litros'],
                    'total_litros' => $totalLitros
                ];
                
                 FcmNotificationService::enviarNotification(
                        "Nuevo viaje creado a {$viaje->destino_ciudad} con {$cantidadDespachos} despachos. Total Litros: {$totalLitros}",  
                        "Nuevo viaje creado a {$viaje->destino_ciudad} con {$cantidadDespachos} despachos. Total Litros: {$totalLitros}",
                        $data
                    );
            // 5. Generar el Cuadro de Viáticos automáticamente (con correcciones y desglose)
            $this->generarCuadroViaticos($viaje, $tabulador, $cantidadDespachos);
            
            DB::commit();

            return redirect()->route('viajes.viaticos.edit', $viaje->id)
                             ->with('success', 'Viaje creado con múltiples despachos y cuadro de viáticos generado. Pendiente de asignación de Chofer y Vehículo.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            // El log ahora mostrará el mensaje de error completo
            Log::error('Error creando Viaje con Despachos: ' . $e->getMessage()); 
            return back()->withInput()->with('error', 'Error del servidor al crear el viaje. Intente nuevamente. Detalles: ' . $e->getMessage());
        }
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
        if($viaje->chofer_id != null){
            $viaje->status = 'COMPLETADO';
            $viaje->save();
        }
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

        return redirect()->route('viajes.list');
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
            Log::error('Error actualizando tabulador: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error de servidor al guardar.'], 500);
        }
    }

     public function resumenProgramacion($id = null)
    {
        $user=Auth::user()->with('persona')->get();
        
        // 1. Inicializa la query builder
        $query = Viaje::with(['chofer.persona', 'ayudante_chofer.persona', 'vehiculo', 'despachos.cliente', 'viaticos']);

        // Excluir cancelados
        $query->where('status', '!=', 'CANCELADO');

        // 2. Aplica la lógica condicional de tu preferencia
        if ($id != null) {
            // Filtrar por ID específico
            $query->where('id', $id);
        } else {
            // Filtrar por fecha de salida igual a hoy (hora de Caracas)
            $query->whereDate('fecha_salida', Carbon::now()->toDateString());
        }
        
        // 3. Ejecuta la consulta
        $viajes = $query->get();
       
        // Calcular el total de viáticos presupuestados/pendientes
        $totalViaticosPresupuestados = $viajes->flatMap(function($viaje) {
            return $viaje->viaticos->pluck('monto');
        })->sum();
        
        return view('viajes.resumen_programacion', compact('viajes', 'totalViaticosPresupuestados','user'));
    }

    public function edit($id)
{
    $viaje = Viaje::findOrFail($id);
         $choferes = Chofer::with('persona')->get();
        $vehiculos = Vehiculo::where('estatus', 1)->where('es_flota',true)->get(['id', 'placa', 'flota']);
        $destino = TabuladorViatico::pluck('destino')->unique();
        $clientes = Cliente::where('status',1)->get(['id','nombre','alias']);
        
        
    return view('viajes.edit', compact('viaje','choferes','vehiculos','destino','clientes'));
}

/**
 * Actualiza el viaje especificado en la base de datos.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id
 * @return \Illuminate\Http\RedirectResponse
 */
public function update(Request $request, $id)
{
    // 1. Validar los datos del formulario
    $request->validate([
        'destino_ciudad' => 'required|string|max:100',
        'fecha_salida' => 'required|date',
        'status' => 'required|in:PENDIENTE_ASIGNACION,PENDIENTE_VIATICOS,EN_CURSO,FINALIZADO,CANCELADO',
        // Añadir reglas de validación para Chofer, Vehículo, etc., si se editan
    ]);

    // 2. Encontrar el viaje y actualizar
    $viaje = Viaje::findOrFail($id);
    
    $viaje->destino_ciudad = $request->input('destino_ciudad');
    $viaje->fecha_salida = $request->input('fecha_salida');
    $viaje->status = $request->input('status');
    // $viaje->chofer_id = $request->input('chofer_id'); // Ejemplo de campo adicional
    
    $viaje->save();

    // 3. Redirigir de vuelta a la vista de detalle con un mensaje de éxito
    return redirect()->route('viaje.show', $viaje->id)
                     ->with('success', '¡El viaje ha sido actualizado con éxito!');
}

    // En ViajesController.php
    public function calendar()
    {

       // 1. Consulta con las relaciones necesarias para el calendario
        $query = Viaje::with(['chofer.persona', 'ayudante_chofer.persona', 'vehiculo', 'despachos.cliente', 'viaticos']);
        $query->where('status', '!=', 'CANCELADO');
        
        $viajes = $query->get();
        
        // 2. Transformación de la colección al formato JavaScript requerido
        $viajesData = $viajes->map(function ($viaje) {
            
            // Lógica de duración: 1 día por defecto, 2 días si hay viático con pernocta
            $duracionDias = 1;
            
            // Buscar si existe al menos un viático con pernocta (asumo que pernocta es un campo numérico/booleano en ViaticoViaje)
            // Asumo que la relación es $viaje->viaticos y que el campo de pernocta es 'monto_pernocta' o similar.
            // Si el campo es 'monto_pernocta' (monto > 0) o 'pernocta' (booleano true), se aplica la duración de 2 días.
            // Ajusta el campo y la condición si 'pernocta' tiene un nombre diferente en tu modelo ViaticoViaje.
            $tienePernocta = $viaje->viaticos->contains(function ($viatico) {
                // Ajusta 'monto_pernocta' al nombre real del campo en ViaticoViaje
                return isset($viatico->monto_pernocta) && $viatico->monto_pernocta > 0;
            });
            
            if ($tienePernocta) {
                $duracionDias = 2;
            }

            
            // Calcular fecha final (FullCalendar usa end de forma NO inclusiva)
            // Añadir $duracionDias días a la fecha de salida.
            $fechaFin = date('Y-m-d', strtotime("+$duracionDias day", strtotime($viaje->fecha_salida)));
            
            return [
                'id' => $viaje->id,
                'destino' => $viaje->destino_ciudad,
                'cliente' => $viaje->despachos->first()->cliente->nombre ?? $viaje->despachos->first()->otro_cliente ?? 'Cliente Desconocido',
                'chofer' => $viaje->chofer->persona->nombre ?? 'Sin Asignar',
                'ayudante' => $viaje->ayudante_chofer->persona->nombre ?? 'Sin Asignar',
                'vehiculo' => $viaje->vehiculo->flota ?? 'Sin Asignar',
                'placa' => $viaje->vehiculo->placa ?? 'Sin Asignar',
                'status' => $viaje->status,
                'fecha_salida' => $viaje->fecha_salida,
                'duracion_dias' => $duracionDias,
                'despachos'=> $viaje->despachos->map(function($despacho) {
                    return [
                        'cliente' => $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'Cliente Desconocido',
                        'litros' => $despacho->litros,
                    ];
                }),
                // Campo extra para FullCalendar (usado en la vista)
                'fecha_fin_calendario' => $fechaFin,
            ];
        });

        //dd($viajesData);
        return view('viajes.calendario', [
            'viajesDataJson' => $viajesData->toJson()
        ]);
    }

    /**
     * API: Obtener datos del calendario de viajes (para Flutter)
     */
    public function getCalendarioApi(Request $request)
    {
        try {
            // Consulta con las relaciones necesarias
            $query = Viaje::with(['chofer.persona', 'ayudante_chofer.persona', 'vehiculo', 'despachos.cliente', 'viaticos']);
            
            // Filtrar por rango de fechas si se proporciona
            if ($request->has('fecha_inicio')) {
                $query->whereDate('fecha_salida', '>=', $request->fecha_inicio);
            }
            
            if ($request->has('fecha_fin')) {
                $query->whereDate('fecha_salida', '<=', $request->fecha_fin);
            }
            
            // Excluir cancelados
            $query->where('status', '!=', 'CANCELADO');
            
            $viajes = $query->orderBy('fecha_salida', 'asc')->get();
            
            // Transformar al formato para la app
            $viajesData = $viajes->map(function ($viaje) {
                // Calcular total de litros
                $totalLitros = $viaje->despachos->sum('litros');
                
                // Verificar si tiene pernocta
                $tienePernocta = $viaje->viaticos->contains(function ($viatico) {
                    return stripos($viatico->concepto, 'pernocta') !== false && 
                           ($viatico->monto_ajustado ?? $viatico->monto_base) > 0;
                });
                
                $duracionDias = $tienePernocta ? 2 : 1;
                
                return [
                    'id' => $viaje->id,
                    'destino_ciudad' => $viaje->destino_ciudad,
                    'fecha_salida' => $viaje->fecha_salida,
                    'status' => $viaje->status,
                    'duracion_dias' => $duracionDias,
                    'total_litros' => $totalLitros,
                    
                    // Información del chofer
                    'chofer' => [
                        'id' => $viaje->chofer_id,
                        'nombre' => $viaje->chofer->persona->nombre ?? 'PENDIENTE',
                    ],
                    
                    // Información del ayudante
                    'ayudante' => $viaje->ayudante ? [
                        'id' => $viaje->ayudante,
                        'nombre' => $viaje->ayudante_chofer->persona->nombre ?? 'N/A',
                    ] : null,
                    
                    // Información del vehículo
                    'vehiculo' => [
                        'id' => $viaje->vehiculo_id,
                        'flota' => $viaje->vehiculo->flota ?? 'PENDIENTE',
                        'placa' => $viaje->vehiculo->placa ?? 'N/A',
                    ],
                    
                    // Despachos
                    'despachos' => $viaje->despachos->map(function($despacho) {
                        return [
                            'id' => $despacho->id,
                            'cliente_id' => $despacho->cliente_id,
                            'cliente_nombre' => $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'Cliente Desconocido',
                            'litros' => $despacho->litros,
                        ];
                    }),
                    
                    // Información adicional
                    'custodia_count' => $viaje->custodia_count ?? 0,
                    'has_viatico' => $viaje->has_viatico ?? false,
                ];
            });
            
            return response()->json([
                'success' => true,
                'viajes' => $viajesData,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener calendario de viajes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
 * Actualiza un campo específico del modelo Viaje mediante petición AJAX.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id (ID del Viaje)
 * @return \Illuminate\Http\JsonResponse
 */
public function updateField(Request $request, $id)
{
    // 1. Validación de campos (solo los que esperamos que se actualicen)
    $request->validate([
        'field' => 'required|string',
        'value' => 'nullable',
    ]);

    $viaje = Viaje::findOrFail($id);
    $field = $request->input('field');
    $value = $request->input('value');
    $allowedFields = ['destino_ciudad', 'fecha_salida', 'chofer_id', 'ayudante', 'vehiculo_id', 'status'];

    if (!in_array($field, $allowedFields)) {
        return response()->json(['message' => 'Campo no permitido para actualización.', 'status' => 'error'], 403);
    }
    
    // 2. Actualización
    try {
        // Manejar el caso especial del ayudante (si el campo de la BD es 'ayudante_id')
        if ($field === 'ayudante') {
            $field = 'ayudante'; 
        }

        // Si el valor es nulo (ej. si se selecciona "Seleccione el ayudante"), guardamos null
        $viaje->{$field} = empty($value) ? null : $value;
        $viaje->save();
        
        return response()->json(['message' => 'Viaje actualizado.', 'status' => 'success'], 200);

    } catch (\Exception $e) {
        Log::error("Error AJAX al actualizar viaje #{$id} ({$field}): " . $e->getMessage());
        return response()->json(['message' => 'Error de servidor al guardar el cambio.', 'status' => 'error'], 500);
    }
}


/**
 * Actualiza un campo específico de un Despacho mediante petición AJAX.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $viajeId (No usado, pero puede ser útil para logs)
 * @param  int  $despachoId
 * @return \Illuminate\Http\JsonResponse
 */
public function updateDespacho(Request $request, $viajeId, $despachoId)
{
    $request->validate([
        'field' => 'required|string|in:litros,cliente_id,otro_cliente', // Solo permitir litros o cliente
        'value' => 'nullable',
    ]);

    $despacho = DespachoViaje::findOrFail($despachoId);
    $field = $request->input('field');
    $value = $request->input('value');
    
    try {
        if ($field === 'cliente_id') {
            
            if (empty($value)) {
                // Si el valor es null/vacío, se asume que se está eliminando el cliente registrado
                // y se apunta a 'otro_cliente' (un campo que debe ser un string).
                // NOTA: Esto requiere que el campo 'otro_cliente' en el formulario sea editable 
                // cuando 'cliente_id' es null.
                $despacho->cliente_id = null;
                // Dejamos 'otro_cliente' vacío o lo manejas con otra entrada en el formulario
                $despacho->otro_cliente = $value; 
            } else {
                // Si se selecciona un Cliente de la lista (ID), cliente_id se establece
                $despacho->cliente_id = (int)$value;
                $despacho->otro_cliente = null; // Limpiar el campo de texto si se usa la lista
            }
            
        } elseif ($field === 'litros') {
            // Asegurarse de que sea un número válido
            $despacho->litros = max(0, (float)$value);
        }

        $despacho->save();
        
        // Devolver el nuevo total de litros para actualizar el pie de la tabla
        $totalLitros = $despacho->viaje->despachos->sum('litros');

        return response()->json([
            'message' => 'Despacho actualizado.', 
            'status' => 'success',
            'total_litros' => number_format($totalLitros, 2)
        ], 200);

    } catch (\Exception $e) {
        Log::error("Error AJAX al actualizar despacho #{$despachoId}: " . $e->getMessage());
        return response()->json(['message' => 'Error de servidor al guardar el cambio.', 'status' => 'error'], 500);
    }
}

public function destroy($id)
{
    $viaje = Viaje::findOrFail($id);

    try {
        // Iniciar Transacción
        DB::beginTransaction();

        // 1. Eliminar los despachos relacionados (Requerido por el usuario)
        // Asume que tu modelo Viaje tiene una relación despachos()
        $despachos_eliminados = $viaje->despachos()->count();
        $viaje->despachos()->delete(); 

        // 2. Eliminar el viaje
        $viaje->delete();

        // Si todo va bien, confirmar la transacción
        DB::commit();

        return redirect()->route('viajes.list')
                         ->with('success', "✅ El Viaje #{$id} a {$viaje->destino_ciudad} (y {$despachos_eliminados} despachos) ha sido eliminado correctamente.");

    } catch (\Exception $e) {
        // Si algo falla, revertir los cambios
        DB::rollBack();
        
        Log::error("Error al eliminar el viaje #{$id}: " . $e->getMessage()); 
        
        return redirect()->route('viajes.list')
                         ->with('error', "❌ Error crítico al intentar eliminar el viaje #{$id}. Consulte los logs.");
    }
}

}
