<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;
use App\Models\Perfil;
use App\Models\Cliente;
use App\Models\Persona;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;



class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->canAccess('read', 51)) {
            abort(403, 'No tiene permisos para acceder a este módulo.');
        }

        $clienteId = $user->cliente_id;
        $stats     = $this->userService->obtenerDashboardData($clienteId);
        $usuarios  = $this->userService->obtenerListaFiltrada($request->all(), $clienteId);

        return view('usuarios.index', array_merge($stats, ['usuarios' => $usuarios]));
    }

    /**
     * Display a listing of the resource.
     */
    public function index_adm()
    {
        // 1. Verificación de Permiso
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para ver el dashboard de usuarios.');
        }
        
        // 2. Lógica de Conteo (Tus variables)
        $clienteId = auth()->user()->cliente_id;
        
        // Consulta para obtener el conteo de usuarios por perfil
        $perfilesConteo = DB::table('users')
            ->select('id_perfil as id','perfiles.nombre as perfil', DB::raw('COUNT(*) as total'))
            ->when($clienteId !== 0, function ($query) use ($clienteId) {
                // Aplicar el filtro de seguridad de cliente si no es Super Admin
                $query->where('cliente_id', $clienteId); 
            })
            ->join('perfiles', 'users.id_perfil', '=', 'perfiles.id')
            ->groupBy('id_perfil','perfiles.nombre')
            ->orderBy('total', 'desc')
            ->get();

        // Obtener el total general
        $totalGeneral = $perfilesConteo->sum('total');

        // 3. Devolver la vista del Dashboard/Index con las variables
        // Esto asume que tienes la vista en resources/views/usuarios/index.blade.php
        // y que mostrará las cards (KPIs) en lugar del listado.
        return view('usuario.index', compact('perfilesConteo', 'totalGeneral'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create_old()
    {
        $perfiles = Perfil::all(); 
        //return view('users.create', compact('perfiles')); // CAMBIADO: Pasar 'perfiles'
    }

     protected function applyBusinessFilters(Builder $query): Builder
    {
        $filterKey = request()->get('filter'); // Usamos el helper global 'request()'
        
        if ($filterKey) {
            $value = request()->get('value'); // Valor del filtro
            switch ($filterKey) {
                
                case 'id_perfil':
                     $query->where('id_perfil', $value);
                    break;                
            }
        }
        
        return $query; // Devolvemos el Query Builder modificado
    }


    public function list($query = null)
    {
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para ver la lista de usuarios.');
        }
        // 2. Llama al método list() del padre. 
        return parent::list($query);    
    }


    public function store(Request $request)
    {
        if (!auth()->user()->canAccess('create', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para crear usuarios.');
        }
        $data = $this->prepareData($request);
        
        return parent::store(new Request($data));
    }
    
    public function update(Request $request, $id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para editar usuarios.');
        }
        
        $item = $this->model->findOrFail($id);
        $data = $this->prepareData($request, $item);

        try {
            $item->update($data);
            Session::flash('success', 'Usuario actualizado exitosamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
        
        return Redirect::route($this->getPluralModelNameLowerCase() . '.show', $id);
    }

    /**
     * Display the specified resource.
     */
      public function show($id) 
        {
            if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
                abort(403, 'No tiene permiso para ver detalles de usuarios.');
            }    
            $query = $this->model->with(['perfil', 'cliente', 'persona']);
            
            view()->share('modulos', \App\Models\Modulo::all());
        
            return parent::show($id);
        }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit_old(User $user)
    {
        $perfiles = Perfil::all();
        // return view('users.edit', compact('user', 'perfiles')); // CAMBIADO: Pasar 'perfiles'
    }

    /**
     * Remove the specified resource from storage.
     */
     public function destroy($id) 
    {
        if (!auth()->user()->canAccess('delete', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para eliminar usuarios.');
        }
        
        // Usamos la lógica del padre para eliminar y manejar redirección/mensajes.
        return parent::destroy($id);
    }

     public function import()
    {
        return view('usuario.import');
    }

    /**
     * Procesa el archivo subido e importa los usuarios.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleImport(Request $request)
    {
       // Validación crucial: comprueba si el archivo realmente fue subido.
        if (!$request->hasFile('file')) {
            Session::flash('error', 'No se ha seleccionado ningún archivo para subir.');
            return Redirect::back();
        }

        // Validación para asegurar que el archivo es del tipo correcto.
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:32768', // Máximo 32MB. Asegúrate de que este valor coincida con la configuración de PHP.
        ]);

        if ($validator->fails()) {
            Session::flash('error', 'El archivo no tiene el formato correcto o excede el tamaño máximo permitido.');
            return Redirect::back();
        }

        $file = $request->file('file');
        try {
            DB::beginTransaction();

            // 2. Obtener la primera hoja del archivo como una colección.
            $coleccion = Excel::toCollection(null, $request->file('file'))->first();

            // 3. Validar que la colección no esté vacía.
            if ($coleccion->isEmpty() || count($coleccion) < 2) {
                throw new \Exception("El archivo está vacío o la hoja de datos no contiene registros.");
            }

            // 4. Omitir la primera fila (encabezados) para empezar con los datos.
            $filas = $coleccion->skip(1);
            
            // 5. Recorrer cada fila para procesar los datos.
            foreach ($filas as $fila) {
                // Si la fila está vacía, la saltamos.
                if ($fila->filter()->isEmpty()) {
                    continue;
                }

                // Mapeamos los datos de la fila a las variables.
                // Asegúrate de que los índices coincidan con tu archivo CSV.
                $nombre_persona = (string) ($fila[5] ?? '');
                $dni_persona = (string) ($fila[4] ?? '');
                $nombre_empresa = (string) ($fila[2] ?? '');
                $rif_empresa = (string) ($fila[1] ?? '');

                // Lógica de validación básica.
                if (empty($dni_persona) || empty($nombre_persona) || empty($rif_empresa)) {
                    Log::warning("Fila omitida por datos de Cédula, Nombre o RIF faltantes.", ['fila' => $fila]);
                    continue;
                }
                
                // 6. Encontrar o crear la empresa (Cliente).
                $cliente = Cliente::firstOrCreate(
                    ['rif' => $rif_empresa],
                    [
                        'nombre' => $nombre_empresa,
                        // Añadir más campos del cliente si están disponibles en el archivo de importación.
                    ]
                );

                // 7. Encontrar o crear la persona.
                $persona = Persona::firstOrCreate(
                    ['dni' => $fila[3].$dni_persona],
                    [
                        'nombre' => $nombre_persona,
                        // Añadir más campos de la persona.
                    ]
                );
                $validateUser = User::where('cliente_id', $cliente->id)->where('id_master',0)->first();
                if($validateUser){
                    $masterUser = $validateUser->id;
                }else{
                    $masterUser = 0; // ID del usuario master por defecto
                }
                // 8. Encontrar o crear el usuario y vincularlo.
                // Usamos la cédula como nombre de usuario (email) y contraseña por defecto.
                $user = User::firstOrCreate(
                    ['id_persona' => $persona->id],
                    [
                        'name' => $nombre_persona,
                        'email' => str_replace('.','',$dni_persona) . '@tucombustible.com', // Correo por defecto, debe ser único
                        'password' => bcrypt(123456789), // Contraseña por defecto
                        'id_perfil' => 3, // Asignamos el perfil de cliente (ajustar si es necesario)
                        'cliente_id' => $cliente->id,
                        'id_master' => $masterUser // Asignar un master por defecto o según la lógica de tu aplicación
                    ]
                );

                // Si el usuario ya existe, actualizamos sus datos para asegurar la consistencia.
                $user->update([
                    'name' => $nombre_persona,
                    'cliente_id' => $cliente->id,
                ]);

            }

            DB::commit();

            Session::flash('success', '¡Usuarios importados exitosamente!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al importar los usuarios: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Session::flash('error', 'Hubo un error al importar los usuarios: ' . $e->getMessage());
        }

        return Redirect::back();

    }

    public function create()
    {
        if (!auth()->user()->canAccess('create', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para crear usuarios.');
        }
        
        $perfiles = Perfil::all();
        $clientes = Cliente::all();
        
        return view('usuario.create_edit', compact('perfiles', 'clientes'));
    }

    /**
     * Sobrescribe el método edit para pasar datos adicionales a la vista.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para editar usuarios.');
        }
        
        $item = $this->model->findOrFail($id);
        $perfiles = Perfil::all();
        $clientes = Cliente::all();
        $modulos = \App\Models\Modulo::all(); // Para mostrar permisos en la vista
        
        return view('usuario.create_edit', compact('item', 'perfiles', 'clientes', 'modulos'));
    }

    public function editPermissions($id)
    {
        // 1. Validar acceso (Siguiendo tu estándar de seguridad)
        if (!auth()->user()->canAccess('update', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene autorización para editar permisos.');
        }

        // 2. Buscar el usuario
        $usuario = $this->model->with('perfil')->findOrFail($id);

        // 3. Obtener los módulos para la matriz de permisos
        $modulos = \App\Models\Modulo::all(); 

        // 4. Retornar la vista (puedes crear una específica o usar un modal)
        return view('usuario.edit_permissions', compact('usuario', 'modulos'));
    }

    public function updateSinglePermission(Request $request, $id)
    {
        // Validar acceso rápido
        if (!auth()->user()->canAccess('update', $this->moduloIdUsuarios)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $usuario = User::findOrFail($id);
        
        // Lógica para actualizar la tabla pivot
        // Suponiendo que usas una relación 'modulos' con columnas r, w, d
        $columna = $request->accion; // 'r', 'w' o 'd'
        
        $usuario->modulos()->updateExistingPivot($request->modulo_id, [
            $columna => $request->estado
        ]);

        return response()->json(['status' => 'ok']);
    }
    
    

    /**
    * Procesa el cambio de contraseña.
    */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.'
        ]);

        $user = Auth::user();

        // 1. Actualizamos la contraseña y removemos el flag de cambio obligatorio
        $this->userService->actualizarPasswordObligatorio($user->id, $request->password);

        // 2. AUTOMATIZACIÓN PASO 1 -> 2
        // Si el usuario es un cliente y está en el paso inicial de registro, lo movemos a carga de documentos
        if ($user->id_perfil == 3 && $user->cliente && $user->cliente->registro_paso == 1) {
            $user->cliente->update(['registro_paso' => 2]);
        }

        return redirect()->route('dashboard')->with('success', 'Contraseña actualizada correctamente. Acceso concedido.');
    }

    public function showChangePassword()
    {
        if (Auth::user()->must_change_password != 1) {
            return redirect()->route('dashboard');
        }

        return view('auth.passwords.change');
    }
}