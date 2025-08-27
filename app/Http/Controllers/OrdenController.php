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
    public function create()
    {
        // En una app real, se obtendrían de la base de datos
        $vehiculos = Vehiculo::all();
        $personal = Personal::all();
        $tipos = TipoOrden::all();
        $nro_orden = $this->generateOrdenCode();
        

        return view('orden.create', compact('vehiculos', 'personal','tipos', 'nro_orden'));
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
            $insumos_usados = [
                (object)['nombre' => 'Aceite de motor', 'cantidad' => 5, 'unidad' => 'Litros'],
                (object)['nombre' => 'Filtro de aceite', 'cantidad' => 1, 'unidad' => 'Unidad'],
                (object)['nombre' => 'Pastillas de freno', 'cantidad' => 1, 'unidad' => 'Juego'],
            ];
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
                        'nro_orden' => $orden->nro_orden, // Usar el número de orden que acabamos de crear
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

}
