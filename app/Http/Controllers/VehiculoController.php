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
use Illuminate\Support\Facades\Auth;


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

     protected function applyBusinessFilters(Builder $query): Builder
    {
        $filterKey = request()->get('filter'); // Usamos el helper global 'request()'
        
        if ($filterKey) {
            switch ($filterKey) {
                
                case 'mantenimiento':
                    $query->VehiculosEnMantenimiento();
                    break;

                case 'documentos_alerta':
                    $query->VehiculosConDocumentosEnAlerta(Auth::user()->cliente_id);
                    break;
                
                case 'disponibles':
                    // Filtro genérico que solo aplica si Vehiculo tiene columna 'estatus'
                    $query->disponibles();
                    break;
                case 'con_orden_abierta':
                    $query->VehiculosConOrdenAbierta();
                    break;
                
            }
        }

        return $query; // Devolvemos el Query Builder modificado
    }

    public function create()
    {
        $marcas = Marca::pluck('marca', 'id');
        $modelos = Modelo::pluck('modelo', 'id');
        $clientes = Cliente::pluck('name', 'id');
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
        
        // Si la validación pasa, continuamos y llamamos al método store del padre.
        return parent::store($request);
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
                
                $rowData = array_combine($header, $row);
//                $vard=var_dump($rowData);


                // // Lógica de búsqueda y creación de marca y modelo
                // $marcaNombre = $rowData['marca'] ?? null;
                // $modeloNombre = $rowData['modelo'] ?? null;
                // $marcaId = null;
                // $modeloId = null;

                // if ($marcaNombre) {
                //     $marca = Marca::firstOrCreate(['nombre' => $marcaNombre]);
                //     $marcaId = $marca->id;

                //     if ($modeloNombre && $marcaId) {
                //         $modelo = Modelo::firstOrCreate(
                //             ['nombre' => $modeloNombre, 'id_marca' => $marcaId],
                //             ['id_marca' => $marcaId]
                //         );
                //         $modeloId = $modelo->id;
                //     }
                // }
                 $vehiculo=Vehiculo::where('placa', $rowData['PLACAS'])->first();
                    if($vehiculo){
                        $tiposVehiculo= TipoVehiculo::where('tipo', $rowData['TIPO'])->first();
                        $vehiculo->color = $rowData['COLOR'] ?? $vehiculo->color;
                        $vehiculo->kilometraje = $rowData['KILOMETRAJE'] ?? $vehiculo->kilometraje;
                        //$vehiculo->observacion = $rowData['detalles'] ?? $vehiculo->observacion;
                        $vehiculo->serial_motor = $rowData['SERIAL_MOTOR'] ?? $vehiculo->serial_motor;
                        $vehiculo->serial_carroceria = $rowData['SERIAL_CARROCERIA'] ?? $vehiculo->serial_carroceria;
                        $vehiculo->tipo = $tiposVehiculo->id ?? $vehiculo->tipo;
                        $vehiculo->anno = $rowData['AÑO'] ?? $vehiculo->anno;
                        $vehiculo->sucursal = $rowData['EMPRESA'] ?? $vehiculo->sucursal;
                        $vehiculo->carga_maxima = $rowData['CAPACIDAD'] ?? 0;
                        $vehiculo->gps = $rowData['GPS'] ?? $vehiculo->gps;
                        $vehiculo->es_flota = true;
                        $vehiculo->save();
                    }


                    // // Preparar los datos del vehículo
                    // $vehiculoData = [
                    // //    'flota' => $rowData['vehiculo'] ?? null,
                    //     'placa' => $rowData['placas'] ?? null,
                    //     'estatus' => $rowData['estatus'] ?? null,
                    //     'id_marca' => $marcaId,
                    //     'id_modelo' => $modeloId,
                    //     'kilometraje' => $rowData['kilometraje'] ?? 0,
                    //     'observacion' => $rowData['detalles'] ?? null,
                    //     'rotc_venc' => $rowData['rotc'], 
                    //     'poliza_fecha' => $rowData['poliza de seguro'], 
                    //     'rcv'=> $rowData['rcv'], 
                    //     'racda' => $rowData['racda'], 
                    //     'semcamer' => $rowData['semcamer'], 
                    //     'homologacion_intt' => $rowData['homologacion_intt'],
                    //     'permiso_intt' => $rowData['permiso_intt']
                        
                    // ];

                    // // Crear el registro del vehículo en la base de datos
                    // Vehiculo::create($vehiculoData);
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
