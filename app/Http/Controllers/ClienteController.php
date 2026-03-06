<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Services\{ClienteService, DashboardService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    protected $clienteService;
    protected $dashboardService;

    public function __construct(ClienteService $clienteService, DashboardService $dashboardService)
    {
        $this->clienteService = $clienteService;
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $user = Auth::user();
        $cliente = $user->cliente; // Obtenemos la relación

        // CASO A: El cliente está en proceso de registro (Pasos 2 al 9)
        if ($cliente->registro_paso < 10) {
            $cliente->load('documentos'); // Cargamos los documentos que ya subió
            return view('cliente.dashboard_registro', compact('cliente'));
        }

        // CASO B: Cliente ya aprobado (Paso 10) - Lógica original
        $data = $this->dashboardService->getDashboardData($user);
        return view('cliente.index', $data);
    }

    /**
     * Acción para subir PDFs (Paso 2)
     */
    public function uploadDoc(Request $request)
    {
        $request->validate([
            'tipo_documento' => 'required|string',
            'archivo'        => 'required|mimes:pdf|max:5120', // PDF hasta 5MB
        ]);

        try {
            $this->clienteService->subirDocumentoExpediente(
                Auth::user()->cliente->id, 
                $request->file('archivo'), 
                $request->tipo_documento
            );

            return back()->with('success', 'Documento cargado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function perfil()
    {
        $cliente = $this->clienteService->obtenerExpediente(Auth::user()->cliente_id);
        return view('cliente.perfil', compact('cliente'));
    }
}