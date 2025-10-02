<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Modulo;
use App\Models\PermisoPerfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// Hereda de BaseController para listar, crear, etc.
class PerfilController extends BaseController
{
    private $moduloAdministrarId = 5; // ID del módulo 'Administrar'
    private $moduloIdPerfiles = 52; // ID del módulo 'Perfiles'

    
    public function __construct()
    {
        $this->model = new Perfil();
        parent::__construct();
    }
    
    // Sobrescribe el método 'index' para listar y añadir validación de permisos
    public function index($query = null)
    {
        // El ID del módulo 'Administrar' (5) y la acción 'read'
        if (!auth()->user()->canAccess('read', $this->moduloAdministrarId)) {
            abort(403, 'No tiene permiso para ver la lista de perfiles.');
        }
        
        // Llama al método list() del BaseController
        $query = Perfil::query();
        return $this->list($query); 
    }

    protected function getModuloData(): array
    {
        // 1. Obtener todos los módulos y ordenarlos por el campo 'orden' (asumiendo que existe)
        $allModulos = Modulo::orderBy('id_padre')
                            ->orderBy('orden') // Asumiendo un campo 'orden' para el ordenamiento
                            ->get();

        $modulosJerarquicos = collect();
        $modulosHijos = [];

        // 2. Separar padres (id_padre = 0) de hijos (id_padre > 0)
        foreach ($allModulos as $modulo) {
            if ($modulo->id_padre == 0) {
                // Inicializar el padre
                $modulo->hijos = collect();
                $modulosJerarquicos->push($modulo);
            } else {
                // Guardar hijos temporalmente por id_padre
                $modulosHijos[$modulo->id_padre][] = $modulo;
            }
        }

        // 3. Asignar los hijos a sus respectivos padres
        foreach ($modulosJerarquicos as $padre) {
            if (isset($modulosHijos[$padre->id])) {
                $padre->hijos = collect($modulosHijos[$padre->id]);
            }
        }
        
        // Devolvemos la estructura jerárquica
        return [
            'modulos' => $modulosJerarquicos, // Nueva variable que usaremos en la vista
        ];
    }


     public function create(): View
    {
        // 1. Verificación de Permisos
        if (!auth()->user()->canAccess('create', $this->moduloIdPerfiles)) {
            abort(403, 'No tiene permiso para crear perfiles.');
        }
        
        // 2. Obtiene datos específicos
        $data = $this->getModuloData();
        
        // 3. Retorna la vista con los datos
        // Usa la convención de nombres de vista: 'perfiles.create_edit'
        return view('usuario.perfil_create_edit', $data);
    }
    
    /**
     * Muestra el formulario de edición de perfil.
     * @param int $id
     * @return View
     */
    public function edit($id): View
    {
        // 1. Verificación de Permisos
        if (!auth()->user()->canAccess('update', $this->moduloIdPerfiles)) {
            abort(403, 'No tiene permiso para editar perfiles.');
        }
        
        // 2. Obtiene el ítem usando la referencia del modelo de BaseController
        $item = $this->model->findOrFail($id); 
        
        // 3. Obtiene datos específicos
        $extraData = $this->getModuloData();

        // 4. Retorna la vista con el ítem y los datos extra
        return view('usuario.perfil_create_edit', compact('item') + $extraData);
    }

    /**
 * Muestra el perfil especificado con su matriz de permisos.
 * @param int $id
 * @return View
 */
public function show($id): View
{
    if (!auth()->user()->canAccess('read', $this->moduloIdPerfiles)) {
        abort(403, 'No tiene permiso para ver este perfil.');
    }
    $MODULO_PERFILES = $this->moduloIdPerfiles; 
    $item = $this->model->findOrFail($id); 
    
    $extraData = $this->getModuloData(); // Reutiliza el método que crea la jerarquía
    $data = compact('item', 'MODULO_PERFILES') + $extraData;
    
    // 4. Retornar la vista 'perfiles.show'
    return view('usuario.perfil_show', $data);
}

    /**
     * Muestra la vista para configurar los permisos base de un perfil.
     * Esto reemplaza la vista 'edit' estándar de BaseController.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function editPermissions($id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloAdministrarId)) {
            abort(403, 'No tiene permiso para modificar permisos de perfiles.');
        }
        
        $perfil = Perfil::findOrFail($id);
        
        // Obtener todos los módulos y los permisos actuales del perfil
        $modulos = Modulo::orderBy('nombre')->get();
        $permisosActuales = $perfil->permisosBase->keyBy('id_modulo');

        return view('perfiles.permissions', compact('perfil', 'modulos', 'permisosActuales'));
    }

    protected function preparePermissionsForPivot(array $inputPermisos, int $perfilId): array
    {
        $dataToSync = [];
        $now = now();
        
        foreach ($inputPermisos as $moduleId => $actions) {
            // Se asume que los nombres de las acciones son 'read', 'update', 'create', 'delete'
            $dataToSync[] = [
                'id_perfil'  => $perfilId,
                'id_modulo'  => $moduleId,
                'read'       => $actions['read'] ?? 0,
                'update'     => $actions['update'] ?? 0,
                'create'     => $actions['create'] ?? 0,
                'delete'     => $actions['delete'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        return $dataToSync;
    }

    /**
     * Actualiza los permisos base del perfil en la tabla 'permiso_perfil'.
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePermissions(Request $request, $id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloAdministrarId)) {
            abort(403, 'No tiene permiso para modificar permisos de perfiles.');
        }
        
        $perfil = Perfil::findOrFail($id);
        $permissions = $request->input('permissions', []); // Array de [moduloId => ['read', 'create', ...]]
        $dataToSync = [];
        $now = now();

        // 1. Procesar la entrada del formulario
        foreach ($permissions as $moduleId => $actions) {
            $dataToSync[] = [
                'id_perfil' => $perfil->id,
                'id_modulo' => $moduleId,
                'read'      => in_array('read', $actions) ? 1 : 0,
                'update'    => in_array('update', $actions) ? 1 : 0,
                'create'    => in_array('create', $actions) ? 1 : 0,
                'delete'    => in_array('delete', $actions) ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 2. Transacción para asegurar la consistencia de los datos
        DB::beginTransaction();
        try {
            // Eliminar todos los permisos base actuales del perfil
            PermisoPerfil::where('id_perfil', $perfil->id)->delete();
            
            // Insertar la nueva matriz de permisos
            if (!empty($dataToSync)) {
                DB::table('permiso_perfil')->insert($dataToSync);
            }

            DB::commit();
            Session::flash('success', 'Permisos del perfil ' . $perfil->nombre . ' actualizados exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('error', 'Error al actualizar los permisos: ' . $e->getMessage());
        }

        return Redirect::route('perfiles.index');
    }

     public function store(Request $request)
    {
        if (!auth()->user()->canAccess('create', $this->moduloIdPerfiles)) {
            abort(403, 'No tiene permiso para crear perfiles.');
        }

        try {
            DB::beginTransaction(); // <-- Iniciar la transacción
            
            // 1. Validación (Asegurar que el nombre es único)
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255|unique:perfiles,nombre',
            ]);

            // 2. Crear el Perfil (Guardar solo los datos del perfil)
            $item = $this->model->create($validatedData);
            
            // 3. Procesar y guardar permisos en la tabla 'permiso_perfil'
            $permissionsData = $this->preparePermissionsForPivot($request->input('permisos', []), $item->id);

            if (!empty($permissionsData)) {
                DB::table('permiso_perfil')->insert($permissionsData);
            }

            DB::commit(); // <-- Confirmar la transacción
            
            Session::flash('success', 'Perfil **' . $item->nombre . '** creado exitosamente.');
            
            // Redirigir a la vista de detalle (asumo 'perfiles.show')
            return Redirect::route('perfiles.show', $item->id); 

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback(); // Deshacer si falla la validación
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollback(); // Deshacer si falla la inserción
            Session::flash('error', 'Error al crear el perfil: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }
    public function update(Request $request, $id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloIdPerfiles)) {
            abort(403, 'No tiene permiso para editar perfiles.');
        }

        $item = $this->model->findOrFail($id);

        try {
            DB::beginTransaction(); // <-- Iniciar la transacción
            
            // 1. Validación (Asegurar que el nombre es único, ignorando el actual)
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255|unique:perfiles,nombre,' . $item->id,
            ]);

            // 2. Actualizar el Perfil (Guardar solo los datos del perfil)
            $item->update($validatedData);
            
            // 3. Procesar y guardar permisos en la tabla 'permiso_perfil'
            // Eliminar permisos actuales
            PermisoPerfil::where('id_perfil', $item->id)->delete();
            
            // Insertar los nuevos permisos
            $permissionsData = $this->preparePermissionsForPivot($request->input('permisos', []), $item->id);

            if (!empty($permissionsData)) {
                DB::table('permiso_perfil')->insert($permissionsData);
            }

            DB::commit(); // <-- Confirmar la transacción
            
            Session::flash('success', 'Perfil **' . $item->nombre . '** actualizado exitosamente.');
            
            // Redirigir a la vista de detalle (asumo 'perfiles.show')
            return Redirect::route('perfiles.show', $item->id); 

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback(); // Deshacer si falla la validación
            return Redirect::back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollback(); // Deshacer si falla la inserción
            Session::flash('error', 'Error al actualizar el perfil: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }
}