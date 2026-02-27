<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Repositories\ClienteRepository;
use App\Models\User;
use App\Mail\PlanillasRegistro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CaptacionController extends Controller
{
    protected $dashboardService;
    protected $clientRepo;

    public function __construct(DashboardService $dashboardService, ClienteRepository $clientRepo)
    {
        $this->dashboardService = $dashboardService;
        $this->clientRepo = $clientRepo;
    }

    /**
     * LISTADO ADMINISTRATIVO (Módulo de Clientes)
     * Adaptado para usar el Servicio y Repositorio.
     */
    public function index(Request $request)
    {
        // 1. Obtenemos las estadísticas de los 10 pasos para las cards superiores
        $stats = $this->dashboardService->getCaptacionStats();

        // 2. Obtenemos los prospectos (pasos 1 al 9) desde el Repositorio
        $prospectos = $this->clientRepo->getProspectos(20);

        // 3. Retornamos la vista con Tailwind que diseñamos antes
        return view('captacion.index', compact('prospectos', 'stats'));
    }

    /**
     * AVANZAR PASO (La magia del flujo)
     * Este método sustituye a múltiples funciones del controlador viejo.
     */
    public function updateStep(Request $request, $id)
    {
        $request->validate([
            'paso' => 'required|integer|between:1,10'
        ]);

        $exito = $this->dashboardService->avanzarPasoCliente($id, $request->paso);

        if ($exito) {
            return back()->with('success', 'El cliente ha avanzado al: ' . $this->dashboardService->getNombrePaso($request->paso));
        }

        return back()->with('error', 'No se pudo actualizar el progreso del cliente.');
    }

    /**
     * DETALLE DEL CLIENTE (Paso 3 y 4: Revisión de Documentos)
     */
    public function show($id)
    {
        $cliente = $this->clientRepo->findWithDetails($id);
        
        // Usamos la lógica de requisitos que ya tenías
        return view('captacion.show', compact('cliente'));
    }

    public function finalizarCarga()
    {
        $user = Auth::user();
        $cliente = $user->cliente;

        if ($cliente->registro_paso == 3) {
            // Usamos nuestro DashboardService para avanzar al paso 4
            $this->dashboardService->avanzarPasoCliente($cliente->id, 4);

            return redirect()->route('dashboard')->with('success', '¡Expediente enviado! Ahora estamos en el paso: Revisión de Documentos.');
        }

        return back()->with('error', 'Acción no permitida.');
    }

    public function validarDoc(Request $request, $id)
    {
        $status = $request->status;
        $observaciones = $request->observaciones;

        // Llamamos al service que ya tiene el método validarDocumento
        $this->dashboardService->validarDocumento($id, $status, $observaciones);

        $msg = ($status == 'validado') ? 'Documento aprobado con éxito' : 'Documento rechazado';
        return back()->with('success', $msg);
    }

    public function enviarPlanillas(User $user)
    {
        try {
            Mail::to($user->email)->send(new PlanillasRegistro($user));
        
            // Avanzamos al cliente al Paso 2 usando el Service
            $this->dashboardService->avanzarPasoCliente($user->cliente->id, 2);

            return back()->with('success', 'Planillas enviadas al correo del cliente.');
        } catch (\Exception $e) {
            Log::error("Error enviando planillas: " . $e->getMessage());
            return back()->with('error', 'No se pudo enviar el correo.');
        }
    }
}