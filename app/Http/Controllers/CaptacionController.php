<?php

namespace App\Http\Controllers;

use App\Mail\EstatusSolicitud;
use App\Mail\ProspectoBienvenida;
use App\Mail\PasswordCambiado;
use App\Models\CaptacionCliente;
use App\Models\CaptacionDocumento;
use App\Models\RequisitoCaptacion;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlanillasGeneradas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use DB;

class CaptacionController extends Controller
{
    /**
     * Muestra el formulario de registro público para prospectos.
     */
    public function create()
    {
        return view('captacion.create');
    }

    /**
     * Guarda el registro inicial del prospecto y crea su usuario.
     */
    public function store(Request $request)
    {
        $rifLimpio = strtoupper($request->rif_tipo . $request->rif_numero);
    
        // Agregamos el RIF limpio al request para que Laravel lo pueda validar con 'unique'
        $request->merge(['rif_completo' => $rifLimpio]);

        $request->validate([
            'razon_social'    => 'required|string',
            'rif_tipo'        => 'required|string|max:1',
            'rif_numero'      => 'required|string|max:15',
            'rif_completo'    => 'unique:captacion_clientes,rif', // Valida que el RIF no exista
            'correo'          => 'required|email|unique:users,email|unique:captacion_clientes,correo',
            'telefono'        => 'required|string',
            'tipo_cliente'    => 'required|in:padre,sucursal',
            'tipo_solicitud'  => 'required|in:nuevo,migracion',
            'estado'          => 'required|string',
            'ciudad'          => 'required|string',
            'cantidad_litros' => 'required|numeric|min:1',
            'tipo_servicio'   => 'required|in:Maritimo,Industrial',
        ], [
            'rif_completo.unique' => 'Ya existe un cliente con este RIF',
            'correo.unique'       => 'Ya existe un cliente con este Correo Electronico',
        ]);

        $telefonoLimpio = preg_replace('/[^0-9]/', '', $request->telefono);

    Log::info('Iniciando registro de prospecto', $request->all());

    try {
        DB::beginTransaction();

        // 1. Crear el registro de captación
        $captacion = CaptacionCliente::create([
            'razon_social'      => $request->razon_social,
            'rif'               => $rifLimpio,
            'correo'            => $request->correo,
            'telefono'          => $telefonoLimpio,
            'tipo_cliente'      => $request->tipo_cliente,
            'tipo_solicitud'    => $request->tipo_solicitud,
            'estado'            => $request->estado,
            'ciudad'            => $request->ciudad,
            'cantidad_litros'   => $request->cantidad_litros,
            'tipo_servicio'     => $request->tipo_servicio,
            'estatus_captacion' => 'prospecto'
        ]);

        // 2. Crear el Usuario vinculado como prospecto
        User::create([
            'name'                 => $request->razon_social,
            'email'                => $request->correo,
            'password'             => bcrypt($request->rif_numero),
            'id_perfil'            => 3, 
            'id_persona'           => 0, 
            'status'               => 1, 
            'status_usuario'       => 'prospecto',
            'must_change_password' => 1 
        ]);

        DB::commit();

        // Enviar correo de bienvenida (con mail en log por ahora)
        //Mail::to($captacion->correo)->send(new ProspectoBienvenida($captacion));

        return redirect()->route('login')->with('success', 'Registro exitoso. Inicie sesión con su correo y use su número de RIF como contraseña.');

    } catch (\Exception $e) {
        DB::rollback();
        Log::error("Error en Registro de Captación: " . $e->getMessage());
        return back()->withInput()->with('error', 'Error en el registro: ' . $e->getMessage());
    }
    }

    /**
     * Listado administrativo de prospectos y clientes en captación.
     */
    public function index(Request $req)
    {
        $estadisticas = [
            'registro_inicial'            => CaptacionCliente::where('estatus_captacion', 'prospecto')->count(),
            'revision_pendiente'   => CaptacionCliente::where('estatus_captacion', 'revision_pendiente')->count(),
            'planillas_enviadas'   => CaptacionCliente::where('estatus_captacion', 'planillas_enviadas')->count(),
            'esperando_inspeccion' => CaptacionCliente::where('estatus_captacion', 'esperando_inspeccion')->count(),
            'aprobado'             => CaptacionCliente::where('estatus_captacion', 'aprobado')->count(),
            'nuevo'                => CaptacionCliente::where('tipo_solicitud', 'nuevo')->count(),
            'migracion'            => CaptacionCliente::where('tipo_solicitud', 'migracion')->count(),
        ];

        $query = CaptacionCliente::with('documentos');

        // Si no se está filtrando específicamente por "aprobado", los ocultamos
        if ($req->estatus_captacion) {
            $query->where('estatus_captacion', $req->estatus_captacion);
        } else {
            // Por defecto, ocultar los que ya son clientes reales
            $query->where('estatus_captacion', '!=', 'aprobado');
        }

        // --- FILTRO POR FECHA (Corregido) ---
        if ($req->fecha) {
            $query->whereDate('created_at', $req->fecha);
        }

        // Filtro por nombre o RIF
        if ($req->search) {
            $query->where(function($q) use ($req) {
                $q->where('razon_social', 'like', "%{$req->search}%")
                ->orWhere('rif', 'like', "%{$req->search}%");
            });
        }

        $clientes = $query->orderBy('id', 'DESC')->paginate(20);

        return view('captacion.index', compact('clientes', 'estadisticas'));
    }

    /**
     * Muestra el detalle de un prospecto específico para el administrador.
     */
    public function show(CaptacionCliente $cliente)
    {        
        $cliente->load('documentos');
        $esPadre = ($cliente->tipo_cliente === 'padre');

        $requisitos = RequisitoCaptacion::where(function ($query) use ($esPadre) {
            $query->where('tipo_cliente', 'ambos');
            if ($esPadre) {
                $query->orWhere('tipo_cliente', 'padre');
            }
        })->get();

        return view('captacion.show', compact('cliente', 'requisitos'));
    }

    /**
     * Vista de carga de documentos para el usuario prospecto.
     */
    public function completarExpediente()
    {
        $user = Auth::user();
        $captacion = CaptacionCliente::where('correo', $user->email)->first();

        if (!$captacion) {
            return redirect()->route('login')->with('error', 'No se encontró registro de captación asociado.');
        }

        $requisitos = RequisitoCaptacion::where(function($query) use ($captacion) {
            $query->where('tipo_cliente', $captacion->tipo_cliente)
                  ->orWhere('tipo_cliente', 'ambos');
        })->get();

        $documentosSubidos = $captacion->documentos->pluck('requisito_id')->toArray();

        return view('captacion.completar_expediente', compact('captacion', 'requisitos', 'documentosSubidos'));
    }

    /**
     * Procesa la subida de documentos del prospecto.
     */
    public function uploadDocument(Request $request, $id)
    {
        // 1. Validamos y capturamos errores si los hay
        $request->validate([
            'archivo'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'requisito_id' => 'required'
        ]);

        try {
            $captacion = CaptacionCliente::findOrFail($id);
        
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            
                // 2. Intentamos guardar el archivo físicamente
                $path = $archivo->storeAs("clientes/{$id}/documentos", $nombreArchivo, 'public');

                // 3. Guardamos o actualizamos en la base de datos
                CaptacionDocumento::updateOrCreate(
                    [
                        'captacion_id' => $id, 
                        'requisito_id' => $request->requisito_id 
                    ],
                    [
                        'nombre_documento' => $nombreArchivo,
                        'ruta'             => $path,
                        'estatus_archivo'  => 'cargado'
                    ]
                );

                return back()->with('success', 'Documento cargado con éxito.');
            }

            return back()->with('error', 'No se detectó ningún archivo.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error crítico: ' . $e->getMessage());
        }
    }

    /**
     * Envío de planillas según el tipo de gestión del cliente.
     */
    public function enviarPlanillas(CaptacionCliente $cliente)
    {
        $planillasPorCaso = [
            'nuevo'     => ['solicitud_nuevo_cupo.pdf', 'requisitos_generales.pdf'],
            'migracion' => ['carta_migracion_mppph.pdf', 'declaracion_jurada.pdf']
        ];

        $archivosAEnviar = $planillasPorCaso[$cliente->tipo_solicitud] ?? ['solicitud_general.pdf'];
        $saved = [];

        foreach ($archivosAEnviar as $nombreArchivo) {
            $fullPath = storage_path("app/planillas/{$nombreArchivo}");
            if (file_exists($fullPath)) {
                $publicPath = "planillas_enviadas/{$cliente->id}/{$nombreArchivo}";
                Storage::disk('public')->put($publicPath, file_get_contents($fullPath));
                $saved[] = $publicPath;
            }
        }

        $cliente->update(['estatus_captacion' => 'planillas_enviadas']);

        /*if ($cliente->correo) {
            Mail::to($cliente->correo)->send(new PlanillasGeneradas($cliente, $saved));
        }*/

        return redirect()->back()->with('success', "Planillas enviadas correctamente.");
    }

    /**
     * Registra el resultado de la inspección técnica.
     */
    public function registrarInspeccion(Request $request, CaptacionCliente $captacion)
    {
        $request->validate(['aprobado' => 'required|boolean']);

        $captacion->estatus_captacion = $request->aprobado ? 'esperando_aprobacion_final' : 'rechazado_inspeccion';
        $captacion->observaciones = $request->observaciones;
        $captacion->save();

        return redirect()->back()->with('success','Resultado de inspección guardado.');
    }

    /**
     * Aprobación final: Convierte al prospecto en Cliente real.
     */
    public function aprobar(CaptacionCliente $cliente)
    {
        try {
            DB::beginTransaction();

            $cliente->update(['estatus_captacion' => 'aprobado']);

            $clienteReal = Cliente::create([
                'nombre'     => $cliente->razon_social,
                'rif'        => $cliente->rif,
                'correo'     => $cliente->correo,
                'telefono'   => $cliente->telefono,
                'direccion'  => $cliente->estado . ", " . $cliente->ciudad,
                'cupo'       => $cliente->cantidad_litros, 
                'disponible' => $cliente->cantidad_litros,
                'parent'     => ($cliente->tipo_cliente == 'sucursal') ? 1 : 0,
                'status'     => 1
            ]);

            $usuario = User::where('email', $cliente->correo)->first();
            if ($usuario) {
                $usuario->update([
                    'cliente_id'     => $clienteReal->id,
                    'status_usuario' => 'aprobado'
                ]);
            }

            DB::commit();
            return redirect()->route('captacion.index')->with('success', 'Cliente aprobado.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error en aprobación: " . $e->getMessage());
            return back()->with('error', 'Error al procesar activación.');
        }
    }

    /**
     * Validación individual de documentos por el administrador.
     */
    public function validarDocumento(Request $request, CaptacionDocumento $documento)
    {
        $documento->validado = true;
        $documento->validado_por = Auth::id();
        $documento->save();

        // Redireccionamos atrás con mensaje en lugar de devolver JSON
        return back()->with('success', 'Documento validado correctamente.');
    }

    public function finalizarCarga($id)
    {
        $captacion = CaptacionCliente::findOrFail($id);
    
        // Cambiamos el estatus para que el administrador sepa que ya puede revisar
        $captacion->update([
            'estatus_captacion' => 'revision_pendiente'
        ]);

        return redirect()->route('captacion.thanks')->with('success', 'Tu expediente ha sido enviado a revisión.');
    }

    public function thanks()
    {
        return view('captacion.thanks');
    }

    public function rechazar($id)
    {
        DB::beginTransaction();
        try {
            // Aseguramos que la variable sea la que el Mailable espera
            $prospecto = CaptacionCliente::findOrFail($id); 
            $user = User::where('email', $prospecto->correo)->first();

            // Enviar correo (Ahora la variable $prospecto sí existe)
            //Mail::to($prospecto->correo)->send(new EstatusSolicitud($prospecto, 'rechazado'));

            // Borrado lógico/físico según el tipo de solicitud
            if ($prospecto->tipo_solicitud == 'nuevo') {
                foreach ($prospecto->documentos as $doc) {
                    if (Storage::disk('public')->exists($doc->ruta)) {
                        Storage::disk('public')->delete($doc->ruta);
                    }
                    $doc->delete();
                }
                if ($user) { $user->delete(); }
                $prospecto->delete();
            } else {
                $prospecto->update(['estatus_captacion' => 'rechazado']);
            }

            DB::commit();
            return redirect()->route('captacion.index')->with('success', 'Solicitud rechazada con éxito.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

}