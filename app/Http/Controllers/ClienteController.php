<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Viaje;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use App\Services\{ClienteService, DashboardService};
use App\Services\GascoCupoService;
use App\Models\{Estado, Ciudad};
use App\Models\TipoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Redirect, Session, Auth, Log};

class ClienteController extends Controller
{
    protected ClienteService $clienteService;
    protected DashboardService $dashboardService;
    protected GascoCupoService $gascoCupoService;

    public function __construct(ClienteService $clienteService, DashboardService $dashboardService, GascoCupoService $gascoCupoService)
    {
        $this->clienteService   = $clienteService;
        $this->dashboardService = $dashboardService;
        $this->gascoCupoService = $gascoCupoService;
    }

    // -------------------------------------------------------
    // REGISTRO PÚBLICO DE CLIENTES
    // -------------------------------------------------------

    public function showRegistrationForm()
    {
        $estados          = Estado::orderBy('nombre', 'asc')->get();
        $tiposCombustible = TipoCombustible::all();

        return view('auth.register_cliente', compact('estados', 'tiposCombustible'));
    }

    public function store(Request $request)
    {
        $rifCompleto = strtoupper($request->rif_tipo . '-' . $request->rif_numero);
        $request->merge(['rif' => $rifCompleto]);

        $rules = [
            'rif'                 => 'required|string|max:20',
            'nombre'              => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email',
            'contacto'            => 'required|string|max:255',
            'telefono'            => 'required|string|max:20',
            'estado_id'           => 'required|exists:estados,id',
            'ciudad_id'           => 'required|exists:ciudades,id',
            'direccion'           => 'nullable|string',
            'direccion_operativa' => 'required|string',
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'litros_solicitados'  => 'required|numeric|min:1',
        ];

        if ($request->tipo_cliente === 'sucursal') {
            $rules['token_padre'] = 'required|exists:clientes,token_registro';
        }

        $request->validate($rules, [
            'token_padre.exists'         => 'El Código de Empresa Principal (Token) ingresado no es válido.',
            'token_padre.required'       => 'Debe ingresar el Token de la empresa principal para vincular la sucursal.',
            'tipo_combustible_id.exists' => 'Debe seleccionar un tipo de combustible válido.',
            'litros_solicitados.min'     => 'Debe solicitar al menos 1 litro.',
        ]);

        try {
            $this->clienteService->registrarCliente($request->all());
            return redirect()->route('login')
                ->with('success', 'Registro exitoso. Ingrese con su RIF sin guiones como contraseña.');
        } catch (\Exception $e) {
            Log::error('Error en registro de cliente: ' . $e->getMessage());
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // DASHBOARD DEL CLIENTE
    // -------------------------------------------------------

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->cliente) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'No tiene un expediente asociado.');
        }

        $sucursalId = $request->query('sucursal_id');
        $data = $this->dashboardService->getDashboardData($user, $sucursalId);
        $cliente = $data['cliente'];

        // 1. FILTROS PARA PEDIDOS
        $p_search = $request->query('p_search');
        $p_status = $request->query('p_status');
        $p_desde  = $request->query('p_desde');
        $p_hasta  = $request->query('p_hasta');
        
        $queryPedidos = Pedido::where('cliente_id', $cliente->id)
            ->with(['tipoCombustible'])
            ->orderBy('fecha_solicitud', 'desc');

        if ($p_search) {
            $queryPedidos->where('id', 'LIKE', "%{$p_search}%");
        }
        if ($p_status) {
            $queryPedidos->where('estado', $p_status);
        }
        if ($p_desde && $p_hasta) {
            // Se agrega la hora para cubrir el día completo
            $queryPedidos->whereBetween('fecha_solicitud', [$p_desde . ' 00:00:00', $p_hasta . ' 23:59:59']);
        }
        
        $data['pedidos'] = $queryPedidos->paginate(10, ['*'], 'pedidos_page')->appends($request->all());

        // 2. FILTROS PARA PLANIFICACIONES (Viajes)
        $v_search = $request->query('v_search');
        $v_status = $request->query('v_status');
        $v_desde  = $request->query('v_desde');
        $v_hasta  = $request->query('v_hasta');

        $queryPlan = Viaje::where('status', '!=', 'BORRADOR')
            ->where(function($query) use ($cliente) {
                $query->where('cliente_id', $cliente->id)
                    ->orWhereHas('detalles', function($q) use ($cliente) {
                        $q->where('cliente_id', $cliente->id);
                    });
            })
            ->with(['vehiculo', 'chofer.persona', 'ayudante.persona', 'sede', 'detalles.cliente', 'detalles.buques', 'tipoCombustible'])
            ->orderBy('fecha_salida', 'desc');

        if ($v_search) {
            $queryPlan->where(function($q) use ($v_search) {
                $q->where('id', 'LIKE', "%{$v_search}%")
                ->orWhere('vehiculo_externo', 'LIKE', "%{$v_search}%")
                ->orWhereHas('vehiculo', function($qv) use ($v_search) { 
                    $qv->where('placa', 'LIKE', "%{$v_search}%"); 
                });
            });
        }
        if ($v_status) {
            $queryPlan->where('status', $v_status);
        }
        if ($v_desde && $v_hasta) {
            $queryPlan->whereBetween('fecha_salida', [$v_desde . ' 00:00:00', $v_hasta . ' 23:59:59']);
        }

        $data['planificaciones'] = $queryPlan->paginate(10, ['*'], 'planificaciones_page')->appends($request->all());

        // Lógica de Gasco y Perfiles
        $infoGasco = $this->gascoCupoService->obtenerSaldoActual($cliente->id);
        $cliente->refresh();
        $data['cupoSiavcom'] = $cliente->cupos->first()->litros_aprobados ?? 0;
        $data['cupoGasco'] = $infoGasco['autorizados'] ?? 0;
        $data['disponible'] = $cliente->disponible;
        $data['viendoSucursal'] = $request->filled('sucursal_id');

        if (in_array($data['perfil'], ['cliente_en_registro', 'cliente_rechazado', 'cliente_inactivo'])) {
            return view("cliente." . str_replace('cliente_', '', $data['perfil']), $data);
        }

        return view('cliente.index', $data);
    }

    public function perfil()
    {
        // NO uses compact('cliente'), llama al index para que cargue TODO
        return $this->index(new Request());
    }

    // -------------------------------------------------------
    // AUXILIARES
    // -------------------------------------------------------

    public function getCiudades($estado_id)
    {
        return Ciudad::where('estado_id', $estado_id)
            ->orderBy('nombre', 'asc')
            ->get();
    }

    public function updatePerfil(Request $request)
    {
        // Seguridad: El ID se extrae directamente del usuario autenticado
        $id = auth()->user()->cliente_id; 

        // Normalizamos a mayúsculas antes de validar y guardar
        $data = $request->all();
        if ($request->filled('contacto')) $data['contacto'] = strtoupper($request->contacto);
        if ($request->filled('contacto_alt')) $data['contacto_alt'] = strtoupper($request->contacto_alt);
        if ($request->filled('direccion')) $data['direccion'] = strtoupper($request->direccion);
        if ($request->filled('direccion_operativa')) $data['direccion_operativa'] = strtoupper($request->direccion_operativa);
        $request->merge($data);

        $request->validate([
            'nombre'              => 'required|string|max:255',
            'email'               => 'required|email:rfc,dns|max:255',
            'contacto'            => 'required|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono'            => 'nullable|digits_between:10,11',
            'contacto_alt'        => 'nullable|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono_alt'        => 'nullable|digits_between:10,11',
            'estado_id'           => 'nullable|exists:estados,id',
            'ciudad_id'           => 'nullable|exists:ciudades,id',
            'direccion'           => 'nullable|string',
            'direccion_operativa' => 'nullable|string',
        ], [
            'contacto.regex'              => 'El campo Persona de Contacto solo debe contener letras.',
            'contacto_alt.regex'          => 'El campo Contacto Alternativo solo debe contener letras.',
            'email.email'                 => 'El correo electrónico debe ser una dirección válida con @.',
            'telefono.digits_between'     => 'El teléfono debe tener entre 10 y 11 dígitos.',
            'telefono_alt.digits_between' => 'El teléfono alternativo debe tener entre 10 y 11 dígitos.',
        ]);

        try {
            // Reutilizamos el repositorio para actualizar los datos permitidos
            app(ClienteRepository::class)->update($id, $request->only([
                'nombre', 'rif', 'email', 'contacto', 'telefono',
                'contacto_alt', 'telefono_alt',
                'estado_id', 'ciudad_id', 'direccion', 'direccion_operativa',
            ]));

            Session::flash('success', 'Tus datos han sido actualizados correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil desde el portal de clientes: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', 'Error al procesar la actualización.');
        }
    }

    public function registrarPlaca(Request $request)
    {
        $request->validate([
            'placa'      => 'required|string|max:8',
            'cliente_id' => 'required|integer'
        ]);

        $padreId  = auth()->user()->cliente_id;
        $targetId = $request->cliente_id;

        // Validar que el destino sea el perfil propio o una de sus sucursales
        if ($targetId != $padreId) {
            $esSucursal = Cliente::where('id', $targetId)->where('parent', $padreId)->exists();
            if (!$esSucursal) abort(403, 'No tienes permiso para registrar en esta sucursal.');
        }

        $placaFormateada = strtoupper(trim($request->placa));

        try {
            $this->clienteService->registrarPlaca($targetId, $placaFormateada);
            Session::flash('success', 'Placa registrada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar placa: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function inactivarPlaca(Request $request, $placaId)
    {
        $padreId  = auth()->user()->cliente_id;
        // Si no viene el cliente_id, asumimos el del padre por seguridad
        $targetId = $request->input('cliente_id', $padreId); 

        if ($targetId != $padreId) {
            $esSucursal = Cliente::where('id', $targetId)->where('parent', $padreId)->exists();
            if (!$esSucursal) abort(403, 'Acción no autorizada.');
        }

        try {
            // Verificar que la placa pertenece al cliente que se está visualizando
            $cliente = $this->clienteService->obtenerExpediente($targetId);
            $poseePlaca = $cliente->placas()->where('id', $placaId)->exists();
            
            if (!$poseePlaca) abort(403, 'Esta placa no pertenece a este registro.');

            $this->clienteService->inactivarPlaca($placaId);
            Session::flash('success', 'Placa inactivada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function registrarChofer(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'cedula'          => 'required|string|max:15',
            'cliente_id'      => 'required|integer'
        ]);

        $padreId  = auth()->user()->cliente_id;
        $targetId = $request->cliente_id;

        if ($targetId != $padreId) {
            $esSucursal = Cliente::where('id', $targetId)->where('parent', $padreId)->exists();
            if (!$esSucursal) abort(403, 'No tienes permiso para registrar en esta sucursal.');
        }

        $nombreFormateado = strtoupper(trim($request->nombre_completo));

        try {
            $this->clienteService->registrarChofer($targetId, $nombreFormateado, $request->cedula);
            Session::flash('success', 'Personal autorizado registrado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar chofer desde cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function inactivarChofer(Request $request, $choferId)
    {
        $padreId  = auth()->user()->cliente_id;
        $targetId = $request->input('cliente_id', $padreId);

        if ($targetId != $padreId) {
            $esSucursal = Cliente::where('id', $targetId)->where('parent', $padreId)->exists();
            if (!$esSucursal) abort(403, 'Acción no autorizada.');
        }

        try {
            $cliente = $this->clienteService->obtenerExpediente($targetId);
            $poseeChofer = $cliente->choferes()->where('id', $choferId)->exists();
            
            if (!$poseeChofer) abort(403, 'Este chofer no pertenece a este registro.');

            $this->clienteService->inactivarChofer($choferId);
            Session::flash('success', 'Personal autorizado removido correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }
}