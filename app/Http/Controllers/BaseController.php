<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str; // Es necesario para la función Str::plural()
use App\Models\EstatusData;
use App\Traits\PluralizaEnEspanol;
use App\Traits\GenerateAlerts;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;  
use Illuminate\Support\Facades\Schema;  

/**
 * Clase base para controladores de recursos CRUD que infiere el modelo.
 * Los controladores específicos que hereden de esta clase no necesitan constructor.
 */
abstract class BaseController extends Controller
{

     use GenerateAlerts;

    use PluralizaEnEspanol;
    /**
     * Instancia del modelo de Eloquent.
     * @var Model
     */
    protected $model;

    /**
     * Define el namespace por defecto para los modelos.
     * @var string
     */
    protected $modelNamespace = 'App\\Models\\';

    public function __construct()
    {
        // Se obtiene el nombre de la clase que hereda (ej: 'VehiculoController').
        $childClassName = class_basename($this);
        
        // Se infiere el nombre del modelo eliminando 'Controller' (ej: 'Vehiculo').
        $modelName = str_replace('Controller', '', $childClassName);

        // Se construye el nombre completo de la clase del modelo.
        $modelClass = $this->modelNamespace . $modelName;

        // Se verifica que la clase del modelo exista antes de instanciarla.
        if (!class_exists($modelClass)) {
            throw new \Exception("Model class '{$modelClass}' not found for controller '{$childClassName}'.");
        }

        // Se crea la instancia del modelo dinámicamente.
        $this->model = new $modelClass();
    }

    /**
     * Obtiene el nombre del modelo en minúsculas (ej: 'vehiculo').
     * @return string
     */
    protected function getModelNameLowerCase()
    {
        $modelName = class_basename($this->model);
        if($modelName=='User'){
            $modelName='Usuario';
        }
        return strtolower($modelName);
    }

    /**
     * Devuelve el nombre del modelo en minúsculas y plural para las rutas.
     * Ej: 'vehiculos'
     * @return string
     */
    protected function getPluralModelNameLowerCase()
    {
        $singularName = $this->getModelNameLowerCase();
        return $this->getSpanishPlural($singularName); // Usa la nueva función del Trait
        // return Str::plural($singularName); --- IGNORE ---
    }

    /**
     * Muestra una lista de todos los recursos.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data = $this->model->all();
        return view($this->getModelNameLowerCase() . '.index', compact('data'));
    }

    /**
     * Muestra una lista de todos los recursos. Este método puede ser sobrescrito para vistas de listado.
     * @return \Illuminate\View\View
     */
    public function list($query=null) // Aún recibe el Query Builder inicial
    {
       
       $user = auth()->user();
       $cliente = Cliente::find($user->cliente_id);
        if(is_null($query)){
            $query = $this->model->query();
            
        }
        
        if (method_exists($this, 'applyBusinessFilters')) {
            $query = $this->applyBusinessFilters($query); 
        }
        

        $tableName = $this->model->getTable();

        if (Schema::hasColumn($tableName, 'id_cliente')) {

            if ($user->cliente_id === 0) {
                // 1. SUPER USUARIO (cliente_id == 0)
                // No se aplica ningún filtro, obtiene todos los registros.
            } elseif ($cliente && $cliente->parent === 0) {
                // 2. CLIENTE PRINCIPAL / PADRE

                // Obtener los IDs de todos los clientes hijos
                $subClientIds = Cliente::where('parent', $user->cliente_id)->pluck('id'); 
                $allowedClientIds = $subClientIds->push($user->cliente_id);
                $query->whereIn('id_cliente', $allowedClientIds);

            } else {
                // 3. CLIENTE HIJO o CLIENTE REGULAR SIN JERARQUÍA
                $query->where('id_cliente', $user->cliente_id);
            }
        }

        $data = $query->get();
        //    $data = $this->model->all();
        $estatusData = EstatusData::all()->keyBy('id_estatus');
        return view($this->getModelNameLowerCase() . '.list', compact('data', 'estatusData'));
    }

    /**
     * Muestra el formulario para crear un nuevo recurso.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view($this->getModelNameLowerCase() . '.create');
    }

    /**
     * Almacena un nuevo recurso en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $this->model->create($request->all());
            Session::flash('success', class_basename($this->model) . ' creado exitosamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al crear el registro: ' . $e->getMessage());
        }

        return Redirect::route($this->getPluralModelNameLowerCase() . '.list');
    }

    /**
     * Muestra el recurso especificado.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show($id)
    {
        try {
            $estatusData = EstatusData::all()->keyBy('id_estatus');
            $item = $this->model->findOrFail($id);
            return view($this->getModelNameLowerCase() . '.show', compact('item', 'estatusData'));
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'El registro no fue encontrado.');
            return Redirect::route($this->getPluralModelNameLowerCase() . '.list');
        }
    }

    /**
     * Muestra el formulario para editar el recurso especificado.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            $item = $this->model->findOrFail($id);
            return view($this->getModelNameLowerCase() . '.edit', compact('item'));
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'El registro no fue encontrado.');
            return Redirect::route($this->getPluralModelNameLowerCase() . '.list');
        }
    }

    /**
     * Actualiza el recurso especificado en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $item = $this->model->findOrFail($id);
            $item->update($request->all());
            Session::flash('success', class_basename($this->model) . ' actualizado exitosamente.');
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'El registro no fue encontrado.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al actualizar el registro: ' . $e->getMessage());
        }
        
        return Redirect::route($this->getPluralModelNameLowerCase() . '.show', $id);
    }

    /**
     * Elimina el recurso especificado de la base de datos.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $item = $this->model->findOrFail($id);
            $item->delete();
            Session::flash('success', class_basename($this->model) . ' eliminado exitosamente.');
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'El registro no fue encontrado.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al eliminar el registro: ' . $e->getMessage());
        }
        
        return Redirect::route($this->getPluralModelNameLowerCase() . '.list');
    }
}
