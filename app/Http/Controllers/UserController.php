<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Perfil; // CAMBIADO: Usar tu modelo Perfil
use App\Models\Persona;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class UserController extends BaseController
{

    private $moduloIdUsuarios = 51; // ID del módulo 'Usuarios'
    private $perfilClienteName = 'cliente'; // Nombre del perfil que requiere id_cliente
    private $defaultClienteId = 0; // ID para perfiles que no son de cliente


    public function __construct()
    {
        $this->model = new User();
        parent::__construct();
    }

    /**
     * Prepara los datos del formulario (create/edit)
     */
    protected function prepareData(Request $request, ?User $item = null): array
    {
        $data = $request->except(['password', 'password_confirmation']);
        $currentProfile = $request->input('perfil', $item ? $item->perfil : null);

        // 1. Manejo de Contraseña (Solo si se envía o si es nuevo)
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        } elseif (!$item) {
            // Si es un nuevo registro y no hay contraseña (debería fallar la validación, pero por seguridad)
            // Se asume que el frontend tiene validación
        } else {
            // Si es edición y la contraseña está vacía, la eliminamos para no sobrescribir el hash
            unset($data['password']);
        }

        // 2. Lógica de Asignación de id_cliente
        if ($currentProfile === $this->perfilClienteName) {
            // Perfil 'cliente': mantiene el id_cliente enviado por el formulario
            // Si no se envía (y es requerido), la validación debe fallar.
        } else {
            // Otros perfiles: forzar el id_cliente al valor por defecto (0)
            $data['id_cliente'] = $this->defaultClienteId;
        }

        return $data;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('perfil')->paginate(10); // CAMBIADO: Cargar la relación 'perfil'
   //     return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create_old()
    {
        $perfiles = Perfil::all(); // CAMBIADO: Obtener perfiles
        //return view('users.create', compact('perfiles')); // CAMBIADO: Pasar 'perfiles'
    }

    public function list($query = null)
    {
        if (!auth()->user()->canAccess('read', $this->moduloIdUsuarios)) {
            abort(403, 'No tiene permiso para ver la lista de usuarios.');
        }
    
        // 2. Llama al método list() del padre. 
        //    El BaseController aplicará los filtros de seguridad del cliente.
        return parent::list($query); 
    
    }


    // Sobrescribe el método store para hashear la contraseña y asignar id_cliente
    public function store(Request $request)
    {
        if (!auth()->user()->canAccess('create', $this->moduloIdUsuarios)) {
             abort(403, 'No tiene permiso para crear usuarios.');
        }

        $data = $this->prepareData($request);
        
        // Se usa el método store del BaseController
        return parent::store(new Request($data));
    }
    
    // Sobrescribe el método update para manejar la contraseña opcional y asignar id_cliente
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
    public function show(User $user)
    {
       // return redirect()->route('users.edit', $user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit_old(User $user)
    {
        $perfiles = Perfil::all(); // CAMBIADO: Obtener perfiles
       // return view('users.edit', compact('user', 'perfiles')); // CAMBIADO: Pasar 'perfiles'
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente.');
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
        Log::info('Archivo recibido', ['file_name' => $file->getClientOriginalName()]);
        ;
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
        
        $perfiles = Perfil::pluck('nombre_perfil')->toArray();
        $clientes = Cliente::all();
        
        return view('usuarios.create_edit', compact('perfiles', 'clientes'));
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
        $perfiles = Perfil::pluck('nombre_perfil')->toArray();
        $clientes = Cliente::all();
        
        return view('usuarios.create_edit', compact('item', 'perfiles', 'clientes'));
    }
    
}
