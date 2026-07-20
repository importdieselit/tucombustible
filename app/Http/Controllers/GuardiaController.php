<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Personal;    
use App\Models\Guardia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\WhatsappApiService;
use Illuminate\Support\Facades\Storage;

class GuardiaController extends Controller
{
    /**
     * Vista principal del Calendario Semanal (3 Semanas: Pasada, Actual, Próxima)
     */
    public function index(Request $request)
    {
        // 1. Calcular fecha base y la semana seleccionada (actual por defecto)
        $fechaBase = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::now();
        
        $inicioSemanaActual = $fechaBase->copy()->startOfWeek(Carbon::MONDAY);
        $finSemanaActual = $fechaBase->copy()->endOfWeek(Carbon::SUNDAY);

        // Calcular el inicio de la semana pasada y el fin de la semana próxima
        $inicioSemanaPasada = $inicioSemanaActual->copy()->subWeek();
        $finProximaSemana = $inicioSemanaActual->copy()->addWeeks(2)->subDay();

        // Generar array de los 21 días continuos para el calendario grid
        $diasCalendario = CarbonPeriod::create($inicioSemanaPasada, $finProximaSemana);

        // 2. Rol activo seleccionado en el filtro
        $rolSeleccionado = $request->get('rol', 'Chofer');

        // 3. Cargar Personal según el rol (con el JOIN optimizado para ordenar)
        $personal = Personal::where('estatus', 1) // Filtro base
            ->where(function($query) use ($rolSeleccionado) {
                // Opción 1: Que el cargo_id coincida mediante la relación
                $query->whereHas('cargo', function($subQuery) use ($rolSeleccionado) {
                    $subQuery->where('nombre', $rolSeleccionado);
                })
                // O Opción 2: Que el campo 'cargo' (string) en 'personal' coincida
                ->orWhere('personal.cargo', $rolSeleccionado);
            })
            ->join('personas', 'personal.id_persona', '=', 'personas.id')
            ->orderBy('personas.nombre', 'asc')
            ->select('personal.*')
            ->with('persona')
            ->get();

        // 4. Obtener las guardias programadas SOLO en los 21 días visibles
        $guardias = Guardia::with('personal.persona') // Importante cargar la relación de persona aquí
            ->whereBetween('fecha', [$inicioSemanaPasada->toDateString(), $finProximaSemana->toDateString()])
         //   ->where('rol_guardia', $rolSeleccionado) // Mostramos solo las del rol activo
            ->get()
            ->groupBy('fecha');

        // Mandamos las variables que requiere la nueva vista de Blade
        return view('planificacion.guardias', compact(
            'rolSeleccionado', 
            'personal', 
            'guardias', 
            'fechaBase',
            'inicioSemanaActual',
            'finSemanaActual',
            'diasCalendario'
        ));
    }

    /**
     * Guardar asignación asíncronamente (AJAX)
     */
    public function storeAjax(Request $request)
    {
        // Nota: Asegúrate de que tu fetch en JS envíe 'personal_id' en el body.
        // En el script que te di antes decía 'personnel_id', debes usar 'personal_id'.
        $request->validate([
            'personal_id' => 'required|exists:personal,id_personal',
            'fecha' => 'required|date',
            'rol_guardia' => 'required|in:Chofer,Ayudante de Chofer,Mecanico'
        ]);

        try {
            // Evitar duplicados modificando o creando la asignación
            $guardia = Guardia::updateOrCreate(
                [
                    'personal_id' => $request->personal_id,
                    'fecha' => $request->fecha,
                ],
                [
                    'rol_guardia' => $request->rol_guardia
                ]
            );

            // Retornamos el objeto con la relación anidada cargada para el DOM
            $guardia->load('personal.persona');

            return response()->json([
                'success' => true, 
                'guardia_id' => $guardia->id,
                // Corregido: la estructura correcta es a través de "persona"
                'nombre' => $guardia->personal->persona->nombre, 
                'rol' => $guardia->rol_guardia
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una asignación (AJAX)
     */
    public function destroyAjax(int $id)
    {
        try {
            $guardia = Guardia::findOrFail($id);
            $guardia->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generar reporte imprimible del cronograma de guardias
     */
    public function report(Request $request)
    {
        $startOfWeek = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date')) 
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
        
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        $semanaDias = [];
        for ($i = 0; $i < 7; $i++) {
            $semanaDias[] = $startOfWeek->copy()->addDays($i);
        }

        $guardias = Guardia::with('personal.persona')
            ->whereBetween('fecha', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get()
            ->groupBy('fecha');

        return view('planificacion.guardias-print', compact('semanaDias', 'guardias', 'startOfWeek', 'endOfWeek'));
    }

    public function sendWhatsapp(Request $request, WhatsappApiService $whatsappService)
    {
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('temp', 'public');
            $fullPath = storage_path('app/public/' . $path);
            $destiny=config('services.whatsapp.group_test');           
            $response = $whatsappService->enviarImagen($request->caption, $fullPath, $destiny);
            
            Storage::disk('public')->delete($path);

            if ($response && $response->successful()) {
                return response()->json(['success' => true]);
            }
        }
        
        return response()->json(['success' => false], 500);
    }
}