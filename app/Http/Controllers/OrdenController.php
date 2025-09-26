<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\Vehiculo; // Asumimos la existencia de este modelo
use App\Models\Personal; // Asumimos la existencia de un modelo de personal/mecanicos
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\TipoOrden; // Asegúrate de importar el modelo TipoOrden
use App\Models\EstatusData; // Asegúrate de importar el modelo EstatusData
use Carbon\Carbon; // Para manejo de
use Illuminate\Support\Facades\Auth;
use App\Models\InventarioSuministro; // Asegúrate de importar el modelo
use App\Traits\GenerateAlerts;
use Illuminate\Support\Facades\Redirect;
use App\Models\Inventario; // Asegúrate de importar el modelo Inventario

class OrdenController extends BaseController
{

     use GenerateAlerts;
    /**
     * Muestra el dashboard de órdenes de trabajo.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // --- Datos de prueba para el Dashboard ---

        // Simulación de reportes de falla (timeline)
        $reportes_falla = [
            (object)['fecha' => '2024-05-15', 'descripcion' => 'Falla en el sistema de frenos del Vehículo 003.'],
            (object)['fecha' => '2024-05-12', 'descripcion' => 'Motor sobrecalentado en el Vehículo 005.'],
            (object)['fecha' => '2024-05-10', 'descripcion' => 'Problema eléctrico en luces delanteras del Vehículo 012.'],
        ];

        // Simulación de próximos mantenimientos programados
        $mantenimientos_proximos = [
            (object)['vehiculo' => 'Flota 012', 'tarea' => 'Cambio de aceite', 'fecha' => '2024-05-20'],
            (object)['vehiculo' => 'Flota 008', 'tarea' => 'Revisión de frenos', 'fecha' => '2024-05-25'],
            (object)['vehiculo' => 'Flota 001', 'tarea' => 'Rotación de neumáticos', 'fecha' => '2024-06-01'],
        ];

        // Simulación de tiempo promedio de la orden
        // En un entorno real, esto se calcularía en base a las fechas de apertura y cierre.
        $tiempo_promedio_orden = 3.5; // Días

        // Simulación de gasto mensual en insumos
        $gasto_mensual = collect([
            (object)['name' => 'Enero', 'y' => 2500],
            (object)['name' => 'Febrero', 'y' => 3100],
            (object)['name' => 'Marzo', 'y' => 2800],
            (object)['name' => 'Abril', 'y' => 4500],
            (object)['name' => 'Mayo', 'y' => 3900],
        ]);

        // Simulación de vehículos con más reportes de falla
        $vehiculos_mas_fallas = collect([
            (object)['name' => 'Flota 003', 'y' => 15],
            (object)['name' => 'Flota 005', 'y' => 10],
            (object)['name' => 'Flota 013', 'y' => 8],
            (object)['name' => 'Otros', 'y' => 12],
        ]);

        // Simulación de alertas de kilometraje
        $alertas_kilometraje = [
            (object)['vehiculo' => 'Flota 003', 'placa' => 'ABC-123', 'kilometraje' => 105000, 'proximo_mantenimiento' => 100000],
            (object)['vehiculo' => 'Flota 005', 'placa' => 'DEF-456', 'kilometraje' => 82000, 'proximo_mantenimiento' => 80000],
        ];

        return view('orden.index', compact(
            'reportes_falla',
            'mantenimientos_proximos',
            'tiempo_promedio_orden',
            'gasto_mensual',
            'vehiculos_mas_fallas',
            'alertas_kilometraje'
        ));
    }

    public function searchSupplies(Request $request)
    {
        $search = $request->input('query');
        
        $suministros = Inventario::where('descripcion', 'LIKE', "%{$search}%")
                                 ->orWhere('codigo', 'LIKE', "%{$search}%")
                                 ->get();
        
        return response()->json($suministros);
    }

    /**
     * Muestra el listado de órdenes de trabajo.
     * @return \Illuminate\View\View
     */
    
    // public function list()
    // {
    //     // En una aplicación real, esto obtendría los datos de la base de datos
    //     // y podría usar paginación.
    //     $data = collect([
    //         (object)['id' => 1, 'nro_orden' => 1001, 'vehiculo' => 'Vehículo 001 (ABC-123)', 'fecha_in' => '2024-05-10', 'tipo' => 'Reparación', 'estatus' => 'Cerrada'],
    //         (object)['id' => 2, 'nro_orden' => 1002, 'vehiculo' => 'Vehículo 008 (DEF-456)', 'fecha_in' => '2024-05-12', 'tipo' => 'Mantenimiento', 'estatus' => 'Abierta'],
    //         (object)['id' => 3, 'nro_orden' => 1003, 'vehiculo' => 'Vehículo 012 (GHI-789)', 'fecha_in' => '2024-05-15', 'tipo' => 'Servicio', 'estatus' => 'Abierta'],
    //     ]);
    //     $estatusData = EstatusData::all()->keyBy('id_estatus');

    //     return view('ordenes.list', compact('data'));
    // }


    private function generateOrdenCode()
    {
        // Obtener la fecha actual
        $today = Carbon::now();
        $year = $today->format('y');
        $day = $today->format('d');
        
        // Mapeo de meses a abreviaturas en español
        $month_map = [
            '01' => 'EN', '02' => 'FE', '03' => 'MA', '04' => 'AB',
            '05' => 'MY', '06' => 'JN', '07' => 'JL', '08' => 'AG',
            '09' => 'SE', '10' => 'OC', '11' => 'NO', '12' => 'DI',
        ];
        $month = $month_map[$today->format('m')];

        // Buscar la última orden del día
        $lastOrden = Orden::whereDate('created_at', $today)
                           ->orderBy('created_at', 'desc')
                           ->first();
        
        // Determinar el número secuencial
        $sequential_number = 1;
        if ($lastOrden) {
            // Extraer el número secuencial del código de la última orden
            $last_nro = substr($lastOrden->nro_orden, 6);
            $sequential_number = intval($last_nro) + 1;
        }

        // Formatear el número secuencial con ceros a la izquierda
        $padded_number = str_pad($sequential_number, 2, '0', STR_PAD_LEFT);

        // Combinar todas las partes
        return "{$year}{$month}{$day}{$padded_number}";
    }


    
    /**
     * Muestra el formulario para crear un nuevo recurso, sobrescribiendo el del BaseController.
     * @return \Illuminate\View\View
     */
    public function create($vehiculo_id=null)
    {
        // En una app real, se obtendrían de la base de datos
        $vehiculo =NULL;
        $vehiculos = Vehiculo::all();
        $personal = Personal::all();
        $tipos = TipoOrden::all();
        $nro_orden = $this->generateOrdenCode();
        $suministros = Inventario::all();
        $estatusOpciones = EstatusData::all()->keyBy('id_estatus');        
        if(!is_null($vehiculo_id)){
                     $vehiculo = Vehiculo::findOrFail($vehiculo_id); 
                }
        return view('orden.create', compact('vehiculo','vehiculos', 'personal','tipos', 'nro_orden','suministros','estatusOpciones'));
    }

    /**
     * Muestra el recurso especificado (hoja técnica), sobrescribiendo el del BaseController.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        try {
            // Asumiendo que se puede obtener la orden con sus relaciones
            $orden = $this->model->findOrFail($id);

            // Datos de prueba para la hoja técnica
            // $insumos_usados = [
            //     (object)['nombre' => 'Aceite de motor', 'cantidad' => 5, 'unidad' => 'Litros'],
            //     (object)['nombre' => 'Filtro de aceite', 'cantidad' => 1, 'unidad' => 'Unidad'],
            //     (object)['nombre' => 'Pastillas de freno', 'cantidad' => 1, 'unidad' => 'Juego'],
            // ];
            $insumos_usados = InventarioSuministro::with('inventario')->where('id_orden', $id)->get();
            $estatusData = EstatusData::all()->keyBy('id_estatus');

            return view('orden.show', compact('orden', 'insumos_usados', 'estatusData'));
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'La orden de trabajo no fue encontrada.');
            return Redirect::route('orden.list');
        }
    }

    /**
     * Sobrescribimos el método `store` para manejar la lógica específica.
     * La validación se haría aquí o en un FormRequest.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Lógica de validación aquí

        // Lógica para crear la orden, asignar insumos, etc.
        // Obtener el ID del usuario autenticado
            $userId = Auth::id();
        // Almacena en la DB
        $orden=$this->model->create($request->all());
         // 4. Procesar y guardar los suministros solicitados
            if ($request->has('supplies') && is_array($request->supplies)) {
                foreach ($request->supplies as $supply) {
                    // 5. Crear un nuevo registro en la tabla inventario_suministro para cada ítem
                    // Nota: Tu esquema de tabla no tiene un campo para la cantidad solicitada.
                    // Si la cantidad es importante, deberás crear una tabla relacionada o modificar este esquema.
                    InventarioSuministro::create([
                        'estatus' => 1, // 1 = 'Solicitado'
                        'id_usuario' => $userId,
                        'id_orden' => $orden->id, // Usar el número de orden que acabamos de crear
                        'id_auto' => $request->id_vehiculo,
                        'id_inventario' => $supply['item_id'],
                        'id_emisor' => $userId,
                        // 'servicio' y 'anulacion' se dejarán nulos por ahora, ya que no son parte del formulario inicial
                        'servicio' => null,
                        'anulacion' => null,
                        'destino' => null,
                    ]);
                }
            }

        $this->createAlert([
            'id_usuario' => $userId, // ID del usuario responsable de la orden.
            'id_rel' => $orden->id, // ID de la orden.
            'observacion' => 'Se te ha asignado una nueva orden de trabajo: ' . $orden->nro_orden.' a '.$orden->resposable,
            'accion' => route('ordenes.show', $orden->id), // Ruta para ver la orden.
            'dias' => 0,
        ]);
        // Mensaje de éxito
        Session::flash('success', 'Orden de trabajo creada exitosamente.');

        // Redirige al listado
        return Redirect::route('ordenes.list');
    }


    /**
     * Almacena un nuevo suministro para una orden.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeSupply(Request $request)
    {
        $request->validate([
            'id_orden' => 'required|integer|exists:ordenes,id',
            'id_inventario' => 'required|integer|exists:inventario,id',
            'cantidad' => 'required|numeric|min:1',
            // Puedes agregar más validaciones aquí
        ]);
    
        try {
            $userId = Auth::id();
            $orden = Orden::findOrFail($request->id_orden);
    
            $supply = InventarioSuministro::create([
                'id_orden' => $orden->id,
                'id_inventario' => $request->id_inventario,
                'cantidad' => $request->cantidad,
                'id_usuario' => $userId, // Usuario que registra el suministro
                'id_auto' => $orden->id_vehiculo,
                'id_emisor' => $userId,
                'estatus' => 2, // 2 = 'Solicitado'
            ]);
            $result= InventarioSuministro::with('inventario')->where('id_inventario_suministro', $supply->id_inventario_suministro)->first();
    
            Session::flash('success', 'Suministro agregado exitosamente.');

            return response()->json(['success' => true, 'supply' => $result]);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al agregar el suministro.');
            return response()->json(['success' => false, 'message' => 'Error al agregar el suministro. '.$e->getMessage()], 500);
        }
    }

    /**
     * Actualiza un suministro existente.
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSupply(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:1',
            // Puedes agregar más validaciones aquí
        ]);

        try {
            $supply = InventarioSuministro::findOrFail($id);
            $supply->update([
                'cantidad' => $request->cantidad,
                // Puedes actualizar otros campos aquí
            ]);

            Session::flash('success', 'Suministro actualizado exitosamente.');
            return response()->json(['success' => true, 'supply' => $supply]);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Suministro no encontrado.');
            return response()->json(['success' => false, 'message' => 'Suministro no encontrado'], 404);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al actualizar el suministro.');
            return response()->json(['success' => false, 'message' => 'Error al actualizar el suministro.'], 500);
        }
    }

    /**
     * Elimina un suministro.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSupply($id)
    {
        try {
            $supply = InventarioSuministro::findOrFail($id);
            $supply->delete();

            Session::flash('success', 'Suministro eliminado exitosamente.');
            return response()->json(['success' => true, 'message' => 'Suministro eliminado.']);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Suministro no encontrado.');
            return response()->json(['success' => false, 'message' => 'Suministro no encontrado'], 404);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al eliminar el suministro.');
            return response()->json(['success' => false, 'message' => 'Error al eliminar el suministro.'], 500);
        }
    }

    public function cerrarOrden(Request $request, $id)
    {
        try {
            $orden = Orden::findOrFail($id);
            $orden->estatus = 1; // 1 = 'Cerrada'
            $orden->fecha_out = Carbon::now()->toDateString();
            $orden->hora_out = Carbon::now()->toTimeString();
            $orden->id_us_out = Auth::id(); // Usuario que cierra la orden
            $orden->save();

            Session::flash('success', 'Orden de trabajo cerrada exitosamente.');
            return response()->json(['success' => true, 'message' => 'Orden de trabajo cerrada exitosamente']);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden de trabajo no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden de trabajo no encontrada.']);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al cerrar la orden de trabajo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cerrar la orden de trabajo: ' . $e->getMessage()], 500);
        }
    }


     public function anularOrden(Request $request, $id)
    {
        try {
            $orden = Orden::findOrFail($id);
            $orden->estatus = 4; // 4 = 'anulada'
            $orden->fecha_out = Carbon::now()->toDateString();
            $orden->hora_out = Carbon::now()->toTimeString();
            $orden->anulacion = $request->input('anulacion'); // Guardar el motivo de anulación
            $orden->id_us_out = Auth::id(); // Usuario que cierra la orden
            $orden->save();

            Session::flash('success', 'Orden de trabajo cerrada exitosamente.');
                   return response()->json(['success' => true, 'message' => 'Orden de trabajo anulada exitosamente']);
     
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden de trabajo no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden de trabajo no encontrada.']);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al cerrar la orden de trabajo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al anular la orden de trabajo: ' . $e->getMessage()], 500);
        }
    }
     public function reactivarOrden(Request $request, $id)
    {
        try {
            $orden = Orden::findOrFail($id);
            $orden->estatus = 2; // 2 = 'Abierta'
            $orden->fecha_out = null;
            $orden->hora_out = null;
            $orden->id_us_out =null; // Usuario que cierra la orden
            $orden->save();

            Session::flash('success', 'Orden de trabajo Reactivada exitosamente.');
            return response()->json(['success' => true, 'message' => 'Orden de trabajo Reactivada exitosamente']);
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Orden de trabajo no encontrada.');
            return response()->json(['success' => false, 'message' => 'Orden de trabajo no encontrada.']);
        } catch (\Exception $e) {
            Session::flash('error', 'Error al cerrar la orden de trabajo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cerrar la orden de trabajo: ' . $e->getMessage()], 500);
        }

    }
}
