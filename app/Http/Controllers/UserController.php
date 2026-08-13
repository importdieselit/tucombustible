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
use App\Models\Personal;
use App\Models\Chofer;
use App\Models\Cargo;
use App\Models\Persona;
use App\Models\Modulo;
use App\Models\Sedes;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    protected UserService $userService;
    protected int $moduloIdUsuarios = 51; // ID del módulo de usuarios

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth');
        $this->model = new User(); // Asignación para BaseController
    }

    /**
     * Vista principal / Dashboard filtrado por cliente.
     */
    public function index(?Request $request = null)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permisos para acceder a este módulo.');
        }

        $clienteId = $user->cliente_id;
        $stats     = $this->userService->obtenerDashboardData($clienteId);
        $usuarios  = $this->userService->obtenerListaFiltrada($request->all(), $clienteId);

        return view('usuario.index', array_merge($stats, ['usuarios' => $usuarios]));
    }

    /**
     * Muestra las métricas (KPIs) generales de usuarios por perfil.
     */
    public function index_adm()
    {
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para ver el dashboard de usuarios.');
        }
        
        $clienteId = auth()->user()->cliente_id;
        
        $perfilesConteo = DB::table('users')
            ->select('id_perfil as id', 'perfiles.nombre as perfil', DB::raw('COUNT(*) as total'))
            ->when($clienteId !== 0, function ($query) use ($clienteId) {
                $query->where('cliente_id', $clienteId); 
            })
            ->join('perfiles', 'users.id_perfil', '=', 'perfiles.id')
            ->groupBy('id_perfil', 'perfiles.nombre')
            ->orderBy('total', 'desc')
            ->get();

            // Directorio de usuarios con relaciones precargadas
    $usuarios = User::with(['perfil', 'persona'])
        ->when($clienteId !== 0, function ($query) use ($clienteId) {
            $query->where('cliente_id', $clienteId);
        })
        ->latest()
        ->get();

        $totalGeneral = $perfilesConteo->sum('total');

        return view('usuario.index', compact('perfilesConteo', 'totalGeneral', 'usuarios'));
    }

    /**
     * Aplica filtros de negocio a las consultas del BaseController.
     */
    protected function applyBusinessFilters(Builder $query): Builder
    {
        $filterKey = request()->get('filter');
        
        if ($filterKey && $filterKey === 'id_perfil') {
            $value = request()->get('value');
            $query->where('id_perfil', $value);
        }
        
        return $query;
    }

    /**
     * Retorna el listado paginado/filtrado reutilizando la base.
     */
    public function list($query = null)
    {
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para ver la lista de usuarios.');
        }

        return parent::list($query);    
    }

    /**
     * Formulario de creación de usuario.
     */
    public function create()
    {
        if (!auth()->user()->canAccess('create', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para crear usuarios.');
        }
        
        $perfiles = Perfil::all();
        $clientes = Cliente::all();
        $cargos   = Cargo::all();
        $sedes = Sedes::all();
        
        return view('usuario.create_edit', compact('perfiles', 'clientes', 'cargos', 'sedes'));
    }

    /**
     * Almacena un nuevo usuario.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->canAccess('create', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para crear usuarios.');
        }

        // Validaciones base y condicionales para choferes
        $validated = $request->validate([
            // Datos de Persona / Usuario
            'nombre'                         => 'required|string|max:255',
            'dni'                            => 'required|string|max:50|unique:personas,dni',
            'email'                          => 'required|email|unique:users,email',
            'password'                       => 'required|min:6',
            'id_perfil'                      => 'required|exists:perfiles,id',
            'id_sede'                        => 'nullable|integer',
            'cargo_id'                       => 'nullable|exists:cargo,id',
            'telefono'                       => 'nullable|string|max:20',
            
            // Checkbox de Chofer o validación condicional
            'es_chofer'                      => 'nullable|boolean',
            'licencia_numero'                => 'required_if:es_chofer,1|nullable|string|max:100',
            'licencia_vencimiento'           => 'required_if:es_chofer,1|nullable|date',
            'tipo_licencia'                  => 'nullable|string|max:50',
            'documento_vialidad_numero'      => 'nullable|string|max:100',
            'documento_vialidad_vencimiento' => 'nullable|date',
            'certificado_medico'             => 'nullable|string|max:100',
            'certificado_medico_vencimiento' => 'nullable|date',
            
            // Archivos adjuntos para choferes
            'foto'                           => 'nullable|image|max:2048',
            'soporte_licencia'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'soporte_certificado'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'soporte_documento'              => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // 1. Crear Registro en 'personas'
            $persona = Persona::create([
                'nombre'        => $request->input('nombre'),
                'dni'           => $request->input('dni'),
                'dni_exp'       => $request->input('dni_exp'),
                'telefono'      => $request->input('telefono'),
                'address'       => $request->input('address'),
                'city'          => $request->input('city'),
                'state'         => $request->input('state'),
                'country'       => $request->input('country'),
                'date_of_birth' => $request->input('date_of_birth'),
                'gender'        => $request->input('gender'),
                'notes'         => $request->input('notes'),
                'cargo_id'      => $request->input('cargo_id'),
            ]);

            // 2. Crear Registro en 'users' (asociado al id_persona)
            $user = User::create([
                'name'       => $request->input('name', $request->input('nombre')),
                'email'      => $request->input('email'),
                'password'   => Hash::make($request->input('password')),
                'id_perfil'  => $request->input('id_perfil'),
                'id_persona' => $persona->id,
                'id_sede'    => $request->input('id_sede'),
                'cliente_id' => $request->input('id_cliente'),
                'status'     => 1,
            ]);

            // 3. Crear Registro en 'personal' (Si aplica cargo o sede)
            if ($request->filled('cargo_id') || $request->filled('id_sede')) {
                Personal::create([
                    'id_persona' => $persona->id,
                    'id_usuario' => $user->id,
                    'id_sede'    => $request->input('id_sede'),
                    'cargo_id'   => $request->input('cargo_id'),
                    'telefono'   => $request->input('telefono'),
                    'email'      => $request->input('email'),
                    'estatus'    => 1,
                    'fecha_in'   => now(),
                ]);
            }

            // 4. Crear Registro en 'choferes' (Si está marcado como chofer o llenó licencia)
            if ($request->boolean('es_chofer') || $request->filled('licencia_numero')) {
                $choferData = [
                    'persona_id'                     => $persona->id,
                    'licencia_numero'                => $request->input('licencia_numero'),
                    'licencia_vencimiento'           => $request->input('licencia_vencimiento'),
                    'tipo_licencia'                  => $request->input('tipo_licencia'),
                    'documento_vialidad_numero'      => $request->input('documento_vialidad_numero'),
                    'documento_vialidad_vencimiento' => $request->input('documento_vialidad_vencimiento'),
                    'certificado_medico'             => $request->input('certificado_medico'),
                    'certificado_medico_vencimiento' => $request->input('certificado_medico_vencimiento'),
                    'vehiculo_id'                    => $request->input('vehiculo_id'),
                ];

                // Subida de archivos / soportes digitales
                if ($request->hasFile('foto')) {
                    $choferData['foto'] = $request->file('foto')->store('choferes/fotos', 'public');
                }
                if ($request->hasFile('soporte_licencia')) {
                    $choferData['soporte_licencia'] = $request->file('soporte_licencia')->store('choferes/licencias', 'public');
                }
                if ($request->hasFile('soporte_certificado')) {
                    $choferData['soporte_certificado'] = $request->file('soporte_certificado')->store('choferes/certificados', 'public');
                }
                if ($request->hasFile('soporte_documento')) {
                    $choferData['soporte_documento'] = $request->file('soporte_documento')->store('choferes/documentos', 'public');
                }

                Chofer::create($choferData);
            }

            DB::commit();

            Session::flash('success', 'Usuario y registros asociados creados exitosamente.');
            return Redirect::route('usuarios.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Session::flash('error', 'Error al registrar el usuario: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }

    /**
     * Detalle del usuario y carga de matriz de permisos/módulos.
     */
    public function show($id) 
    {
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para ver detalles de usuarios.');
        }    

        view()->share('modulos', Modulo::all());
        
        return parent::show($id);
    }

    /**
     * Formulario de edición de usuario.
     */
    public function edit($id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para editar usuarios.');
        }
        
        $item = User::findOrFail($id);
        $perfiles = Perfil::all();
        $clientes = Cliente::all();
        $cargos   = Cargo::all();
        // 1. Obtenemos todos los módulos
        $todos = Modulo::orderBy('modulo', 'asc')->get();

        // 2. Filtramos los padres y los combinamos linealmente con sus hijos
        $modulos = $todos->where('id_padre', 0)->flatMap(function ($padre) use ($todos) {
            $hijos = $todos->where('id_padre', $padre->id);
            return collect([$padre])->concat($hijos);
        });
        
        return view('usuario.create_edit', compact('item', 'perfiles', 'clientes', 'cargos', 'modulos'));
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para editar usuarios.');
        }
        
        $item = User::findOrFail($id);
        $data = $this->prepareData($request, $item);

        try {
            $item->update($data);
            Session::flash('success', 'Usuario actualizado exitosamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al actualizar el usuario: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
        
        return Redirect::route('usuarios.show', $id);
    }

    /**
     * Elimina un usuario del sistema.
     */
    public function destroy($id) 
    {
        if (!auth()->user()->canAccess('delete', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para eliminar usuarios.');
        }
        
        return parent::destroy($id);
    }

    /**
     * Obtiene los permisos individuales (AJAX para Modal).
     */
    public function getPermissions($id)
    {
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $user = User::findOrFail($id);
        $modules = Modulo::select('id', 'modulo', 'icono')->get();
        $permissions = DB::table('accesos')
            ->where('id_usuario', $id)
            ->get()
            ->keyBy('id_modulo');

        return response()->json([
            'user' => $user,
            'modules' => $modules,
            'permissions' => $permissions
        ]);
    }

    /**
     * Actualiza un permiso individual en la tabla 'accesos' vía AJAX.
     */
    public function updateSinglePermission(Request $request, $id)
    {
        if (!auth()->user()->canAccess('update', $this->moduloIdUsuarios)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'modulo_id' => 'required|integer|exists:modulos,id',
            'accion'    => 'required|string|in:read,create,update,delete',
            'estado'    => 'required|boolean'
        ]);

        DB::table('accesos')->updateOrInsert(
            [
                'id_usuario' => $id,
                'id_modulo'  => $request->modulo_id,
            ],
            [
                $request->accion => $request->estado ? 1 : 0,
                'updated_at'     => now(),
                'created_at'     => DB::raw('COALESCE(created_at, NOW())')
            ]
        );

        return response()->json(['status' => 'success', 'message' => 'Permiso actualizado correctamente.']);
    }

    /**
     * Muestra la vista de importación masiva.
     */
    public function import()
    {
        return view('usuario.import');
    }

    /**
     * Procesa la importación de usuarios desde CSV.
     */
    public function handleImport(Request $request)
    {
        if (!$request->hasFile('file')) {
            Session::flash('error', 'No se ha seleccionado ningún archivo para subir.');
            return Redirect::back();
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:32768',
        ]);

        if ($validator->fails()) {
            Session::flash('error', 'El archivo no tiene el formato correcto o excede el tamaño máximo permitido.');
            return Redirect::back();
        }

        try {
            DB::beginTransaction();

            $coleccion = Excel::toCollection(null, $request->file('file'))->first();

            if ($coleccion->isEmpty() || count($coleccion) < 2) {
                throw new \Exception("El archivo está vacío o no contiene registros válidos.");
            }

            $filas = $coleccion->skip(1);
            
            foreach ($filas as $fila) {
                if ($fila->filter()->isEmpty()) {
                    continue;
                }

                $nombre_persona = (string) ($fila[5] ?? '');
                $dni_persona    = (string) ($fila[4] ?? '');
                $nombre_empresa = (string) ($fila[2] ?? '');
                $rif_empresa    = (string) ($fila[1] ?? '');

                if (empty($dni_persona) || empty($nombre_persona) || empty($rif_empresa)) {
                    Log::warning("Fila omitida por datos faltantes.", ['fila' => $fila]);
                    continue;
                }
                
                $cliente = Cliente::firstOrCreate(
                    ['rif' => $rif_empresa],
                    ['nombre' => $nombre_empresa]
                );

                $persona = Persona::firstOrCreate(
                    ['dni' => ($fila[3] ?? '') . $dni_persona],
                    ['nombre' => $nombre_persona]
                );

                $validateUser = User::where('cliente_id', $cliente->id)->where('id_master', 0)->first();
                $masterUser   = $validateUser ? $validateUser->id : 0;

                $user = User::firstOrCreate(
                    ['id_persona' => $persona->id],
                    [
                        'name'       => $nombre_persona,
                        'email'      => str_replace('.', '', $dni_persona) . '@tucombustible.com',
                        'password'   => bcrypt('123456789'),
                        'id_perfil'  => 3, // Perfil predeterminado: Cliente
                        'cliente_id' => $cliente->id,
                        'id_master'  => $masterUser
                    ]
                );

                $user->update([
                    'name'       => $nombre_persona,
                    'cliente_id' => $cliente->id,
                ]);
            }

            DB::commit();
            Session::flash('success', '¡Usuarios importados exitosamente!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en importación de usuarios: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Session::flash('error', 'Error al importar usuarios: ' . $e->getMessage());
        }

        return Redirect::back();
    }

    /**
     * Muestra la vista obligatoria de cambio de contraseña.
     */
    public function showChangePassword()
    {
        if (Auth::user()->must_change_password != 1) {
            return redirect()->route('dashboard');
        }

        return view('auth.passwords.change');
    }

    /**
     * Procesa la actualización obligatoria de contraseña.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.'
        ]);

        $user = Auth::user();

        $this->userService->actualizarPasswordObligatorio($user->id, $request->password);

        if ($user->id_perfil == 3 && $user->cliente && $user->cliente->registro_paso == 1) {
            $user->cliente->update(['registro_paso' => 2]);
        }

        return redirect()->route('dashboard')->with('success', 'Contraseña actualizada correctamente.');
    }

    /**
     * Prepara y valida los datos para crear o actualizar un usuario.
     */
    protected function prepareData(Request $request, $item = null): array
    {
        $rules = [
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . ($item->id ?? 'NULL'),
            'id_perfil'  => 'required|exists:perfiles,id',
            'cliente_id' => 'nullable|exists:clientes,id',
        ];

        if (!$item) {
            $rules['password'] = 'required|string|min:8';
        } else if ($request->filled('password')) {
            $rules['password'] = 'string|min:8';
        }

        $validated = $request->validate($rules);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        return $validated;
    }

    public function toggleEstatus($id)
    {
        try {
            $usuario = User::findOrFail($id);

            // Soporta formatos numéricos (1/0) o en texto ('activo'/'bloqueado')
            if (is_numeric($usuario->status)) {
                $usuario->status = ($usuario->status == 1) ? 0 : 1;
            } else {
                $usuario->status = (strtolower($usuario->status) === 'activo') ? 'bloqueado' : 'activo';
            }

            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'El estatus del usuario ha sido actualizado correctamente.',
                'nuevo_estatus' => $usuario->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el cambio de estatus: ' . $e->getMessage()
            ], 500);
        }
    }

    
}