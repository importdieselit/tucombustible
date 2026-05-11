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
use App\Models\DocumentosVehiculo;
use App\Models\InventarioSuministro;
use App\Models\DespachoViaje;
use App\Models\Viaje;
use App\Models\EstatusData;
use App\Models\VehiculoFoto;
use App\Models\TipoDocumento;
use App\Models\HistorialGpsVehiculo;
use Illuminate\Http\Request;
use App\Services\VehiculoService;
use App\Repositories\VehiculoRepository;
use App\Http\Requests\VehiculoStoreRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str; 
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use App\Traits\GenerateAlerts;
use App\Traits\PluralizaEnEspanol;


class VehiculoController extends BaseController
{
    protected $service;
    protected $repo;

    public function __construct(VehiculoService $service, VehiculoRepository $repo)
    {
        parent::__construct(); 
        $this->service = $service;
        $this->repo = $repo;
    }
/*
    public function index()
    {
        $vehiculos = $this->repo->getAll();
        return view('vehiculo.index', compact('vehiculos'));
    }
*/
    public function filter(Request $request)
    {
        return $this->list(\App\Models\Vehiculo::query()); 
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
                ->whereIn('estatus', [1, 2]) // Solo pendientes o en proceso
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



        $viajesActivos = Viaje::with(['vehiculo', 'despachos'])
            // Filtramos solo los viajes que tengan un vehículo relacionado con estatus 2
            ->whereHas('vehiculo', function($q) {
                $q->where('estatus', 2);
            })
            ->whereDate('fecha_salida', now()->format('Y-m-d'))
            ->orderBy('fecha_salida', 'desc')
            ->get()
            ->map(function($v) {
                $vehiculo = $v->vehiculo;

                // Si el vehículo no tiene dato, evitar error
                $km = $vehiculo->km ?? 0;
                
                // Calculamos la carga total sumando los litros de todos los despachos asociados
                $cargaTotal = $v->litros ?? $v->despachos->sum('litros');

                // Obtenemos los nombres de los clientes/destinos de forma única
                $destinos = $v->despachos->map(function($d) {
                    return $d->cliente->alias ?? $d->cliente->nombre ?? $d->otro_cliente ?? 'Desconocido';
                })->unique()->implode(', ');

                return [
                    'placa'           => $vehiculo->placa ?? $v->otro_vehiculo ?? 'N/D',
                    'modelo'          => is_null($v->otro_vehiculo) ? $vehiculo->isModelo->modelo ?? 'N/D' : 'Unidad Externa',
                    'marca'           => is_null($v->otro_vehiculo) ? $vehiculo->isMarca->marca ?? 'N/D' : '',
                    'ruta'            => $v->destino_ciudad ?? 'Sin Destino',
                    'km'              => number_format($vehiculo->km_mantt ?? 0, 0, ',', '.'),
                    'carga_total'     => number_format($cargaTotal, 2, ',', '.'),
                    'cliente_destino' => $destinos,
                    'estatus'         => $v->status ?? '', 
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
        $docsV = TipoDocumento::where('tipo', 'V')->get();

        return view('vehiculo.documentacion', compact('vehiculos', 'clientes', 'docsV'));

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
                case 'en_mantenimiento':
                    $query->VehiculosEnMantenimiento();
                    break;
                case 'flota':
                    $query->EsFlota();
                    break;
                case 'chutos_camiones':
                    $query->whereIn('tipo', [1,3])->where('es_flota', true);
                    break;
                case 'cisternas':
                    $query->whereIn('tipo', [2,5])->where('es_flota', true);
                    break;
                case 'chutos_disponibles':
                    $query->where('tipo', 3)->where('estatus', 1)->where('es_flota', true);
                    break;
                case 'camiones_disponibles':
                    $query->where('tipo', 1)->where('estatus', 1)->where('es_flota', true);
                    break;
                case 'cisternas_disponibles':
                    $query->whereIn('tipo', [2,5])->where('estatus', 1)->where('es_flota', true);
                    break;

            }
        }

        return $query; // Devolvemos el Query Builder modificado
    }

    public function create()
    {
        $marcas = $this->repo->getMarcas();
        $modelos = $this->repo->getModelos();
        $clientes = $this->repo->getClientes();
        $tiposVehiculo = $this->repo->getTiposVehiculo();
        $documentosRequeridos = $this->repo->getDocumentosRequeridosV();
        
        return view('vehiculo.create', compact('marcas', 'modelos', 'clientes', 'tiposVehiculo','documentosRequeridos'));
    }

    public function store(Request $request)
    {
        app(VehiculoStoreRequest::class); 
        try {
            $marcaId = $request->marca;
            if ($marcaId === 'otro') {
                $nuevaMarca = Marca::create(['marca' => $request->nueva_marca]);
                $request->marca = $nuevaMarca->id;
            }

            $modeloId = $request->modelo;
            if ($modeloId === 'otro') {
                $nuevoModelo = Modelo::create([
                    'modelo' => $request->nuevo_modelo,
                    'id_marca' => $request->marca, // Usamos el ID de la marca recién creada o seleccionada
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
            Log::error('Error Store Vehiculo: ' . $e->getMessage());
            Session::flash('error', 'Error al crear el registro.');
        }
        return Redirect::route('vehiculos.list');
    }

    public function edit($id)
    {
        $item = $this->repo->findById($id);
        $marcas = $this->repo->getMarcas();
        $modelos = $this->repo->getModelos();
        $clientes = $this->repo->getClientes();
        $tiposVehiculo = $this->repo->getTiposVehiculo();
        $documentosRequeridos = $this->repo->getDocumentosRequeridosV();

        return view('vehiculo.edit', compact('item', 'marcas', 'modelos', 'clientes', 'tiposVehiculo','documentosRequeridos'));
    }

    public function getDocumentoDetalle($vehiculo_id, $tipo_id)
    {
        $vehiculo = Vehiculo::findOrFail($vehiculo_id);
        $tipoDoc = TipoDocumento::findOrFail($tipo_id);

        // 1. Obtener el valor del campo dinámico si existe (ej: rcv, racda)
        $valorCampoDestino = null;
        if ($tipoDoc->campo_destino && isset($vehiculo->{$tipoDoc->campo_destino})) {
            $valorCampoDestino = $vehiculo->{$tipoDoc->campo_destino};
        }

        // 2. Obtener el registro detallado (el último subido)
        $registro = DocumentosVehiculo::where('vehiculo_id', $vehiculo_id)
                                    ->where('tipo', $tipo_id)
                                    ->first();

        // 3. Verificar si el archivo físico existe (PDF, JPG o PNG)
        $finalPath = null;
        $extensions = ['pdf', 'jpg', 'png', 'jpeg'];
        foreach ($extensions as $ext) {
            $path = "storage/vehiculos/{$vehiculo_id}/documentos/{$tipoDoc->abreviatura}_{$vehiculo_id}.{$ext}";
            if (file_exists(public_path($path))) {
                $finalPath = asset($path);
                break;
            }
        }

        return response()->json([
            'success'      => true,
            'label'        => $tipoDoc->nombre,
            'abreviatura'  => $tipoDoc->abreviatura,
            'valor_actual' => $valorCampoDestino ?? ($registro->fecha_venc ?? ''),
            'nro_registro' => $registro->nro ?? '',
            'file_url'     => $finalPath,
            'tiene_campo'  => !empty($tipoDoc->campo_destino)
        ]);
    }


    
    public function updateDocumento(Request $request)
    {
        $tipoDoc = TipoDocumento::findOrFail($request->doc_id);
        
        // Validación dinámica: si el documento tiene campo_destino, la fecha es obligatoria.
        // Si no (como el Certificado), la fecha puede ser opcional.
        $rules = [
            'vehiculo_id' => 'required',
            'doc_id'      => 'required',
            'archivo'     => 'nullable|file|mimes:pdf,jpg,png|max:5120'
        ];
        
        if ($tipoDoc->campo_destino) {
            $rules['valor_texto'] = 'required';
        }

        $request->validate($rules);

        $vehiculo = Vehiculo::findOrFail($request->vehiculo_id);

        // 1. Gestión del Archivo (Ruta Estándar)
        if ($request->hasFile('archivo')) {
            $extension = $request->file('archivo')->getClientOriginalExtension();
            $fileName = "{$tipoDoc->abreviatura}_{$vehiculo->id}.{$extension}";
            $folder = "public/vehiculos/{$vehiculo->id}/documentos";
            
            // Borrar archivos viejos con otras extensiones para evitar duplicados (estándar)
            foreach(['pdf','jpg','png'] as $ext) {
                Storage::delete("{$folder}/{$tipoDoc->abreviatura}_{$vehiculo->id}.{$ext}");
            }
            
            $request->file('archivo')->storeAs($folder, $fileName);
        }

        // 2. Actualización en Tabla Principal (Solo si aplica)
        if ($tipoDoc->tabla_destino == 'vehiculos' && $tipoDoc->campo_destino) {
            $campo = $tipoDoc->campo_destino;
            $vehiculo->$campo = $request->valor_texto;
            $vehiculo->save();
        }

        // 3. Registro en Tabla de Documentos (Auditoría/Histórico)
        // Esto se hace SIEMPRE para tener el rastro de quién subió qué y cuándo
        DocumentosVehiculo::updateOrCreate(
            ['vehiculo_id' => $vehiculo->id, 'tipo' => $tipoDoc->id],
            [
                'fecha_venc' => $request->fecha_venc ?? null,
                'nro'        => $request->nro ?? null,
                'doc'        => "vehiculos/{$vehiculo->id}/documentos/{$tipoDoc->abreviatura}_{$vehiculo->id}", // nombre base
                'fecha_ing' => now(),
            ]
        );

        return back()->with('success', "{$tipoDoc->nombre} gestionado correctamente.");
    }

    public function updateV(Request $request)
    {
        try {
            $this->service->actualizarVehiculo($request->id, $request->all(), $request->file('documentos'), $request->file('fotos'), $request->input('fotos_a_eliminar'));
            Session::flash('success', '¡Vehículo actualizado!');
            return Redirect::route('vehiculos.index');
        } catch (\Exception $e) {
            Log::error("Error Update Vehiculo: " . $e->getMessage());
            return Redirect::back()->with('error', 'Error al actualizar.');
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

    public function reporteDisponibilidad(Request $request)
{
    $tokenValido = config('services.reporte.internal_token');
    // Si no está logueado Y el token no coincide, entonces al login
    if (!auth()->check() && $request->get('token') !== $tokenValido) {
       // abort(403, 'Acceso no autorizado');
    }
    $today = now();
    $data = Vehiculo::miFlota()->with(['tipoVehiculo', 'cisternaAcoplada', 'ordenActiva'])->get();

    $vehiculosEnRuta = $data->where('estatus', 2);
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
    
    $cisternasFalla = $cisternas->where('estatus', '>', 2);
    $camionesFalla = $camiones->where('estatus', '>', 2);
    $chutosFalla = $chutos->where('estatus', '>', 2);
    $camionetasFalla = $ligero->where('estatus', '>', 2);
    $cisternasOperativas = $cisternas->where('estatus', 1);
    $camionetasOperativas = $ligero->where('estatus', 1);
    $camionesOperativos = $camiones->where('estatus', 1);
    $chutosOperativos = $chutos->where('estatus', 1);
    $chutosEnRuta = $chutos->where('estatus', 2);
    $camionetasEnRuta = $ligero->where('estatus', 2);
    $camionesEnRuta = $camiones->where('estatus', 2);
    $cisternasEnRuta = $cisternas->where('estatus', 2);

   $today = Carbon::parse($today);
   $queryViajesHoy = Viaje::whereDate('fecha_salida', now()->format('Y-m-d'));

    // 2. Obtener el conteo de vehículos únicos (usando el nombre real de la columna: id_vehiculo)
    $vehiculosEnUsoHoy = (clone $queryViajesHoy)->distinct()->count('vehiculo_id');


    // 3. Cálculo de la tasa de utilización
    // Evitamos división por cero si no hay flota operativa configurada
    $utilizacionFlota = $operativosCount > 0 
        ? round(($vehiculosEnUsoHoy / $operativosCount) * 100, 1) 
        : 0;

    // Obtenemos los viajes activos de esos vehículos
    $despachosHoy = $queryViajesHoy->with(['vehiculo', 'chofer'])->get();
    // ------------------------------------
    

    return view('vehiculo.reporte_disponibilidad', compact(
        'today', 'cisternasFalla', 'enRuta', 'totalCisternas','camionesFalla', 'despachosHoy', 
        'camionetasFalla', 'camionetasOperativas', 'totalLivianos','totalCamiones', 'totalChutos',
        'chutosFalla', 'chutosOperativos', 'camionesOperativos', 
        'total', 'operativosCount', 'fallaCount', 'porcentajeDisponibilidad','cisternasOperativas','utilizacionFlota',
        'chutosEnRuta', 'camionetasEnRuta', 'camionesEnRuta', 'cisternasEnRuta'

    ));
}

  public function refreshDisponibilidad()
{
    $today = now();
    $data = Vehiculo::miFlota()->with(['tipoVehiculo', 'cisternaAcoplada', 'ordenActiva'])->get();

    $vehiculosEnRuta = $data->where('estatus', 2);
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
    
    $cisternasFalla = $cisternas->where('estatus', '>', 2);
    $camionesFalla = $camiones->where('estatus', '>', 2);
    $chutosFalla = $chutos->where('estatus', '>', 2);
    $camionetasFalla = $ligero->where('estatus', '>', 2);
    $cisternasOperativas = $cisternas->where('estatus', 1);
    $camionetasOperativas = $ligero->where('estatus', 1);
    $camionesOperativos = $camiones->where('estatus', 1);
    $chutosOperativos = $chutos->where('estatus', 1);
    $chutosEnRuta = $chutos->where('estatus', 2);
    $camionetasEnRuta = $ligero->where('estatus', 2);
    $camionesEnRuta = $camiones->where('estatus', 2);
    $cisternasEnRuta = $cisternas->where('estatus', 2);

   $today = Carbon::parse($today);
   $queryViajesHoy = Viaje::whereDate('fecha_salida', now()->format('Y-m-d'));

    // 2. Obtener el conteo de vehículos únicos (usando el nombre real de la columna: id_vehiculo)
    $vehiculosEnUsoHoy = (clone $queryViajesHoy)->distinct()->count('vehiculo_id');


    // 3. Cálculo de la tasa de utilización
    // Evitamos división por cero si no hay flota operativa configurada
    $utilizacionFlota = $operativosCount > 0 
        ? round(($vehiculosEnUsoHoy / $operativosCount) * 100, 1) 
        : 0;

    // Obtenemos los viajes activos de esos vehículos
    $despachosHoy = $queryViajesHoy->with(['vehiculo', 'chofer'])->get();
    // ------------------------------------
    

    return view('vehiculo.partials._tabla_disponibilidad', compact(
        'today', 'cisternasFalla', 'enRuta', 'totalCisternas','camionesFalla', 'despachosHoy', 
        'camionetasFalla', 'camionetasOperativas', 'totalLivianos','totalCamiones', 'totalChutos',
        'chutosFalla', 'chutosOperativos', 'camionesOperativos', 
        'total', 'operativosCount', 'fallaCount', 'porcentajeDisponibilidad','cisternasOperativas','utilizacionFlota',
        'chutosEnRuta', 'camionetasEnRuta', 'camionesEnRuta', 'cisternasEnRuta'

    ))->render();
}

    /**
     * Procesa y guarda los vehículos del archivo cargado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importSave(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            $this->service->procesarImportacion($request->file('file'));
            Session::flash('success', 'Importación completada.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error en importación: ' . $e->getMessage());
        }
        return Redirect::back();
    }

    public function ubicacionGeneral()
    {
        // Filtramos unidades que tengan GPS (evitamos lat/lng en 0 o null)
        $unidades = Vehiculo::with(['isMarca','isModelo'])->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->get(['id', 'placa', 'marca', 'modelo', 'tipo', 'estatus', 'latitud', 'longitud', 'updated_at']);

        return view('vehiculo.ubicacion_general', compact('unidades'));
    }

    public function apiUbicaciones()
    {
        $unidades = Vehiculo::with(['isMarca','isModelo'])->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->get(['id', 'placa', 'marca', 'modelo', 'tipo', 'estatus', 'latitud', 'longitud', 'updated_at']);
        return response()->json($unidades);
    }

    public function vistaHistorial(Request $request, $id = null)
    {
        // Obtenemos los vehículos para el select del buscador
        $vehiculos = Vehiculo::with(['isMarca','isModelo'])->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)->orderBy('placa', 'asc')->get();
        $desde = $request->get('desde', now()->subDay()->format('Y-m-d\TH:i'));
        $hasta = $request->get('hasta', now()->format('Y-m-d\TH:i'));

        return view('vehiculo.historial', compact('vehiculos', 'id', 'desde', 'hasta'));
    }
    
    public function historial(Request $request, $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        
        // Si no vienen fechas en el GET, usamos las de las últimas 24h por defecto
        $desde = $request->get('desde', now()->subDay()->format('Y-m-d\TH:i'));
        $hasta = $request->get('hasta', now()->format('Y-m-d\TH:i'));

        return view('vehiculo.historial', compact('vehiculo', 'id', 'desde', 'hasta'));
    }
    
}
