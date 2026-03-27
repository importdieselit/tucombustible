<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo; // El modelo sigue siendo necesario para las vistas
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\User;
use App\Models\TipoVehiculo;
use App\Models\Orden;
use App\Models\ResumenDiario;
use App\Models\Cliente;
use App\Models\MantenimientoProgramado;
use App\Models\InventarioSuministro;
use App\Models\DespachoViaje;
use Illuminate\Http\Request;
use App\Http\Requests\VehiculoStoreRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str; // Es necesario para la función Str::plural()
use Illuminate\Support\Facades\DB;
use App\Models\TipoDocumento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Traits\GenerateAlerts;
use App\Traits\PluralizaEnEspanol;
use App\Models\VehiculoFoto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Viaje;
use App\Models\EstatusData;
use Illuminate\Support\Facades\Storage;


class VehiculoController extends BaseController
{
    /**
     * Sobrescribe el método create para pasar datos adicionales a la vista.
     * @return \Illuminate\View\View
     */
    
    public function filter(Request $request)
            {
            // 1. Inicializar el Query Builder del modelo correcto
            $query = Vehiculo::query(); 
            
            // 2. Llamar al list() del padre, que ejecutará el applyBusinessFilters(si existe)
            // y luego el filtro de seguridad de cliente.
            return $this->list($query); 
        }

    protected function getDetailsForView($item)
    {
        

        $orden=false;
        $insumos_usados=false;

     // NOTA: En una aplicación real, estos datos vendrían de la base de datos.
            $rutas = collect([
                // ['fecha' => '2024-05-15', 'origen' => 'Caracas', 'destino' => 'Valencia', 'km' => 170, 'conductor' => 'Pedro Pérez'],
                // ['fecha' => '2024-05-12', 'origen' => 'Valencia', 'destino' => 'Maracay', 'km' => 60, 'conductor' => 'Ana López'],
                // ['fecha' => '2024-05-10', 'origen' => 'Maracay', 'destino' => 'Caracas', 'km' => 120, 'conductor' => 'Juan Rivas'],
                // ['fecha' => '2024-05-08', 'origen' => 'Caracas', 'destino' => 'La Guaira', 'km' => 40, 'conductor' => 'Pedro Pérez'],
                // ['fecha' => '2024-05-05', 'origen' => 'La Guaira', 'destino' => 'Caracas', 'km' => 45, 'conductor' => 'Ana López'],
            ]);

            $historialMensual = collect([
                // ['mes' => 'Mayo 2024', 'km' => 1500, 'consumo' => 120.5],
                // ['mes' => 'Abril 2024', 'km' => 1800, 'consumo' => 145.7],
                // ['mes' => 'Marzo 2024', 'km' => 2100, 'consumo' => 170.3],
                // ['mes' => 'Febrero 2024', 'km' => 1950, 'consumo' => 155.0],
                // ['mes' => 'Enero 2024', 'km' => 1750, 'consumo' => 135.2],
            ]);

            // Cálculo de indicadores económicos (con datos simulados)
            $precioLitroCombustible = 0.5; // Precio ficticio por litro en USD
            $consumoTotalLitros = $historialMensual->sum('consumo');
            $gastoCombustible = $consumoTotalLitros * $precioLitroCombustible;
            $kmTotales = $historialMensual->sum('km');
            $costoPorKm = $kmTotales > 0 ? $gastoCombustible / $kmTotales : 0;
            $planes=
   
            // 1. Foto Principal
            $foto = VehiculoFoto::where('vehiculo_id', $item->id)
                ->where('es_principal', true)
                ->first();

            // 2. Historial de Viajes y Despachos (Optimizado con relaciones)
            $viajes = DespachoViaje::query()
                ->join('viajes', 'despachos_viajes.viaje_id', '=', 'viajes.id')
                ->with([
                    'viaje.chofer.persona', 
                    'viaje.ayudante_chofer.persona',
                    'cliente'
                ])
                ->where('viajes.vehiculo_id', $item->id)
                ->orderBy('viajes.fecha_salida', 'desc')
                ->select('despachos_viajes.*') 
                ->get()
                ->map(function ($v) {
                    return [
                        'id'       => $v->id,
                        'fecha'    => $v->viaje->fecha_salida ? $v->viaje->fecha_salida->format('d/m/Y H:i') : 'N/D',
                        'destino'  => $v->viaje->destino_ciudad ?? 'Sin datos',
                        'chofer'   => $v->viaje->chofer->persona->nombre ?? 'N/D',
                        'ayudante' => $v->viaje->ayudante_chofer->persona->nombre ?? 'N/D',        
                        'cliente'  => $v->cliente->nombre ?? $v->otro_cliente ?? 'N/D',
                        'litros'   => number_format($v->litros, 2, ',', '.')
                    ];
                });

            // 3. Lógica de Orden Abierta (Si está en Taller o Fuera de Servicio)
            if (in_array($item->estatus, [2, 3, 5])) { // Estatus que implican taller o revisión
                $ordenD = Orden::where('id_vehiculo', $item->id)
                    ->where('estatus', 2) // Asumiendo 2 como 'En Proceso/Abierta'
                    ->first();

                if ($ordenD) {
                    $orden = [
                        'id'             => $ordenD->id,
                        'fecha_ingreso'  => $ordenD->fecha_in,
                        'dias_parada'    => Carbon::parse($ordenD->fecha_in)->diffInDays(now()),
                        'insumos'        => InventarioSuministro::with('inventario')
                                            ->where('id_orden', $ordenD->id)->get()
                    ];
                }
            }

            $mantenimientos = Orden::where('id_vehiculo', $item->id)
                    //->where('tipo', 'mantenimiento')
                    ->orderBy('created_at', 'desc')
                    //->limit(5)
                    ->get();
            
            $estatus = EstatusData::where('id_estatus', $item->estatus)->first();
            $tipo = TipoVehiculo::where('id', $item->tipo)->first()->tipo ?? 'N/D';
            $esChuto = $item->tipo == 3; // Asumiendo tipo 3 es Chuto
            $esCisterna = in_array($item->tipo, [2, 5]); // Asumiendo tipo 2 y 5 son Cisterna/Tanque
            $acoples = [];
            if($esCisterna){
                $acoples = Vehiculo::where('es_flota', true)->whereIn('tipo', [3])->whereNull('acoplado_id')->get();
            
            }
            if($esChuto){
                $acoples = Vehiculo::where('es_flota', true)->whereIn('tipo', [2,5])->whereNull('acoplado_id')->get();
            
            }

            $docsV = TipoDocumento::where('tipo', 'V')->get();
        return [
            'foto'   => $foto,
            'viajes' => $viajes,
            'orden'  => $orden,
            'indicadores' => [
                'gasto_combustible' => number_format($gastoCombustible, 2, ',', '.'),
                'costo_por_km' => number_format($costoPorKm, 4, ',', '.'),
            ],
            'historialMensual' => $historialMensual,
            'rutas' => $rutas,
            'estatus' => $estatus,
            'esChuto' => $esChuto,
            'esCisterna' => $esCisterna,
            'tipo' => $tipo,
            'acoples' => $acoples,
             'mantenimientos' => $mantenimientos,
             'docsV' => $docsV
        ];
    }

    protected function getAdditionalData()
    {
        $cliente_id = Auth::user()->cliente_id;
        
        $unidades_con_alerta = Vehiculo::getUnidadesConDocumentosVencidos($cliente_id)->count();
        $total_vehiculos     = Vehiculo::misVehiculos()->count();
        $total_flota = Vehiculo::miFlota()->count();

        $unidades_con_orden_abierta = Vehiculo::VehiculosConOrdenAbierta()->count();
        $unidades_en_mantenimiento = Vehiculo::countVehiculosEnMantenimiento();
        $unidades_disponibles = Vehiculo::Disponibles()->count();
        $unidades_no_disponibles = Vehiculo::NoDisponibles()->count();
        $unidades_en_servicio = Vehiculo::EnServicio()->count();
        $historicoEficiencia = ResumenDiario::orderBy('fecha', 'desc')->limit(15)->get()->sortBy('fecha')->toArray();
        $mantenimientos = MantenimientoProgramado::with('vehiculo')
                ->whereIn('estatus', [1, 2])
                ->orderBy('fecha', 'desc') // o por km si lo deseas
                ->limit(7)
                ->get();

        $eficienciaActual = $total_flota > 0 ? ($unidades_disponibles / $total_flota) * 100 : 0; 
        $eficienciaActual = round($eficienciaActual, 2); 

        $fechaLimite = now()->subDays(45); // últimos 45 días

        $vehiculos = Vehiculo::select('vehiculos.id', 'vehiculos.placa', 'vehiculos.modelo')
                ->withCount(['ordenes as fallas_count' => function ($q) use ($fechaLimite) {
                    $q->where('created_at', '>=', $fechaLimite);
                    // Si quieres incluir solo órdenes marcadas como "falla":
                    // $q->where('tipo', 'falla');
                }])
                ->orderByDesc('fallas_count')
                ->take(10)
                ->get();


                    $desde = now()->subMonths(11)->startOfMonth();
            $hasta = now()->endOfMonth();

            $fallas = Orden::select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') AS mes"),
                    DB::raw("COUNT(*) AS total")
                )->whereBetween('created_at', [$desde, $hasta])
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            // arrays finales
            $fallas_labels = [];
            $fallas_values = [];

            $cursor = $desde->copy();
            while ($cursor <= $hasta) {
                $key = $cursor->format('Y-m');
                $fallas_labels[] = $cursor->isoFormat('MMM');  // Ene, Feb, Mar...
                $fallas_values[] = $fallas->firstWhere('mes', $key)->total ?? 0;
                $cursor->addMonth();
            }
    // 1. Obtener los tipos de vehículos y los estatus que queremos mostrar
    $tipos = TipoVehiculo::all();
    $estatusList = EstatusData::whereIn('id_estatus', [1, 2, 5])->get();

    $series = [];
    $categorias = $tipos->pluck('tipo')->toArray(); // Nombres de los tipos para el eje X

    foreach ($estatusList as $est) {
        // Definir colores según tu lógica vieja
        $color = match($est->id_estatus) {
            1 => '#28a745', // Disponible
            2 => '#dc3545', // Fuera de servicio
            5 => '#555555', // Otro / Desincorporado
            default => '#cccccc'
        };

        $dataPorTipo = [];
        foreach ($tipos as $tipo) {
            // Contamos los vehículos que coincidan con este estatus Y este tipo
            $conteo = Vehiculo::where('estatus', $est->id_estatus)
                        ->where('tipo', $tipo->id)
                        ->count();
            $dataPorTipo[] = $conteo;
        }

        $series[] = [
            'name'  => $est->auto, // Ajusta según el campo de tu tabla
            'data'  => $dataPorTipo,
            'color' => $color,
            'url'   => route('vehiculos.index', ['estatus' => $est->id_estatus]) 
        ];
    }



        $viajesActivos = Viaje::with(['vehiculo','despachos'])
                ->whereDate('fecha_salida', now()->format('Y-m-d')) // según tus estados reales
                ->orderBy('fecha_salida', 'desc')
                ->get()
                ->map(function($v){

                    $vehiculo = $v->vehiculo;

                    // si el vehículo no tiene dato, evitar error
                    $km = $vehiculo->km ?? 0;
                  //  $consumo = $vehiculo->consumo_promedio ?? null;
                  // Calculamos la carga total sumando los litros de todos los despachos asociados
                $cargaTotal = $v->litros ?? $v->despachos->sum('litros');

                // Obtenemos los nombres de los clientes/destinos de forma única
                $destinos = $v->despachos->map(function($d) {
                    // Prioridad: 1. Relación Cliente, 2. Campo otro_cliente
                    return $d->cliente->alias ?? $d->cliente->nombre ?? $d->otro_cliente ?? 'Desconocido';
                })->unique()->implode(', ');

                    return [
                        'placa'     => $vehiculo->placa ?? $v->otro_vehiculo ?? 'N/D',
                        'modelo'    => is_null($v->otro_vehiculo)?  $vehiculo->isModelo->modelo ?? 'N/D' :'Unidad Externa',
                        'marca'     => is_null($v->otro_vehiculo)? $vehiculo->isMarca->marca ?? 'N/D': '',
                        'ruta'      => $v->destino_ciudad ?? 'Sin Destino', //$v->despacho->cliente->nombre ?? $v->otro_cliente ??
                        'km'        => number_format($vehiculo->km_mantt ?? 0, 0, ',', '.'),
                        'carga_total' => number_format($cargaTotal, 2, ',', '.'),
                        'cliente_destino' => $destinos,
                        'estatus'   => '',//$v->status
                    ];
                });
        // Preparar datos para Chart.js
        $chartLabels = array_map(function($date) {
            return  Carbon::parse($date)->format('d/M');
        }, array_column($historicoEficiencia, 'fecha'));

        $chartDataCierre = array_column($historicoEficiencia, 'disponibilidad');

        return [
            'unidades_con_alerta' => $unidades_con_alerta,
            'total_vehiculos'     => $total_vehiculos,
            'total_flota'         => $total_flota,
            'unidades_con_orden_abierta'    => $unidades_con_orden_abierta,
            'unidades_en_mantenimiento'    => $unidades_en_mantenimiento,
            'unidades_disponibles'         => $unidades_disponibles,
            'unidades_en_servicio'         => $unidades_en_servicio,
            'unidades_no_disponibles'      => $unidades_no_disponibles,
            'historicoEficiencia' => $historicoEficiencia,
            'mantenimientos'      => $mantenimientos,
            'eficienciaActual'    => $eficienciaActual,
            'viajesActivos'       => $viajesActivos,
            'chartLabels'        => $chartLabels,
            'chartDataCierre'    => $chartDataCierre,
            'fallas_labels'      => $fallas_labels,
            'fallas_values'      => $fallas_values,
            'v_tot' => $total_vehiculos,
            'm_tot' => Vehiculo::where('es_flota', true)->whereIn('tipo', [1,3])->count(),
            't_tot' => Vehiculo::where('es_flota', true)->whereIn('tipo', [2,5])->count(),
            'm_dis' => Vehiculo::where('es_flota', true)->where('id_cliente',$cliente_id)->where('estatus', 1)->whereIn('tipo', [1])->count(),
            'ch_dis' => Vehiculo::where('es_flota', true)->where('id_cliente',$cliente_id)->where('estatus', 1)->whereIn('tipo', [3])->count(),
            'c_dis' => Vehiculo::where('es_flota', true)->where('id_cliente',$cliente_id)->where('estatus', 1)->whereIn('tipo', [2,4,5])->count(),
            'v_dis' => $unidades_disponibles,
            'v_fue' => $unidades_con_orden_abierta,
            'chartCategorias' => $categorias,
            'chartSeries'     => $series,   
            
            'promsem' => ResumenDiario::orderBy('fecha', 'desc')->limit(15)->get()->sortBy('fecha'),       
            'status_distribucion' => Vehiculo::select('estatus', DB::raw('count(*) as total'))
                                        ->groupBy('estatus')
                                        ->get(),
            
        ];
      
    }

    


    public function controlDocumentacion(Request $request)
    {
        $query = Vehiculo::query();

        // Filtro por cliente si es necesario
        if ($request->filled('cliente_id')) {
            $query->where('id_cliente', $request->cliente_id);
        }

        // Filtro de búsqueda general (Placa o Marca)
        if ($request->filled('search')) {
            $search = strtoupper($request->search);
            $query->where(function($q) use ($search) {
                $q->where('placa', 'LIKE', "%{$search}%")
                ->orWhere('marca', 'LIKE', "%{$search}%");
            });
        }

        $vehiculos = $query->orderBy('placa', 'asc')->paginate(20);
        $clientes = Cliente::orderBy('nombre', 'asc')->get();

        return view('vehiculo.documentacion', compact('vehiculos', 'clientes'));
    }

     protected function applyBusinessFilters(Builder $query): Builder
    {
        $filterKey = request()->get('filter'); // Usamos el helper global 'request()'
        
        if ($filterKey) {
            switch ($filterKey) {
                
                case 'en_servicio':
                    $query->enServicio();
                    break;

                case 'documentos_alerta':
                    $query->ConDocumentosEnAlerta(Auth::user()->cliente_id);
                    break;
                
                case 'disponibles':
                    // Filtro genérico que solo aplica si Vehiculo tiene columna 'estatus'
                    $query->disponibles();
                    break;
                case 'no_disponibles':
                    $query->noDisponibles();
                    break;
                
                case 'con_orden_abierta':
                    $query->VehiculosConOrdenAbierta();
                    break;
                case 'flota':
                    $query->EsFlota();
                    break;
                
            }
        }

        return $query; // Devolvemos el Query Builder modificado
    }

    public function create()
    {
        $marcas = Marca::pluck('marca', 'id');
        $modelos = Modelo::pluck('modelo', 'id');
        $clientes = Cliente::pluck('nombre', 'id');
        $tiposVehiculo = TipoVehiculo::pluck('tipo', 'id');
        $documentosRequeridos = TipoDocumento::where('tipo', 'V')->get();

        $counts = [
            'T' => Vehiculo::where('tipo', 2)->count(), // Tipo 2: Tanques
            'L' => Vehiculo::where('tipo', 6)->count(), // Tipo 6: Livianos
            'C' => Vehiculo::whereNotIn('tipo', [2, 6])->count(),
        ];
        
        // La lógica de la vista se hereda del BaseController, pero con los datos adicionales.
        return view($this->getModelNameLowerCase() . '.create', compact('marcas', 'modelos', 'clientes', 'tiposVehiculo','documentosRequeridos', 'counts'));
    }

    /**
     * Sobrescribe el método edit para pasar datos adicionales a la vista.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Se obtiene el vehículo usando la lógica del BaseController.
        $item = $this->model->findOrFail($id);

        $marcas = Marca::pluck('marca', 'id');
        $modelos = Modelo::pluck('modelo', 'id');
        $clientes = Cliente::pluck('nombre', 'id');
        $tiposVehiculo = TipoVehiculo::pluck('tipo', 'id');
        $documentosRequeridos = TipoDocumento::where('tipo', 'V')->get();

        // Se pasa el vehículo y los datos adicionales a la vista.
        return view($this->getModelNameLowerCase() . '.edit', compact('item', 'marcas', 'modelos', 'clientes', 'tiposVehiculo','documentosRequeridos'));
    }


    public function store(Request $request)
    {
        // Creamos una instancia de nuestro Form Request y validamos los datos.
        // Esto lanzará una excepción y redirigirá si la validación falla.
        app(VehiculoStoreRequest::class);
        try {
            $marcaId = $request->marca;
            if ($marcaId === 'otro') {
                $nuevaMarca = Marca::create(['nombre' => $request->nueva_marca]);
                $request->marca = $nuevaMarca->id;
            }

            $modeloId = $request->modelo;
            if ($modeloId === 'otro') {
                $nuevoModelo = Modelo::create([
                    'nombre' => $request->nuevo_modelo,
                    'marca_id' => $request->marca, // Usamos el ID de la marca recién creada o seleccionada
                ]);
                $request->modelo = $nuevoModelo->id;
            }
            
            if(is_null($request->cliente_id)){ $request->cliente_id=348;}
            $request->es_flota = true;


            $vehiculo=Vehiculo::create($request->all());

            // 2. Manejo de Documentos
        if ($request->has('documentos')) {
            foreach ($request->file('documentos') as $tipoId => $file) {
                // Buscamos el tipo para obtener la abreviatura
                $tipoDoc = TipoDocumento::find($tipoId);
                
                if ($tipoDoc && $file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    // Nombre estandarizado: CR_25.pdf
                    $nombreArchivo = "{$tipoDoc->abreviatura}_{$vehiculo->id}.{$extension}";
                    
                    // Ruta: public/vehiculos/25/documentos/CR_25.pdf
                    $rutaDestino = "vehiculos/{$vehiculo->id}/documentos";
                    
                    $file->storeAs("public/{$rutaDestino}", $nombreArchivo);
                    
                    // Opcional: Guardar referencia en una tabla pivote si quieres auditoría, 
                    // aunque tu planteamiento es consultarlo por ruta estática.
                }
            }
        }

            $this->handleFotoUpload($request, $vehiculo);

            Session::flash('success', 'Vehiculo creado exitosamente.');
        } catch (\Exception $e) {
            Log::info('Error al crear el registro: ' . $e->getMessage());
            Session::flash('error', 'Error al crear el registro: ' . $e->getMessage());

        }

        return Redirect::route('vehiculos.list');

    }

    public function updateV(Request $request)
    {
        $vehiculo=Vehiculo::findOrFail($request->id);
        
        //app(VehiculoStoreRequest::class);
        DB::beginTransaction();
        try {
            // 1. Actualizar datos del vehículo
            $vehiculo->update($request->all());

             // 2. Manejar la carga de imágenes (nuevas imágenes)
             $this->handleFotoUpload($request, $vehiculo);
            
             // 3. Manejar eliminación de fotos si se enviaron IDs a eliminar
             if ($request->has('fotos_a_eliminar')) {
                 $idsParaEliminar = explode(',', $request->input('fotos_a_eliminar'));
                 VehiculoFoto::whereIn('id', $idsParaEliminar)
                             ->where('vehiculo_id', $vehiculo->id)
                             ->delete();
                 // Opcionalmente, eliminar los archivos físicos del disco aquí
             }

             if ($request->has('documentos')) {
                foreach ($request->file('documentos') as $tipoId => $file) {
                    $tipoDoc = TipoDocumento::find($tipoId);
                    if ($tipoDoc && $file->isValid()) {
                        $extension = $file->getClientOriginalExtension();
                        $nombreArchivo = "{$tipoDoc->abreviatura}_{$vehiculo->id}.{$extension}";
                        $rutaDestino = "vehiculos/{$vehiculo->id}/documentos";

                        // Opcional: Limpiar archivos viejos con diferentes extensiones para evitar duplicados
                        $formatos = ['pdf', 'jpg', 'jpeg', 'png'];
                        foreach ($formatos as $f) {
                            $viejo = "public/{$rutaDestino}/{$tipoDoc->abreviatura}_{$vehiculo->id}.{$f}";
                            if (Storage::exists($viejo)) Storage::delete($viejo);
                        }

                        $file->storeAs("public/{$rutaDestino}", $nombreArchivo);
                    }
                }
            }

            DB::commit();
            Session::flash('success', 'Vehículo actualizado exitosamente!');
            return Redirect::route('vehiculos.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar vehículo: " . $e->getMessage());
            Session::flash('error', 'Hubo un error al actualizar el vehículo.');
            return Redirect::back()->withInput();
        }
    }

   
    /**
     * Lógica para guardar las fotos.
     * @param Request $request
     * @param Vehiculo $vehiculo
     */
    protected function handleFotoUpload(Request $request, Vehiculo $vehiculo)
    {
        if ($request->hasFile('fotos')) {
            $is_principal_set = $vehiculo->fotos()->where('es_principal', true)->exists();

            foreach ($request->file('fotos') as $index => $foto) {
                // Guarda la imagen en storage/app/public/vehiculos/
                $ruta = $foto->store('public/vehiculos');

                // Quita el prefijo 'public/' para obtener la ruta accesible
                $ruta_accesible = str_replace('public/', 'storage/', $ruta); 
                
                // Determina si es la primera foto y no hay principal aún
                $es_principal = false;
                if (!$is_principal_set && $index === 0 && $vehiculo->fotos()->count() === 0) {
                    $es_principal = true;
                    $is_principal_set = true;
                }

                VehiculoFoto::create([
                    'vehiculo_id' => $vehiculo->id,
                    'ruta' => $ruta_accesible,
                    'es_principal' => $es_principal,
                ]);
            }
        }
    }
    
    public function acoplar(Request $request)
    {
        $request->validate([
            'chuto_id' => 'required|exists:vehiculos,id',
            'acoplado_id' => 'required|exists:vehiculos,id',
        ]);

        try {
            $chuto = Vehiculo::findOrFail($request->chuto_id);
            $chuto->acoplado_id = $request->acoplado_id;
            $chuto->save();

            return back()->with('success', "Unidades acopladas exitosamente (Placa: {$chuto->placa})");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al acoplar: ' . $e->getMessage());
        }
    }

    public function desacoplar($id)
    {
        try {
            $chuto = Vehiculo::findOrFail($id);
            $chuto->acoplado_id = null;
            $chuto->save();

            return back()->with('success', 'Unidad desacoplada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al desacoplar: ' . $e->getMessage());
        }
    }
     public function importForm()
    {
        return view('vehiculo.import');
    }

    public function reporteDisponibilidad()
{
    $today = now();
    $data = Vehiculo::miFlota()->with(['tipoVehiculo', 'cisternaAcoplada', 'ordenActiva'])->get();

    $vehiculosEnRuta = Vehiculo::miFlota()->where('estatus', 2)->with(['viajes','viajes.cisternaAcoplada'])->get();
    $enRuta = $vehiculosEnRuta->count();

    $total = $data->count();
    $operativosCount = $data->where('estatus', 1)->count();
    
    $cisternas= $data->where('tipo', 2);
    $totalCisternas= $cisternas->count();
    $camiones = $data->whereIn('tipoVehiculo.tipo', ['CAMION','CAMION CISTERNA']);
    $chutos = $data->whereIn('tipoVehiculo.tipo', ['CHUTO']);
    $totalCamiones= $camiones->count();
    $totalChutos= $chutos->count();

    $fallaCount = $data->whereIn('estatus', [3,4,5])->count();
    $porcentajeDisponibilidad = $total > 0 ? round(($operativosCount / $total) * 100) : 0;
    $ligero=Vehiculo::misVehiculos()->with(['tipoVehiculo', 'ordenActiva'])->where('tipo', 6)->get();
    $totalLivianos= $ligero->count();
    
    $cisternasFalla = $cisternas->where('estatus', '!=', 1);
    $cisternasOperativas = $cisternas->where('estatus', 1);
    $camionesFalla = $camiones->where('estatus', '>', 2);
    $chutosFalla = $chutos->where('estatus', '>', 2);
    $camionetasFalla = $ligero->where('estatus', '!=', 1);
    $camionetasOperativas = $ligero->where('estatus', 1);
    $camionesOperativos = $camiones->where('estatus', 1);
    $chutosOperativos = $chutos->where('estatus', 1);

   $today = Carbon::parse($today); 
   $vehiculosEnUsoHoy = Viaje::whereDate('fecha_salida', $today)
    ->distinct('vehiculo_id')
    ->count('vehiculo_id');

    // 3. Cálculo de la tasa de utilización
    // Evitamos división por cero si no hay flota operativa configurada
    $utilizacionFlota = $operativosCount > 0 
        ? round(($vehiculosEnUsoHoy / $operativosCount) * 100, 1) 
        : 0;

    // Obtenemos los viajes activos de esos vehículos
    $despachosHoy = $vehiculosEnRuta->map(function($v) {
        return $v->viajes->first(); // Tomamos el último viaje cargado por el eager loading
    })->filter(); // Eliminamos nulos si algún vehículo no tiene viaje asignado
    // ------------------------------------
    

    return view('vehiculo.reporte_disponibilidad', compact(
        'today', 'cisternasFalla', 'enRuta', 'totalCisternas','camionesFalla', 'despachosHoy', 
        'camionetasFalla', 'camionetasOperativas', 'totalLivianos','totalCamiones', 'totalChutos',
        'chutosFalla', 'chutosOperativos', 'camionesOperativos', 
        'total', 'operativosCount', 'fallaCount', 'porcentajeDisponibilidad','cisternasOperativas','utilizacionFlota'

    ));
}

    /**
     * Procesa y guarda los vehículos del archivo cargado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importSave(Request $request)
    {
        // 1. Validar que se ha subido un archivo
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);
        
        try {
            $rows = Excel::toArray(null, $request->file('file'))[1];
            $header = array_map('trim', array_change_key_case($rows[0], CASE_LOWER));
            $dataRows = array_slice($rows, 1);
            
            foreach ($dataRows as $row) {
                // Si la fila está vacía, la ignoramos para evitar errores
                if (empty(array_filter($row))) {
                    continue;
                }

                $rotc_venc=null;
                $rowData = array_combine($header, $row);

                // // Lógica de búsqueda y creación de marca y modelo
                $marcaNombre = $rowData['marca'] ?? null;
                $modeloNombre = $rowData['modelo'] ?? null;
                $marcaId = null;
                $modeloId = null;
                $flota = $rowData['flota'];

                if ($marcaNombre) {
                    $marca = Marca::firstOrCreate(
                        ['marca' => trim(strtoupper($marcaNombre))]
                    );
                    $marcaId = $marca->id;

                    if ($modeloNombre && $marcaId) {
                        $modelo = Modelo::firstOrCreate(
                            [
                                'modelo' => trim(strtoupper($modeloNombre)),
                                'id_marca' => $marcaId
                            ]
                        );
                        $modeloId = $modelo->id;
                    }
                }

                $poliza = $rowData['POLIZA DE SEGURO'];
                if (strtoupper($poliza) === 'PENDIENTE' || !strtotime($poliza)) {
                    $poliza = null;
                }else{
                    $poliza= \Carbon\Carbon::parse($poliza)->format('Y-m-d');
                }

                $RCV = $rowData['POLIZA DE SEGURO'];
                if (strtoupper($RCV) === 'PENDIENTE' || !strtotime($RCV)) {
                    $RCV = null;
                }else{
                    $RCV= \Carbon\Carbon::parse($RCV)->format('Y-m-d');
                }

                $rotc = $rowData['ROTC'];
                if (strtoupper($rotc) === 'PENDIENTE') {
                    $rotc = null;
                    $rotc_venc=null;
                }else{
                    $rotc= explode('- AL ',$rotc);
                    $rotc_venc= (!empty($rotc[1]) && $rotc[1] !== 'PENDIENTE') 
                        ? \Carbon\Carbon::createFromFormat('d/m/Y', $rotc[1])->format('Y-m-d'):null;
                    $rotc=trim($rotc[0]);
                }
               // dd($rowData);    
                
                $tiposVehiculo= TipoVehiculo::where('tipo', $rowData['tipo'])->first();
               // if(is_null($tiposVehiculo)){ dd( $rowData['tipo']);}
                $vehiculo=Vehiculo::where('placa', $rowData['placa'])->first();
                if($vehiculo){
                    $vehiculo->flota = $flota;
                    $vehiculo->placa = $rowData['placa'];
                    $vehiculo->color = $rowData['color'] ?? $vehiculo->color;
                    $vehiculo->tipo = $tiposVehiculo->id ?? $vehiculo->tipo;
                    //$vehiculo->kilometraje = $rowData['KILOMETRAJE'] ?? $vehiculo->kilometraje;
                    $vehiculo->observacion = $rowData['OBSERVACION'].' - '.$rowData['DETALLES'] ?? $vehiculo->observacion;
                    $vehiculo->serial_motor = $rowData['serial_motor'] ?? $vehiculo->serial_motor;
                    $vehiculo->serial_carroceria = $rowData['serial_carroceria'] ?? $vehiculo->serial_carroceria;
                    $vehiculo->tipo = $tiposVehiculo->id ?? $vehiculo->tipo;
                    $vehiculo->poliza_fecha_out = $poliza;
                    $vehiculo->rcv=  trim($RCV);
                    $vehiculo->rotc=  !is_null($rotc)?$rotc:'PENDIENTE';
                    $vehiculo->rotc_venc=  $rotc_venc;
                    $vehiculo->racda=  $rowData['racda'];
                    //$vehiculo->anno = $rowData['AÑO'] ?? $vehiculo->anno;
                    $vehiculo->agencia = $rowData['EMPRESA'] ?? $vehiculo->agencia;
                    $vehiculo->carga_max = $rowData['ALMACENAMIENTO'] ?? 0;
                    //$vehiculo->consumo = $rowData['AUTONOMIA'] ?? 0;
                //  $vehiculo->gps = $rowData['GPS']== 'SI' ? true : false;
                    $vehiculo->marca= $marcaId;
                    $vehiculo->semcamer = $rowData['semcamer'];
                    $vehiculo->homologacion_intt= $rowData['homologacion_intt'];
                    $vehiculo->modelo= $modeloId;
                    $vehiculo->es_flota = true;
                    $vehiculo->save();
                }else{

                   // // Preparar los datos del vehículo
                    $vehiculoData = [
                        'flota' =>$flota,
                        'color' => $rowData['color'],
                        'placa' => $rowData['placa'],
                        //'kilometraje' => $rowData['KILOMETRAJE'],
                        'observacion' => $rowData['OBSERVACION'].' - '.$rowData['DETALLES'],
                        'serial_motor' => $rowData['serial_motor'],
                        'serial_carroceria' => $rowData['serial_carroceria'],
                        'tipo' => $tiposVehiculo->id,
                        'poliza_fecha_out' => $poliza,
                        'rcv'=>  trim($RCV),
                        'rotc'=>  !is_null($rotc)?$rotc:'PENDIENTE',
                        'rotc_venc'=>  $rotc_venc,
                        'racda'=>  $rowData['racda'],
                       // 'anno' => $rowData['AÑO'],
                        'agencia' => $rowData['EMPRESA'],
                        'carga_max' => $rowData['ALMACENAMIENTO'],
                        //'consumo' => $rowData['AUTONOMIA'],
                        'marca'=> $marcaId,
                        'semcamer' => $rowData['semcamer'],
                        'homologacion_intt'=> $rowData['homologacion_intt'],
                        'modelo'=> $modeloId,
                        'es_flota' => true,
                        'estatus' => 1
                    ];
                    Vehiculo::create($vehiculoData);
                }
                
            }
           // 4. Mensaje de éxito
            Session::flash('success', '¡Vehículos importados exitosamente!');
        } catch (\Exception $e) {
            // 5. Manejo de errores
            Session::flash('error', 'Hubo un error al importar los vehículos: ' . $e->getMessage());
        }

        return Redirect::back();
    }
    
}
