<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Viaje;
use App\Models\Pedido;
use App\Services\{ClienteService, DashboardService};
use App\Services\GascoCupoService;
use App\Services\PedidoService;
use App\Models\{Estado, Ciudad};
use App\Models\TipoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log};

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
}