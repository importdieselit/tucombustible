<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\{ClienteService, DashboardService};
use App\Models\{Estado, Ciudad};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, File, Log};
use ZipArchive;

class ClienteController extends Controller
{
    protected $clienteService;
    protected $dashboardService;

    public function __construct(ClienteService $clienteService, DashboardService $dashboardService)
    {
        $this->clienteService = $clienteService;
        $this->dashboardService = $dashboardService;
    }

    public function showRegistrationForm()
    {
        $estados = Estado::orderBy('nombre', 'asc')->get();
        return view('auth.register_cliente', compact('estados'));
    }

    public function store(Request $request)
    {
        $rifCompleto = strtoupper($request->rif_tipo . '-' . $request->rif_numero);
        $request->merge(['rif' => $rifCompleto]);

        $request->validate([
            'rif'                 => 'required|max:15|unique:clientes,rif', 
            'razon_social'        => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email',
            'contacto'            => 'required|string|max:255',
            'telefono'            => 'required|numeric|digits:11',
            'estado_id'           => 'required|exists:estados,id',
            'ciudad_id'           => 'required|exists:ciudades,id',
            'direccion_operativa' => 'required|string',
            'litros_diesel'       => 'nullable|numeric|min:0',
            'litros_mgo'          => 'nullable|numeric|min:0',
        ]);

        if ((!$request->litros_diesel || $request->litros_diesel <= 0) && 
            (!$request->litros_mgo || $request->litros_mgo <= 0)) {
            return back()->withInput()->with('error', 'Debe solicitar al menos un combustible.');
        }

        try {
            $datos = $request->all();
            $datos['nombre'] = $request->razon_social;
            $this->clienteService->registrarCliente($datos);
            return redirect()->route('login')->with('success', 'Registro exitoso. Ingrese con su RIF sin guiones.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $user = Auth::user();
        $cliente = $user->cliente; 

        if (!$cliente) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'No tiene un expediente asociado.');
        }

        if ($cliente->registro_paso < 10) {
            $cliente->load('documentos');
            return view('cliente.en_proceso', compact('cliente'));
        }

        $data = $this->dashboardService->getDashboardData($user);
        return view('cliente.index', $data);
    }

    /**
     * Genera y descarga un archivo ZIP con las planillas base.
     * MEJORA: Escanea la carpeta completa para evitar errores por nombres de archivo específicos.
     */
    public function descargarFormatos()
    {
        $zip = new ZipArchive;
        $fileName = 'Formatos_Registro_TuCombustible.zip';
        $pathPlanillas = storage_path('app/public/planillas');
        $zipPath = public_path($fileName);

        if (!File::isDirectory($pathPlanillas)) {
            Log::error("La carpeta de planillas no existe: " . $pathPlanillas);
            return back()->with('error', 'El directorio de formatos no está disponible.');
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            
            // Obtenemos todos los archivos dentro de la carpeta sin importar el nombre
            $files = File::files($pathPlanillas);

            if (empty($files)) {
                $zip->close();
                return back()->with('error', 'No hay archivos disponibles para descargar en este momento.');
            }

            foreach ($files as $file) {
                // Añadimos cada archivo usando su nombre real en el disco
                $zip->addFile($file->getRealPath(), $file->getFilename());
            }
            
            $zip->close();
        }

        if (!File::exists($zipPath)) {
            return back()->with('error', 'No se pudo generar el archivo de descarga.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function uploadDoc(Request $request)
    {
        $request->validate([
            'tipo_documento' => 'required|string',
            'archivo'        => 'required|file|mimes:pdf,doc,docx,odt|max:10240', 
        ]);

        try {
            $this->clienteService->subirDocumentoExpediente(
                Auth::user()->cliente_id, 
                $request->file('archivo'), 
                $request->tipo_documento
            );
            return back()->with('success', 'Documento cargado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function finalizarCargaDocs()
    {
        try {
            $this->clienteService->enviarExpedienteARevision(Auth::user()->cliente_id);
            return redirect()->route('portal.clientes.index')->with('success', '¡Perfecto! Tu expediente ha sido enviado a revisión exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function getCiudades($estado_id)
    {
        return Ciudad::where('estado_id', $estado_id)->orderBy('nombre', 'asc')->get();
    }

    public function perfil()
    {
        $cliente = $this->clienteService->obtenerExpediente(Auth::user()->cliente_id);
        return view('cliente.perfil', compact('cliente'));
    }
}