<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\{ClienteService, DashboardService};
use App\Services\GascoCupoService;
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
        
        // 1. Cargamos la data base (Aquí viene el cliente con sus relaciones)
        $data = $this->dashboardService->getDashboardData($user, $sucursalId);
        $cliente = $data['cliente'];

        // 2. Consulta de GASCO
        // IMPORTANTE: Este método debe llamar internamente a 'getOrCreateMonthlyQuota' 
        // del repositorio para asegurar que si es un mes nuevo, se cree el registro.
        $infoGasco = $this->gascoCupoService->obtenerSaldoActual($cliente->id);
        
        // 3. REFRESH DEL MODELO
        // Si el paso anterior creó un registro de mes nuevo, actualizó la DB pero NO esta instancia.
        // Con refresh() nos aseguramos que $cliente->disponible sea el valor real de la DB.
        $cliente->refresh();

        // 4. MAPEO DE LÓGICA RESTAURADA PARA LA VISTA
        
        // Cupo SIAVCOM: Ahora lo sacamos de la tabla relacional (cliente_cupos)
        $data['cupoSiavcom'] = $cliente->cupos->first()->litros_aprobados ?? 0;

        // Cupo GASCO: El autorizado para el mes actual
        $data['cupoGasco'] = $infoGasco['autorizados'] ?? 0;

        // Disponible: El campo que el Repo mantiene (Reset mensual - Despachos)
        $data['disponible'] = $cliente->disponible;

        // 5. Carga de Pedidos y resto de la lógica
        $data['pedidos'] = app(\App\Services\PedidoService::class)
                            ->listarPedidosParaUsuario($cliente);

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