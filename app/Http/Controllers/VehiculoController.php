<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo; // El modelo sigue siendo necesario para las vistas
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\User;
use App\Models\TipoVehiculo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use App\Http\Requests\VehiculoStoreRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str; // Es necesario para la función Str::plural()
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Traits\GenerateAlerts;
use App\Traits\PluralizaEnEspanol;
use App\Models\VehiculoFoto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


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
                
                case 'mantenimiento':
                    $query->VehiculosEnMantenimiento();
                    break;

                case 'documentos_alerta':
                    $query->ConDocumentosEnAlerta(Auth::user()->cliente_id);
                    break;
                
                case 'disponibles':
                    // Filtro genérico que solo aplica si Vehiculo tiene columna 'estatus'
                    $query->disponibles();
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
        
        // La lógica de la vista se hereda del BaseController, pero con los datos adicionales.
        return view($this->getModelNameLowerCase() . '.create', compact('marcas', 'modelos', 'clientes', 'tiposVehiculo'));
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

        // Se pasa el vehículo y los datos adicionales a la vista.
        return view($this->getModelNameLowerCase() . '.edit', compact('item', 'marcas', 'modelos', 'clientes', 'tiposVehiculo'));
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

            $vehiculo=Vehiculo::create($request->all());

            $this->handleFotoUpload($request, $vehiculo);

            Session::flash('success', 'Vehiculo creado exitosamente.');
            Log::info('Vehiculo creado exitosamente.');
        } catch (\Exception $e) {
            Log::info('Error al crear el registro: ' . $e->getMessage());
            Session::flash('error', 'Error al crear el registro: ' . $e->getMessage());

        }

        return Redirect::route('vehiculos.list');

    }

    public function updateV(Request $request)
    {
        $vehiculo=Vehiculo::findOrFail($request->id);
        
        app(VehiculoStoreRequest::class);
        DB::beginTransaction();
        try {
            // 1. Actualizar datos del vehículo
            $vehiculo->update($request->validated());

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
    

     public function importForm()
    {
        return view('vehiculo.import');
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
            $rows = Excel::toArray(null, $request->file('file'))[0];
            $header = array_map('trim', array_change_key_case($rows[0], CASE_LOWER));
            $dataRows = array_slice($rows, 1);

            foreach ($dataRows as $row) {
                // Si la fila está vacía, la ignoramos para evitar errores
                if (empty(array_filter($row))) {
                    continue;
                }


                $rotc_venc=null;
                $rowData = array_combine($header, $row);
//                $vard=var_dump($rowData);

                // // Lógica de búsqueda y creación de marca y modelo
                $marcaNombre = $rowData['MARCA'] ?? null;
                $modeloNombre = $rowData['MODELO'] ?? null;
                $marcaId = null;
                $modeloId = null;
                $flota = explode(' ', $rowData['VEHICULO']);

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
                
                $tiposVehiculo= TipoVehiculo::where('tipo', $rowData['TIPO'])->first();
                if(is_null($tiposVehiculo)){ dd( $rowData['TIPO']);}
                $vehiculo=Vehiculo::where('placa', $rowData['PLACAS'])->first();
                if($vehiculo){
                    $vehiculo->flota = $flota[1].' '.$flota[2];
                    $vehiculo->color = $rowData['COLOR'] ?? $vehiculo->color;
                    $vehiculo->kilometraje = $rowData['KILOMETRAJE'] ?? $vehiculo->kilometraje;
                    $vehiculo->observacion = $rowData['OBSERVACION'].' - '.$rowData['DETALLES'] ?? $vehiculo->observacion;
                    $vehiculo->serial_motor = $rowData['SERIAL DEL MOTOR'] ?? $vehiculo->serial_motor;
                    $vehiculo->serial_carroceria = $rowData['SERIAL DE CARROCERIA'] ?? $vehiculo->serial_carroceria;
                    $vehiculo->tipo = $tiposVehiculo->id ?? $vehiculo->tipo;
                    $vehiculo->poliza_fecha_out = $poliza;
                    $vehiculo->rcv=  trim($RCV);
                    $vehiculo->rotc=  !is_null($rotc)?$rotc:'PENDIENTE';
                    $vehiculo->rotc_venc=  $rotc_venc;
                    $vehiculo->racda=  $rowData['RACDA'];
                    //$vehiculo->anno = $rowData['AÑO'] ?? $vehiculo->anno;
                    $vehiculo->agencia = $rowData['EMPRESA'] ?? $vehiculo->agencia;
                    $vehiculo->carga_max = $rowData['ALMACENAMIENTO'] ?? 0;
                    $vehiculo->consumo = $rowData['AUTONOMIA'] ?? 0;
                //  $vehiculo->gps = $rowData['GPS']== 'SI' ? true : false;
                    $vehiculo->marca= $marcaId;
                    $vehiculo->semcamer = $rowData['SEMCAMER'];
                    $vehiculo->homologacion_intt= $rowData['HOMOLOGACION INTT'];
                    $vehiculo->modelo= $modeloId;
                    $vehiculo->es_flota = true;
                    $vehiculo->save();
                }else{

                   // // Preparar los datos del vehículo
                    $vehiculoData = [
                        'flota' =>$flota[1].' '.$flota[2],
                        'color' => $rowData['COLOR'],
                        //'kilometraje' => $rowData['KILOMETRAJE'],
                        'observacion' => $rowData['OBSERVACION'].' - '.$rowData['DETALLES'],
                        'serial_motor' => $rowData['SERIAL DEL MOTOR'],
                        'serial_carroceria' => $rowData['SERIAL DE CARROCERIA'],
                        'tipo' => $tiposVehiculo->id,
                        'poliza_fecha_out' => $poliza,
                        'rcv'=>  trim($RCV),
                        'rotc'=>  !is_null($rotc)?$rotc:'PENDIENTE',
                        'rotc_venc'=>  $rotc_venc,
                        'racda'=>  $rowData['RACDA'],
                       // 'anno' => $rowData['AÑO'],
                        'agencia' => $rowData['EMPRESA'],
                        'carga_max' => $rowData['ALMACENAMIENTO'],
                        'consumo' => $rowData['AUTONOMIA'],
                      //  $vehiculo->gps = $rowData['GPS']== 'SI' ? true : false;
                        'marca'=> $marcaId,
                        'semcamer' => $rowData['SEMCAMER'],
                        'homologacion_intt'=> $rowData['HOMOLOGACION INTT'],
                        'modelo'=> $modeloId,
                        'es_flota' => true
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
