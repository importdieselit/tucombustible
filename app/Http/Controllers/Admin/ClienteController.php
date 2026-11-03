<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\PedidoRepository;
use App\Repositories\ClienteRepository;
use App\Services\ClienteService;
use App\Services\ClienteLubricanteService;
use App\Services\GascoCupoService;
use App\Models\Cliente;
use App\Models\TipoCombustible;
use App\Models\Pedido;
use App\Models\Estado;
use App\Models\Ciudad;
use App\Models\ClienteDocumento;
use App\Models\Viaje;
use App\Models\DespachoViaje;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Redirect, Session, Log, Auth, Storage, DB};

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
    public function show(Request $request, $id)
    {
        // Mantenemos tus servicios originales
        $cliente          = $this->clienteService->obtenerExpediente($id);
        $tiposCombustible = TipoCombustible::all();
        $infoGasco        = $this->gascoCupoService->obtenerSaldoActual($id);

        $qrExiste = false;
        $qrPath   = null;
        $extensiones = ['png', 'jpg', 'jpeg'];

        foreach ($extensiones as $ext) {
            $pathRelativo = "clientes/qr/{$cliente->id}.{$ext}";
            if (Storage::disk('public')->exists($pathRelativo)) {
                $qrExiste = true;
                $qrPath   = asset("storage/clientes/qr/{$cliente->id}.{$ext}");
                break;
            }
        }

        // Captura de fechas
        $despachoDesde = $request->query('despacho_desde');
        $despachoHasta = $request->query('despacho_hasta');

        $pedidos = Pedido::where('cliente_id', $id)
            ->orderBy('fecha_solicitud', 'desc')
            ->paginate(20, ['*'], 'pedidos_page');

        $despachos = DespachoViaje::where(function ($q) use ($id) {
                $q->where('cliente_id', $id)
                ->orWhereHas('viaje', function ($v) use ($id) {
                    $v->where('cliente_id', $id);
                });
            })
            ->whereHas('viaje', function ($q) {
                $q->whereIn('tipo_planificacion', [1, 2]);
            })
            ->when($despachoDesde, fn($q) => $q->whereDate('created_at', '>=', $despachoDesde))
            ->when($despachoHasta, fn($q) => $q->whereDate('created_at', '<=', $despachoHasta))
            ->with(['viaje.tipoCombustible', 'viaje.sede', 'pedido'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'despachos_page');

        // Sucursales paginadas (solo si es padre)
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
            'despachos',
            'infoGasco', 
            'sucursales',
            'documentos',
            'espacioUsadoMb',
            'despachoDesde',
            'despachoHasta',
            'qrExiste',
            'qrPath'
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
            'email'               => 'required|email:rfc|max:255',
            'es_aliado_comercial' => 'required|boolean',
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
            'es_aliado_comercial.required' => 'Debe indicar si el cliente es un aliado comercial.',
            'es_aliado_comercial.boolean'  => 'El valor seleccionado no es válido.',
            'contacto.regex'          => 'El nombre de contacto solo debe contener letras.',
            'contacto_alt.regex'      => 'El nombre de contacto alternativo solo debe contener letras.',
            'telefono.digits_between' => 'El teléfono debe tener entre 10 y 11 dígitos.',
            'telefono_alt.digits_between' => 'El teléfono alternativo debe tener entre 10 y 11 dígitos.',
            'token_padre.exists'      => 'El Token de la empresa principal no es válido.',
            'email.email'             => 'El correo electrónico debe ser una dirección válida con @.',
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
            'rif'                 => 'required|string|max:15',
            'email'               => 'required|email:rfc|max:255',
            'es_aliado_comercial' => 'required|boolean',
            'contacto'            => 'required|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono'            => 'nullable|digits_between:10,11',
            'contacto_alt'        => 'nullable|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono_alt'        => 'nullable|digits_between:10,11',
            'estado_id'           => 'nullable|exists:estados,id',
            'ciudad_id'           => 'nullable|exists:ciudades,id',
            'direccion'           => 'nullable|string',
            'direccion_operativa' => 'nullable|string',
            'tipo_cliente'        => 'nullable|in:padre,sucursal',
            'token_padre'         => 'required_if:tipo_cliente,sucursal|nullable|string|exists:clientes,token_registro',
        ], [
            'es_aliado_comercial.required' => 'Debe indicar si el cliente es un aliado comercial.',
            'contacto.regex'              => 'El campo Persona de Contacto solo debe contener letras.',
            'contacto_alt.regex'          => 'El campo Contacto Alternativo solo debe contener letras.',
            'email.email'                 => 'El correo electrónico debe ser una dirección válida con @.',
            'telefono.digits_between'     => 'El teléfono debe tener entre 10 y 11 dígitos.',
            'telefono_alt.digits_between' => 'El teléfono alternativo debe tener entre 10 y 11 dígitos.',
            'token_padre.required_if'     => 'Debe ingresar el Token de la empresa principal si selecciona opción Sucursal.',
            'token_padre.exists'          => 'El Token de la empresa principal no es válido.',
        ]);

        try {
            $this->clienteService->obtenerExpediente($id);

            $dataToUpdate = $request->only([
                'nombre', 'rif', 'email', 'es_aliado_comercial', 'contacto', 'telefono',
                'contacto_alt', 'telefono_alt',
                'estado_id', 'ciudad_id', 'direccion', 'direccion_operativa',
            ]);

            // Lógica para actualizar Tipo de Cliente (Padre / Sucursal)
            if ($request->filled('tipo_cliente')) {
                if ($request->tipo_cliente === 'padre') {
                    $dataToUpdate['parent'] = 0;
                } elseif ($request->tipo_cliente === 'sucursal') {
                    $padre = Cliente::where('token_registro', $request->token_padre)->first();

                    if ($padre) {
                        if ($padre->id == $id) {
                            return Redirect::back()->withInput()->with('error', 'Un cliente no puede vincularse como sucursal de sí mismo.');
                        }
                        $dataToUpdate['parent'] = $padre->id;
                    }
                }
            }

            app(ClienteRepository::class)->update($id, $dataToUpdate);

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
            'rif'          => 'required|string|max:20',
            'email'        => 'required|email|max:255',
            'telefono'     => 'nullable|digits_between:10,11',
        ], [
            'razon_social.required'   => 'La razón social es obligatoria.',
            'rif.required'            => 'El RIF es obligatorio.',
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

    public function uploadQr(Request $request, $id)
    {
        $request->validate([
            'qr' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'qr.required' => 'Debe seleccionar un archivo de imagen.',
            'qr.image'    => 'El archivo seleccionado debe ser una imagen válida.',
            'qr.mimes'    => 'Formato no permitido. Solo se aceptan archivos .PNG o .JPG.',
            'qr.max'      => 'El archivo no debe pesar más de 2 MB.'
        ]);

        $cliente = Cliente::findOrFail($id);
        $archivo = $request->file('qr');
        $extension = strtolower($archivo->getClientOriginalExtension());

        // Limpiar cualquier QR viejo previo (por si cambió de extensión .png a .jpg o viceversa)
        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            $viejoPath = "clientes/qr/{$cliente->id}.{$ext}";
            if (Storage::disk('public')->exists($viejoPath)) {
                Storage::disk('public')->delete($viejoPath);
            }
        }

        // Guardar la nueva imagen con el ID del cliente
        $archivo->storeAs('clientes/qr', "{$cliente->id}.{$extension}", 'public');

        return redirect()->back()->with('success', 'Código QR actualizado correctamente.');
    }

    public function destroyQr($id)
    {
        $cliente = Cliente::findOrFail($id);

        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            $path = "clientes/qr/{$cliente->id}.{$ext}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'Código QR eliminado correctamente.');
    }

    public function import()
    {
        return view('cliente.import');
    }

    public function storeImport(Request $request)
    {
        Log::info('Iniciando proceso de importación de clientes desde Excel.');
        echo "Iniciando proceso de importación..."; // Línea de depuración
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Máx 10MB
        ]);

        $file = $request->file('archivo_excel');
        echo "Archivo recibido: " . $file->getClientOriginalName(); // Línea de depuración

        try {
            echo "Intentando cargar el archivo Excel..."; // Línea de depuración
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getSheetByName('Directorio') ?? $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            echo "Archivo Excel cargado con éxito. Filas obtenidas: " . count($rows); // Línea de depuración
        } catch (\Exception $e) {
            echo "Error al leer el archivo Excel: " . $e->getMessage(); // Línea de depuración
            Log::error('Error al leer el archivo Excel: ' . $e->getMessage());
            return back()->withErrors(['archivo_excel' => 'No se pudo leer el archivo Excel: ' . $e->getMessage()]);
        }

        $importados = 0;
        $actualizados = 0;
        $errores = [];
        echo "Iniciando procesamiento de filas..."; // Línea de depuración
        // Encontrar la fila de cabecera donde está 'Cód.'
        $headerRowIndex = null;
        foreach ($rows as $index => $row) {
            echo "Procesando fila {$index}..."; // Línea de depuración
            // Buscamos la fila que contiene la celda 'Cód.'
            $valuesCell = array_values($row);
            if (in_array('Cód.', $valuesCell) || in_array('Código', $valuesCell)) {
                $headerRowIndex = $index;
                break;
            }
        }
        echo "Fila de cabecera encontrada en el índice: " . $headerRowIndex; // Línea de depuración

        if (!$headerRowIndex) {
            echo "No se encontró la cabecera válida ('Cód.' o 'Código') en la hoja de Excel."; // Línea de depuración
            Log::error('No se encontró la cabecera válida ("Cód." o "Código") en la hoja de Excel.');
            return back()->withErrors(['archivo_excel' => 'No se encontró la cabecera válida ("Cód." o "Código") en la hoja de Excel. Verifique que la estructura sea correcta.']);
        }

        echo "Cabecera válida encontrada. Procesando datos..."; // Línea de depuración
        // Extraer cabeceras y mapear índices por nombre de columna
        $headers = $rows[$headerRowIndex];
        $headerMap = array_flip(array_filter($headers));
        echo "Cabeceras mapeadas: " . json_encode($headerMap); // Línea de depuración
        // Procesar filas de datos a partir de la fila siguiente a la cabecera
        for ($i = $headerRowIndex + 1; $i <= count($rows); $i++) {
            echo "Procesando fila de datos {$i}..."; // Línea de depuración
            $row = $rows[$i] ?? null;
            if (!$row) continue;
            echo "Fila {$i} obtenida: " . json_encode($row); // Línea de depuración
            // Mapear datos usando las columnas del Excel
            $getCod = trim($row[$this->getColKey($headerMap, ['Cód.', 'Código'])] ?? '');
            $nombreAgencia = trim($row[$this->getColKey($headerMap, ['Nombre'])] ?? '');

            // Si la fila está vacía en los campos clave, la saltamos
            if (empty($getCod) && empty($nombreAgencia)) {
                echo "Fila {$i} está vacía en campos clave, se omite."; // Línea de depuración
                continue;
            }

            // Validación anti-errores por fila
            if (empty($getCod)) {
                echo "Fila {$i}: El código de agencia es obligatorio."; // Línea de depuración
                $errores[] = "Fila {$i}: El código de agencia es obligatorio.";
                continue;
            }
            echo "Fila {$i}: Código de agencia: {$getCod}"; // Línea de depuración
            if (empty($nombreAgencia)) {
                echo "Fila {$i} (Cód: {$getCod}): El nombre de la agencia es obligatorio."; // Línea de depuración
                $errores[] = "Fila {$i} (Cód: {$getCod}): El nombre de la agencia es obligatorio.";
                continue;
            }

            try {
                echo "Fila {$i} (Cód: {$getCod}): Iniciando transacción para crear/actualizar cliente."; // Línea de depuración
                DB::beginTransaction();

                // 1. Resolver Estado y Ciudad de forma dinámica
                $nombreEstado = trim($row[$this->getColKey($headerMap, ['Estado'])] ?? 'Capital');
                $nombreMunicipio = trim($row[$this->getColKey($headerMap, ['Municipio'])] ?? 'Caracas');

                $estado = Estado::firstOrCreate(
                    ['nombre' => $nombreEstado ?: 'Distrito Capital']
                );

                $ciudad = Ciudad::firstOrCreate(
                    ['nombre' => $nombreMunicipio ?: 'Caracas', 'estado_id' => $estado->id]
                );

                // 2. Extraer datos de contacto del Gerente de Negocios
                $contactoPersona = trim($row[$this->getColKey($headerMap, ['contacto'])] ?? '');
                $dniPersona = trim($row[$this->getColKey($headerMap, ['dni'])] ?? '');
                $telefonoPersona = trim($row[$this->getColKey($headerMap, ['telefono'])] ?? '');
                $emailPersona = trim($row[$this->getColKey($headerMap, ['email'])] ?? '');
                $direccion = trim($row[$this->getColKey($headerMap, ['direccion'])] ?? '');

                $contactoAlt = trim($row[$this->getColKey($headerMap, ['contacto_alt'])] ?? '');
                $telefonoAlt = trim($row[$this->getColKey($headerMap, ['telefono_alt'])] ?? '');
                
                // Capacidad / Cupo
                $capacidad = floatval($row[$this->getColKey($headerMap, ['Capacidad'])] ?? 0);
                $statusTexto = trim($row[$this->getColKey($headerMap, ['Status '])] ?? '');

                // Mapear Status textual a constantes del modelo Cliente
                $statusVal = Cliente::STATUS_APROBADO; // Por defecto aprobado si viene operativa
                if (stripos($statusTexto, 'Inactivo') !== false) {
                    $statusVal = Cliente::STATUS_INACTIVO;
                } elseif (stripos($statusTexto, 'Registro') !== false) {
                    $statusVal = Cliente::STATUS_EN_REGISTRO;
                }

                // 3. Crear o actualizar el registro del cliente usando el Cód. como parte del alias o identificador único
                $aliasUnico = "BDV-".$getCod."-" . $nombreAgencia;

                $clienteData = [
                    'nombre'              => 'BDV-' . $nombreAgencia,
                    'alias'               => $aliasUnico,
                    'rif'                 => 'G-200099976', // RIF provisional estructurado si no viene explícito
                    'contacto'            => $contactoPersona ?: 'Sin Asignar',
                    'telefono'            => $telefonoPersona ?: '04140000000',
                    'direccion_operativa' => $direccion ?: 'Dirección no especificada',
                    'dni'                 => $dniPersona ?: null,
                    'estado_id'           => $estado->id,
                    'ciudad_id'           => $ciudad->id,
                    'email'               => $emailPersona ,
                    'direccion'           => $direccion ?: 'Dirección no especificada',
                    'cupo'                => $capacidad,
                    'status'              => $statusVal,
                    'es_aliado_comercial' => false,
                    'cliente_rapido'      => false,
                    'parent'              => 394, // Asumimos que todos son clientes padres
                    'registro_paso'       => Cliente::TOTAL_PASOS,
                    'fecha_aprobacion'    => Carbon::now(),
                    'contacto_alt'        => $contactoAlt ?: null,
                    'telefono_alt'        => $telefonoAlt ?: null,
                    'token_registro'      => 'RPQI19UURU'
                ];

                $cliente = Cliente::updateOrCreate(
                    ['alias' => $aliasUnico],
                    $clienteData
                );

                if ($cliente->wasRecentlyCreated) {
                    $importados++;
                } else {
                    $actualizados++;
                }

                DB::commit();
                echo "Fila {$i} (Cód: {$getCod}): Cliente procesado correctamente. ID: {$cliente->id}"; // Línea de depuración

            } catch (\Exception $ex) {
                echo "Fila {$i} (Cód: {$getCod}): Error al procesar - " . $ex->getMessage(); // Línea de depuración
                Log::error("Error al procesar fila {$i} (Cód: {$getCod}): " . $ex->getMessage());
                DB::rollBack();
                $errores[] = "Fila {$i} (Cód: {$getCod}): Error al procesar - " . $ex->getMessage();
            }
        }
        echo "Proceso de importación finalizado. Importados: {$importados}, Actualizados: {$actualizados}, Errores: " . count($errores); // Línea de depuración
        Log::info("Proceso de importación finalizado. Importados: {$importados}, Actualizados: {$actualizados}, Errores: " . count($errores));
       // return redirect()->route('cliente.import')->with('success', "Proceso completado con éxito. Nuevos importados: {$importados}, Actualizados: {$actualizados}.")->with('errores_importacion', $errores);
    }

    /**
     * Helper para buscar la clave de la columna en el array de cabeceras de manera flexible.
     */
    private function getColKey(array $headerMap, array $possibleNames)
    {
        foreach ($possibleNames as $name) {
            foreach ($headerMap as $headerText => $colIndex) {
                if (stripos(trim($headerText), trim($name)) !== false) {
                    return $colIndex;
                }
            }
        }
        return null;
    }
}