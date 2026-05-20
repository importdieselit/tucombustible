<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\PedidoRepository;
use App\Services\ClienteService;
use App\Services\ClienteLubricanteService;
use App\Services\GascoCupoService;
use App\Models\Cliente;
use App\Models\TipoCombustible;
use App\Models\Pedido;
use App\Models\Estado;
use App\Models\ClienteDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Redirect, Session, Log, Auth, Storage};

class ClienteController extends Controller
{
    protected ClienteService $clienteService;
    protected ClienteLubricanteService $lubricanteService;
    protected PedidoRepository $repository;
    protected GascoCupoService $gascoCupoService;

    public function __construct(
        ClienteService $clienteService,
        ClienteLubricanteService $lubricanteService,
        PedidoRepository $repository,
        GascoCupoService $gascoCupoService
    ) {
        $this->clienteService    = $clienteService;
        $this->lubricanteService = $lubricanteService;
        $this->repository        = $repository;
        $this->gascoCupoService  = $gascoCupoService;
    }

    // -------------------------------------------------------
    // LISTADO PRINCIPAL — CLIENTES COMBUSTIBLE
    // -------------------------------------------------------

    public function index(Request $request)
    {
        $filtros = $request->only(['search', 'status', 'tipo']);
        $data    = $this->clienteService->obtenerDashboardAdmin($filtros);

        // NUEVO: Filtro para la lista de pedidos
        $statusPedido = $request->query('status_pedido');
        $searchPedido = $request->query('search_pedido');
        $fechaDesde   = $request->query('fecha_desde');
        $fechaHasta   = $request->query('fecha_hasta');
        
        $queryPedidos = Pedido::with(['cliente'])
            ->orderBy('fecha_solicitud', 'desc');

        if ($statusPedido !== null && $statusPedido !== '') {
            $queryPedidos->where('estado', $statusPedido);
        }

        // 2. Filtro por Nombre de Cliente o RIF
        if ($searchPedido !== null && $searchPedido !== '') {
            $queryPedidos->whereHas('cliente', function($q) use ($searchPedido) {
                $q->where('nombre', 'LIKE', '%' . $searchPedido . '%')
                ->orWhere('rif', 'LIKE', '%' . $searchPedido . '%');
            });
        }

        // 3. Filtro por Rango de Fechas (Desde)
        if ($fechaDesde !== null && $fechaDesde !== '') {
            $queryPedidos->whereDate('fecha_solicitud', '>=', $fechaDesde);
        }

        // 4. Filtro por Rango de Fechas (Hasta)
        if ($fechaHasta !== null && $fechaHasta !== '') {
            $queryPedidos->whereDate('fecha_solicitud', '<=', $fechaHasta);
        }

        $ultimosPedidos = $queryPedidos->paginate(20, ['*'], 'pedidos_page');

        return view('admin.cliente.index', [
            'clientes'       => $data['clientes'],
            'stats'          => $data['stats'],
            'pasos'          => $data['pasos'],
            'filtros'        => $filtros,
            'rankingMayores' => $this->clienteService->getRankingCuposMayores(),
            'rankingMenores' => $this->clienteService->getRankingCuposMenores(),
            'ultimosPedidos' => $ultimosPedidos,
            'statusPedido'   => $statusPedido,
            'searchPedido'   => $searchPedido,
            'fechaDesde'     => $fechaDesde,
            'fechaHasta'     => $fechaHasta,
        ]);
    }

    // -------------------------------------------------------
    // EXPEDIENTE — VER DETALLE DE UN CLIENTE
    // -------------------------------------------------------

    public function show($id)
    {
        // Mantenemos tus servicios originales
        $cliente          = $this->clienteService->obtenerExpediente($id);
        $tiposCombustible = TipoCombustible::all();
        $infoGasco        = $this->gascoCupoService->obtenerSaldoActual($id);

        // NUEVO: Pedidos paginados (reemplaza tu llamada al repository para que sea paginada)
        $pedidos = Pedido::where('cliente_id', $id)
            ->orderBy('fecha_solicitud', 'desc')
            ->paginate(20, ['*'], 'pedidos_page');

        // NUEVO: Sucursales paginadas (solo si es padre)
        $sucursales = $cliente->es_padre 
            ? $cliente->sucursales()->paginate(15, ['*'], 'sucursales_page') 
            : collect();

        $documentos = ClienteDocumento::where('cliente_id', $cliente->id)->orderBy('created_at', 'desc')->get();
        $espacioUsadoBytes = ClienteDocumento::where('cliente_id', $cliente->id)->sum('peso_archivo');
        $espacioUsadoMb = round($espacioUsadoBytes / (1024 * 1024), 2);

        return view('admin.cliente.show', compact(
            'cliente', 
            'tiposCombustible', 
            'pedidos', 
            'infoGasco', 
            'sucursales',
            'documentos',
            'espacioUsadoMb'
        ));
    }

    public function listaGeneralPedidos()
    {
        // Usamos el método que ya tienes en el repositorio
        $pedidos = $this->repository->getAllPedidosAdmin();

        return view('admin.cliente.pedidos_general', compact('pedidos'));
    }

    public function create()
    {
        $tiposCombustible = TipoCombustible::all();
        $estados          = Estado::orderBy('nombre')->get();

        return view('admin.cliente.create', compact('tiposCombustible', 'estados'));
    }

    public function store(Request $request)
    {
        if ($request->has('rif_tipo') && $request->has('rif_numero')) {
            $request->merge([
                'rif' => strtoupper($request->rif_tipo) . '-' . $request->rif_numero
            ]);
        }

        $request->validate([
            'nombre'              => 'required|string|max:255',
            'rif'                 => 'required|string|max:12',
            'email'               => 'required|email:rfc,dns|max:255',
            'contacto'            => 'required|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono'            => 'nullable|digits_between:10,11',
            'contacto_alt'        => 'nullable|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono_alt'        => 'nullable|digits_between:10,11',
            'estado_id'           => 'required|exists:estados,id',
            'ciudad_id'           => 'required|exists:ciudades,id',
            'direccion'           => 'nullable|string',
            'direccion_operativa' => 'required|string',
            'tipo_cliente'        => 'nullable|in:padre,sucursal',
            'token_padre'         => 'nullable|string|exists:clientes,token_registro',
        ], [
            'contacto.regex'          => 'El nombre de contacto solo debe contener letras.',
            'contacto_alt.regex'      => 'El nombre de contacto alternativo solo debe contener letras.',
            'telefono.digits_between' => 'El teléfono debe tener entre 10 y 11 dígitos.',
            'telefono_alt.digits_between' => 'El teléfono alternativo debe tener entre 10 y 11 dígitos.',
            'token_padre.exists'      => 'El Token de la empresa principal no es válido.',
        ]);

        try {
            $this->clienteService->registrarCliente($request->all());
            Session::flash('success', 'Cliente registrado correctamente.');
            return Redirect::route('clientes.index');
        } catch (\Exception $e) {
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $cliente = $this->clienteService->obtenerExpediente($id);
        $estados = Estado::orderBy('nombre')->get();

        return view('admin.cliente.edit', compact('cliente', 'estados'));
    }

    public function update(Request $request, $id)
    {
        if ($request->has('rif_tipo') && $request->has('rif_numero')) {
            $request->merge([
                'rif' => strtoupper($request->rif_tipo) . '-' . $request->rif_numero
            ]);
        }

        $request->validate([
            'nombre'              => 'required|string|max:255',
            'rif'                 => 'required|string|max:15|unique:clientes,rif,' . $id,
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
            'rif.unique'                  => 'Este RIF ya se encuentra registrado en otro cliente.',
            'telefono.digits_between'     => 'El teléfono debe tener entre 10 y 11 dígitos.',
            'telefono_alt.digits_between' => 'El teléfono alternativo debe tener entre 10 y 11 dígitos.',
        ]);

        try {
            $this->clienteService->obtenerExpediente($id);

            app(\App\Repositories\ClienteRepository::class)->update($id, $request->only([
                'nombre', 'rif', 'email', 'contacto', 'telefono',
                'contacto_alt', 'telefono_alt',
                'estado_id', 'ciudad_id', 'direccion', 'direccion_operativa',
            ]));

            Session::flash('success', 'Datos del cliente actualizados correctamente.');
            return Redirect::route('clientes.show', $id);
        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function avanzarPaso(Request $request, $id)
    {
        $request->validate([
            'paso' => 'required|integer|exists:registro_pasos,id',
        ]);

        try {
            $this->clienteService->avanzarPaso($id, (int) $request->paso);
            Session::flash('success', 'Etapa de registro actualizada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al avanzar paso: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function aprobar(Request $request, $id)
    {
        try {
            $this->clienteService->aprobarCliente($id);

            Session::flash('success', 'Cliente aprobado.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al aprobar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function rechazar($id)
    {
        try {
            $this->clienteService->rechazarCliente($id);
            Session::flash('success', 'Cliente marcado como rechazado.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al rechazar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function inactivar($id)
    {
        try {
            $this->clienteService->inactivarCliente($id);
            Session::flash('success', 'Cliente inactivado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al inactivar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function reactivar($id)
    {
        try {
            $this->clienteService->reactivarCliente($id);
            Session::flash('success', 'Cliente reactivado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al reactivar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function ajustarCupo(Request $request, $id)
    {
        $request->validate([
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'litros_aprobados'    => 'required|numeric|min:1',
        ]);

        try {
            $this->clienteService->ajustarCupo(
                $id,
                (int) $request->tipo_combustible_id,
                (float) $request->litros_aprobados
            );

            Session::flash('success', 'Cupo ajustado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al ajustar cupo: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // REGISTRO DE PLACAS Y CHOFERES (Solo Admin)
    // -------------------------------------------------------

    public function registrarPlaca(Request $request, $id)
    {
        $request->validate(['placa' => 'required|string|max:8']);

        try {
            $this->clienteService->registrarPlaca($id, $request->placa);
            Session::flash('success', 'Placa registrada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar placa: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function inactivarPlaca($placaId)
    {
        try {
            $this->clienteService->inactivarPlaca($placaId);
            Session::flash('success', 'Placa inactivada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function registrarChofer(Request $request, $id)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'cedula'          => 'required|string|max:15',
        ]);

        try {
            $this->clienteService->registrarChofer($id, $request->nombre_completo, $request->cedula);
            Session::flash('success', 'Chofer registrado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar chofer: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function inactivarChofer($choferId)
    {
        try {
            $this->clienteService->inactivarChofer($choferId);
            Session::flash('success', 'Chofer inactivado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // CLIENTES LUBRICANTES
    // -------------------------------------------------------

    public function indexLubricantes()
    {
        $clientes = $this->lubricanteService->obtenerTodos();
        return view('admin.cliente.lubricantes.index', compact('clientes'));
    }

    public function storeLubricante(Request $request)
    {
        if ($request->has('rif_tipo') && $request->has('rif_numero')) {
            $request->merge([
                'rif' => strtoupper($request->rif_tipo) . '-' . $request->rif_numero
            ]);
        }

        $request->validate([
            'razon_social' => 'required|string|max:255',
            'rif'          => 'required|string|max:20|unique:clientes_lubricantes,rif',
            'email'        => 'required|email|max:255',
            'telefono'     => 'nullable|digits_between:10,11',
        ], [
            'razon_social.required'   => 'La razón social es obligatoria.',
            'rif.required'            => 'El RIF es obligatorio.',
            'rif.unique'              => 'Ya existe un cliente lubricante registrado con este RIF.',
            'email.required'          => 'El correo electrónico es obligatorio.',
            'email.email'             => 'El correo electrónico debe ser válido.',
            'telefono.digits_between' => 'El teléfono debe tener entre 10 y 11 dígitos.',
        ]);

        try {
            $this->lubricanteService->registrar($request->all());
            Session::flash('success', 'Cliente lubricante registrado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar cliente lubricante: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroyLubricante($id)
    {
        try {
            $this->lubricanteService->eliminar($id);
            Session::flash('success', 'Cliente lubricante eliminado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function asignarCupoGasco(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        
        // Tomamos el valor de la columna 'cupo' (SIAVCOM)
        $cupoGeneral = $cliente->cupo ?? 0;

        // Reglas base (siempre debe ser mayor a 100)
        $rules = [
            'litros_autorizados' => ['required', 'numeric', 'min:100']
        ];

        // LÓGICA NUEVA: Solo limitamos si el Cupo SIAVCOM es mayor o igual a 1
        if ($cupoGeneral >= 1) {
            $rules['litros_autorizados'][] = 'max:' . $cupoGeneral;
        }

        $messages = [
            'litros_autorizados.min' => 'La cantidad a asignar debe ser mayor a 100 litros.',
            'litros_autorizados.max' => 'No puede asignar más del Cupo SIAVCOM general (' . number_format($cupoGeneral, 0) . ' Ltrs).',
        ];

        $request->validate($rules, $messages);

        try {
            $this->gascoCupoService->asignarCupoMensual($id, $request->litros_autorizados);
            
            Session::flash('success', 'Cupo GASCO del mes actualizado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al asignar cupo GASCO: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Error técnico: ' . $e->getMessage());
        }
    }

    public function generarToken($id)
    {
        $cliente = Cliente::findOrFail($id);

        // Seguridad: Solo los padres (parent == 0) deben tener token
        if ($cliente->parent != 0) {
            return Redirect::back()->with('error', 'Solo los Clientes Padres pueden poseer un token de vinculación.');
        }

        // Evitar sobreescritura si ya existe uno
        if (!empty($cliente->token_registro)) {
            return Redirect::back()->with('error', 'Este cliente ya tiene un token asignado.');
        }

        try {
            // Generamos el token de 10 caracteres (Alfanumérico)
            // Str::random genera una cadena aleatoria alfanumérica
            $nuevoToken = strtoupper(\Illuminate\Support\Str::random(10));

            // Verificación de unicidad en la base de datos
            while (Cliente::where('token_registro', $nuevoToken)->exists()) {
                $nuevoToken = strtoupper(\Illuminate\Support\Str::random(10));
            }

            $cliente->update([
                'token_registro' => $nuevoToken
            ]);

            Session::flash('success', 'Token de 10 caracteres generado: ' . $nuevoToken);
            return Redirect::back();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al generar token: ' . $e->getMessage());
            return Redirect::back()->with('error', 'No se pudo generar el token por un error técnico.');
        }
    }

    // 2. Método para que el admin cargue (puedes reutilizar el mismo logic)
    public function storeDocumento(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'archivo' => 'required|file|mimes:pdf|max:51200', 
            'nombre_archivo' => 'required|string|max:255'
        ]);

        $archivo = $request->file('archivo');
        $path = $archivo->store('documentos_clientes', 'local');

        ClienteDocumento::create([
            'cliente_id'     => $request->cliente_id,
            'user_id'        => auth()->id(),
            'nombre_archivo' => $request->nombre_archivo,
            'ruta_archivo'   => $path,
            'peso_archivo'   => $archivo->getSize(),
            'mime_type'      => $archivo->getClientMimeType(),
        ]);

        return back()->with('success', 'Archivo cargado por el personal de ImporDiesel.');
    }

    // 3. Método para que el admin elimine
    public function destroyDocumento($id)
    {
        $documento = ClienteDocumento::findOrFail($id);

        // Eliminación física
        if (Storage::exists($documento->ruta_archivo)) {
            Storage::delete($documento->ruta_archivo);
        }

        $documento->delete();

        return back()->with('success', 'Archivo eliminado permanentemente.');
    }

    public function downloadDocumento($id)
    {
        // El administrador puede buscar el documento directamente por su ID
        $documento = ClienteDocumento::findOrFail($id);

        // Verificamos que el archivo físico exista en el storage local
        if (!Storage::exists($documento->ruta_archivo)) {
            return back()->with('error', 'El archivo físico no se encuentra en el servidor.');
        }

        // Descarga segura
        return Storage::download($documento->ruta_archivo, $documento->nombre_archivo . '.pdf');
    }
}