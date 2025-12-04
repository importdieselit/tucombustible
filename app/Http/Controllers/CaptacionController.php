<?php
namespace App\Http\Controllers;

use App\Models\CaptacionCliente;
use App\Models\CaptacionDocumento;
use App\Models\EquipoCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlanillasGeneradas;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\RequisitoCaptacion;

class CaptacionController extends Controller
{
    // Formulario publico para registro inicial
    public function create()
    {
        return view('captacion.create');
    }

    // Guardar registro inicial + archivos
    public function store(Request $request)
    {
        $validated = $request->validate([
            'razon_social' => 'required|string',
            'rif' => 'nullable|string',
            'correo' => 'required|email',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            //'documentos.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);
        
        Log::info('Guardando nueva captación para: '.$validated['razon_social']);
        
        try {
            $captacion = CaptacionCliente::create([
            'razon_social' => $validated['razon_social'],
            'rif' => $validated['rif'] ?? null,
            'correo' => $validated['correo'],
            'telefono' => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'estatus_captacion' => 'registro_inicial'
        ]);

        if ($request->hasFile('documentos')) {
            foreach ($request->file('documentos') as $file) {
                $path = $file->store("clientes/{$captacion->id}/documentos", 'public');
                CaptacionDocumento::create([
                    'captacion_id' => $captacion->id,
                    'tipo_anexo' => $request->input('tipo_anexo') ?? null,
                    'nombre_documento' => $file->getClientOriginalName(),
                    'ruta' => $path
                ]);
            }
        }
        } catch (\Throwable $th) {
            Log::error('Error al guardar captación: '.$th->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error al procesar la solicitud. Intente nuevamente.');
        }

        

        return redirect()->route('captacion.thanks')->with('success', 'Solicitud recibida. Pronto le contactaremos.');
    }

    public function thanks() {
        $query = CaptacionCliente::query();
 $list = $query->orderBy('created_at','desc')->paginate(25);
        return view('captacion.index', compact('list'));
    }

    // ---------------------------------------------------------------------
    // Panel administrativo - listado de solicitudes
    public function index(Request $req)
    {
         $estadisticas = [
            'registro_inicial' => CaptacionCliente::where('estatus_captacion','registro_inicial')->count(),
            'solicitud' => CaptacionCliente::where('gestion','CUPO')->count(),
            'migracion' => CaptacionCliente::where('gestion','MIGRACION')->count(),
            'espera' => CaptacionCliente::where('estatus_captacion','espera')->count(),
            'planillas_enviadas' => CaptacionCliente::where('estatus_captacion','planillas_enviadas')->count(),
            'falta_documentacion' => CaptacionCliente::where('estatus_captacion','falta_documentacion')->count(),
            'esperando_inspeccion' => CaptacionCliente::where('estatus_captacion','esperando_inspeccion')->count(),
            'aprobado' => CaptacionCliente::where('estatus_captacion','aprobado')->count(),
        ];

        $query = CaptacionCliente::query();

        if ($req->search) {
            $query->where('razon_social','like',"%{$req->search}%")
                  ->orWhere('rif','like',"%{$req->search}%");
        }

        if ($req->estatus_captacion) {
            $query->where('estatus_captacion',$req->estatus_captacion);
        }

        $clientes = $query->orderBy('id','DESC')->paginate(20);

        return view('captacion.index', compact('clientes','estadisticas'));
    }

    // Mostrar expediente (admin)
    public function show(CaptacionCliente $cliente)
    {
        dd($cliente);
        $cliente->load('documentos','equipos','requisitosPendientes','requisitosCompletos');
        $requisitos = RequisitoCaptacion::all();
        return view('captacion.show', compact('cliente','requisitos'));
    }

    // Validar documento individual (marca como validado o no)
    public function validarDocumento(Request $request, CaptacionDocumento $documento)
    {
        $this->authorize('validate', $documento); // opcional policy
        $documento->validado = $request->input('validado') ? true : false;
        $documento->validado_por = Auth::id();
        $documento->save();

        

        // Recalcular estatus general simple:
        $captacion = $documento->captacion;
        $captacion = $documento->captacion;

        // VALIDACIÓN AUTOMÁTICA
        if ($captacion->requisitosCompletos()) {
            $captacion->estatus_captacion = 'por_verificar';
        } else {
            $captacion->estatus_captacion = 'documento_incompleto';
        }

        $captacion->save();

        return response()->json(['ok'=>true]);
    }

    // Enviar planillas (genera o adjunta plantillas)
    public function enviarPlanillas(CaptacionCliente $captacion)
    {
        // Generación / copia de planillas (ejemplo: copia desde storage/plantillas)
        $templates = [
            'declaracion_bajo_fe_de_juramento' => storage_path('app/planillas/declaracion_bajo_fe_de_juramento.pdf'),
            'solicitud' => storage_path('app/planillas/solicitud.pdf'),
            'requisitos' => storage_path('app/planillas/requisitos.doc'),
        ];

        $saved = [];
        
        
        foreach ($templates as $key => $file) {

            if (file_exists($file)) {

                // Obtener extensión real del archivo original
                $extension = pathinfo($file, PATHINFO_EXTENSION);

                // Construimos ruta con su extensión real
                $path = "planillas/{$key}.{$extension}";

                // Guardamos el archivo en storage/app/public
                Storage::disk('public')->put($path, file_get_contents($file));

                $saved[] = $path;
            }
        }

        //$captacion->planillas_generadas = json_encode($saved);
        $captacion->estatus_captacion = 'planillas_enviadas';
        $captacion->save();
        Log::debug("Saved files:", $saved);

        // Enviar correo con adjuntos si correo existe
        if ($captacion->correo) {
            Mail::to($captacion->correo)->send(new PlanillasGeneradas($captacion, $saved));
        }

        return redirect()->back()->with('success','Planillas enviadas/habilitadas.');
    }

    // Programar / marcar pendiente inspección
    public function programarInspeccion(CaptacionCliente $captacion)
    {
        if (!$captacion->requisitosCompletos()) {
            return back()->with('error', 'Aún faltan documentos obligatorios. No puede avanzar a inspección.');
        }

        $captacion->estatus_captacion = 'pendiente_inspeccion';
        $captacion->save();
        // Podrías disparar notificación al inspector
        return redirect()->back()->with('success','Inspección programada.');
    }

    // Registrar resultado de inspección (aprobado true/false)
    public function registrarInspeccion(Request $request, CaptacionCliente $captacion)
    {
        $request->validate([
            'aprobado' => 'required|boolean',
            'observaciones' => 'nullable|string',
            'fotos.*' => 'nullable|image|max:5120',
        ]);

        $captacion->estatus_captacion = $request->aprobado ? 'aprobado' : 'rechazado_inspeccion';
        $captacion->observaciones = $request->observaciones;
        $captacion->save();

        // guardar fotos si vienen
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $f) {
                $p = $f->store("clientes/{$captacion->id}/inspecciones", 'public');
                CaptacionDocumento::create([
                    'captacion_id' => $captacion->id,
                    'tipo_anexo' => 'INSPECCION',
                    'nombre_documento' => basename($p),
                    'ruta' => $p,
                    'validado' => true
                ]);
            }
        }

        // Si aprobado -> crear cliente real
        if ($request->aprobado) {
            $cliente = Cliente::create([
                'nombre' => $captacion->razon_social,
                'rif' => $captacion->rif,
                'correo' => $captacion->correo,
                'telefono' => $captacion->telefono,
                'direccion' => $captacion->direccion,
                // otros campos por defecto...
            ]);
            $captacion->cliente_id = $cliente->id;
            $captacion->save();
        }

        return redirect()->route('captacion.show', $captacion->id)
            ->with('success','Inspección registrada.');
    }

    // Descargar documento
    public function downloadDoc(CaptacionDocumento $documento)
    {
        if (!Storage::disk('public')->exists($documento->ruta)) abort(404);
        return response()->download(storage_path('app/public/'.$documento->ruta));
    }


    public function edit(CaptacionCliente $cliente)
    {
        return view('captacion.edit', compact('cliente'));
    }


    public function update(Request $request, CaptacionCliente $cliente)
    {
        $cliente->update($request->all());

        return back()->with('success','Datos actualizados.');
    }





    public function validarDocumentos(Request $request, CaptacionCliente $cliente)
    {
        $data = $cliente->documentos_subidos ?? [];

        foreach ($request->file('documentos') ?? [] as $key => $file) {
            $path = $file->store("clientes/{$cliente->id}/documentos",'public');
            $data[$key] = $path;
        }

        $cliente->documentos_subidos = $data;

        $cliente->estatus_captacion =
            count($cliente->faltantes()) > 0 ? 'falta_documentacion' : 'esperando_inspeccion';

        $cliente->save();

        return back()->with('success','Documentos validados correctamente');
    }

    public function uploadDocument(Request $request, $id)
{
    $request->validate([
        'documento' => 'required|string',
        'archivo'   => 'required|file|max:10240',
    ]);

    $captacion = CaptacionCliente::findOrFail($id);

    $docName = $request->documento;
    $path = $request->file('archivo')->store("captacion/{$captacion->id}/docs", 'public');

    $docs = $captacion->documentos_subidos ?? [];
    $docs[$docName] = $path;

    $captacion->documentos_subidos = $docs;
    $captacion->save();

    return response()->json([
        'ok'   => true,
        'ruta' => asset('storage/' . $path)
    ]);
}



    public function aprobar(CaptacionCliente $cliente)
    {
        $cliente->estatus_captacion = 'aprobado';
        $cliente->save();

        return back()->with('success','Cliente aprobado correctamente.');
    }
}