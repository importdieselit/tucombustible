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
        return view('captacion.admin.index', compact('list'));
    }

    // ---------------------------------------------------------------------
    // Panel administrativo - listado de solicitudes
    public function adminIndex(Request $req)
    {
        $query = CaptacionCliente::query();

        if ($req->filled('estatus')) {
            $query->where('estatus_captacion', $req->estatus);
        }

        $list = $query->orderBy('created_at','desc')->paginate(25);
        return view('captacion.admin.index', compact('list'));
    }

    // Mostrar expediente (admin)
    public function show(CaptacionCliente $captacion)
    {
        $captacion->load('documentos','equipos');
        return view('captacion.admin.show', compact('captacion'));
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

        return redirect()->route('captacion.admin.show', $captacion->id)
            ->with('success','Inspección registrada.');
    }

    // Descargar documento
    public function downloadDoc(CaptacionDocumento $documento)
    {
        if (!Storage::disk('public')->exists($documento->ruta)) abort(404);
        return response()->download(storage_path('app/public/'.$documento->ruta));
    }
}
