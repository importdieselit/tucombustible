<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\{ClienteService, DashboardService};
use App\Models\{Estado, Ciudad};
use App\Models\TipoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log};

class ClienteController extends Controller
{
    protected ClienteService $clienteService;
    protected DashboardService $dashboardService;

    public function __construct(ClienteService $clienteService, DashboardService $dashboardService)
    {
        $this->clienteService   = $clienteService;
        $this->dashboardService = $dashboardService;
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
        
        // Verificación de seguridad básica
        if (!$user->cliente) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'No tiene un expediente asociado.');
        }

        // 1. Capturamos el ID de la sucursal si viene en la URL
        $sucursalId = $request->query('sucursal_id');

        // 2. Llamamos al servicio (Asegúrate de haber actualizado la firma del método en el DashboardService)
        $data = $this->dashboardService->getDashboardData($user, $sucursalId);

        // 3. CARGA DE PEDIDOS (Usando el cliente que determinó el servicio: Padre o Hijo)
        if (isset($data['cliente'])) {
            // Aquí $data['cliente'] ya es el HIJO si venimos por modo espejo
            $data['pedidos'] = app(\App\Services\PedidoService::class)
                                ->listarPedidosParaUsuario($data['cliente']);
        }

        // 4. SOLUCIÓN AL ERROR: Definimos la variable SIEMPRE
        // Si hay un sucursal_id en la URL, es true. Si no, es false.
        $data['viendoSucursal'] = $request->filled('sucursal_id');

        // Manejo de vistas según el perfil retornado por el servicio
        if ($data['perfil'] === 'cliente_en_registro') {
            return view('cliente.en_proceso', $data);
        }

        if ($data['perfil'] === 'cliente_rechazado') {
            return view('cliente.rechazado', $data);
        }

        if ($data['perfil'] === 'cliente_inactivo') {
            return view('cliente.inactivo', $data);
        }

        // Retornamos la vista principal con todas las variables definidas
        return view('cliente.index', $data);
    }

    // -------------------------------------------------------
    // PERFIL DEL CLIENTE
    // -------------------------------------------------------

    public function perfil()
    {
        $cliente = $this->clienteService->obtenerExpediente(Auth::user()->cliente_id);
        return view('cliente.perfil', compact('cliente'));
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