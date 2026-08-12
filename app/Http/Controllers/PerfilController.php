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

class PerfilController extends BaseController
{
    private $moduloAdministrarId = 5; 
    private $moduloIdPerfiles = 52; 

    public function __construct()
    {
        $this->model = new Perfil();
        parent::__construct();
    }
    
    /**
     * Vista Única: Carga los perfiles y la jerarquía de módulos para los modales.
     */
    public function index($query = null)
    {
        if (!auth()->user()->canAccess('read', $this->moduloAdministrarId)) {
            abort(403, 'No tiene permiso para ver la lista de perfiles.');
        }
        
        $perfiles = Perfil::withCount('users')->get(); // Agregamos conteo de usuarios como KPI útil    
        $modulosData = $this->getModuloData(); 

        return view('perfiles.index', compact('perfiles') + $modulosData);
    }

    /**
     * Helper para armar la jerarquía de módulos.
     */
    protected function getModuloData(): array
    {
        $allModulos = Modulo::orderBy('id_padre')->orderBy('orden')->get();

        $modulosJerarquicos = collect();
        $modulosHijos = [];

        foreach ($allModulos as $modulo) {
            if ($modulo->id_padre == 0) {
                $modulo->hijos = collect();
                $modulosJerarquicos->push($modulo);
            } else {
                $modulosHijos[$modulo->id_padre][] = $modulo;
            }
        }

        foreach ($modulosJerarquicos as $padre) {
            if (isset($modulosHijos[$padre->id])) {
                $padre->hijos = collect($modulosHijos[$padre->id]);
            }
        }
        
        return ['modulos' => $modulosJerarquicos];
    }

    /**
     * Guarda un nuevo perfil (Retorna a la vista única).
     */
    public function store(Request $request)
    {
        if (!auth()->user()->canAccess('create', $this->moduloIdPerfiles)) {
            abort(403, 'No tiene permiso para crear perfiles.');
        }

        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:perfiles,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        $this->model->create($validatedData);
        
        Session::flash('success', 'Perfil creado exitosamente.');
        return Redirect::route('perfiles.index');
    }

    /**
     * Actualiza los datos básicos del perfil.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloIdPerfiles)) {
            abort(403, 'No tiene permiso para editar perfiles.');
        }

        $item = $this->model->findOrFail($id);
        
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:perfiles,nombre,' . $item->id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        $item->update($validatedData);
        
        Session::flash('success', 'Perfil actualizado exitosamente.');
        return Redirect::route('perfiles.index'); 
    }

    /**
     * AJAX: Obtiene los permisos de un perfil para cargar el Modal.
     */
    public function getPermissions($id)
    {
        if (!auth()->user()->canAccess('read', $this->moduloAdministrarId)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $permisosActuales = PermisoPerfil::where('id_perfil', $id)->get()->keyBy('id_modulo');
        return response()->json(['permisos' => $permisosActuales]);
    }

    /**
     * Actualiza la matriz completa de permisos de un perfil.
     */
    public function updatePermissions(Request $request, $id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloAdministrarId)) {
            abort(403, 'No tiene permiso para modificar permisos.');
        }
        
        $perfil = Perfil::findOrFail($id);
        $permissions = $request->input('permissions', []); 
        $dataToSync = [];
        $now = now();

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

        DB::beginTransaction();
        try {
            PermisoPerfil::where('id_perfil', $perfil->id)->delete();
            if (!empty($dataToSync)) {
                DB::table('permiso_perfil')->insert($dataToSync);
            }
            DB::commit();
            Session::flash('success', 'Permisos del perfil actualizados exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('error', 'Error al actualizar: ' . $e->getMessage());
        }

        return Redirect::route('perfiles.index');
    }

    /**
     * AJAX: Toggle estado activo/inactivo
     */
    public function toggleEstatus($id)
    {
        try {
            $perfil = Perfil::findOrFail($id);
            $perfil->activo = !$perfil->activo;
            $perfil->save();
            return response()->json(['success' => true, 'nuevo_estatus' => $perfil->activo]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}