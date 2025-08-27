<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\InspectionType;
use App\Models\MasterInspectionItem;
use App\Models\Vehicle;
use App\Models\User; // Para conductores e inspectores
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Por ahora, solo redirigimos o mostramos un mensaje simple.
        // La vista de listado de inspecciones se construirá más adelante.
        $inspections = Inspection::with(['vehicle', 'inspectionType', 'inspectedBy'])->paginate(10);
        return view('inspections.index', compact('inspections'));
    }

    /**
     * Show the form for creating a new resource (the checklist form).
     */
    public function create()
    {
        $vehicles = Vehicle::all(); // Obtener todos los vehículos
        $users = User::all(); // Obtener todos los usuarios (para inspector/conductor)
        $inspectionTypes = InspectionType::all(); // Obtener todos los tipos de inspección

        // Por defecto, cargamos los ítems para la "Inspección General Camión Cisterna (Formato Documento)"
        $selectedInspectionType = InspectionType::where('name', 'Inspección General Camión Cisterna (Formato Documento)')->first();

        $checklistItems = collect();
        $tabCategories = []; // Inicializar array para las categorías de las pestañas

        if ($selectedInspectionType) {
            // Obtener ítems de la checklist con su categoría y orden
            $checklistItems = $selectedInspectionType->masterInspectionItems()
                                ->orderBy('category') // Ordenar por categoría para la vista
                                ->orderBy('order')
                                ->get()
                                ->groupBy('category'); // Agrupar por la categoría del ítem maestro

            // Extraer solo los nombres de las categorías para las pestañas
            $tabCategories = $checklistItems->keys()->toArray();
        }

        // Combinar las pestañas fijas con las categorías dinámicas
        $allTabs = array_merge(['Datos Generales', 'Datos del Vehículo'], $tabCategories, ['Estado General']);

        return view('inspections.create', compact('vehicles', 'users', 'inspectionTypes', 'selectedInspectionType', 'checklistItems', 'allTabs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'inspection_type_id' => ['required', 'exists:inspection_types,id'],
            'inspected_by_user_id' => ['required', 'exists:users,id'],
            'inspection_date' => ['required', 'date'],
            'odometer_reading_km' => ['nullable', 'integer', 'min:0'],
            'overall_status' => ['required', 'string', Rule::in(['OK', 'Con Novedades', 'Rechazado'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'checklist_items' => ['required', 'array'], // Array de ítems de la checklist
            'checklist_items.*.item_id' => ['required', 'exists:master_inspection_items,id'],
            'checklist_items.*.status' => ['required', 'string', Rule::in(['Sí', 'No', 'OK', 'Defecto Leve', 'Defecto Mayor', 'No Aplica'])], // Actualizado para incluir 'Sí'/'No'
            'checklist_items.*.comments' => ['nullable', 'string', 'max:500'],
        ]);

        // 1. Crear el registro de la inspección principal
        $inspection = Inspection::create([
            'vehicle_id' => $request->vehicle_id,
            'inspection_type_id' => $request->inspection_type_id,
            'inspected_by_user_id' => $request->inspected_by_user_id,
            'inspection_date' => $request->inspection_date,
            'odometer_reading_km' => $request->odometer_reading_km,
            'overall_status' => $request->overall_status,
            'notes' => $request->notes,
        ]);

        // 2. Guardar los detalles de la checklist
        foreach ($request->checklist_items as $itemData) {
            $inspection->details()->create([
                'master_inspection_item_id' => $itemData['item_id'],
                'status' => $itemData['status'],
                'comments' => $itemData['comments'],
            ]);
        }

        return redirect()->route('inspections.index')->with('success', 'Inspección registrada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Inspection $inspection)
    {
        // Puedes implementar una vista para mostrar los detalles de una inspección
        return view('inspections.show', compact('inspection'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inspection $inspection)
    {
        // Implementar edición si es necesario
        $vehicles = Vehicle::all();
        $users = User::all();
        $inspectionTypes = InspectionType::all();

        // Obtener el tipo de inspección actual de la inspección
        $selectedInspectionType = $inspection->inspectionType;

        $checklistItems = collect();
        $tabCategories = [];

        if ($selectedInspectionType) {
            $checklistItems = $selectedInspectionType->masterInspectionItems()
                                ->orderBy('category')
                                ->orderBy('order')
                                ->get()
                                ->groupBy('category');

            $tabCategories = $checklistItems->keys()->toArray();
        }

        $allTabs = array_merge(['Datos Generales', 'Datos del Vehículo'], $tabCategories, ['Estado General']);

        // Para precargar los resultados existentes
        $existingDetails = $inspection->details->keyBy('master_inspection_item_id');

        return view('inspections.edit', compact('inspection', 'vehicles', 'users', 'inspectionTypes', 'checklistItems', 'existingDetails', 'selectedInspectionType', 'allTabs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inspection $inspection)
    {
        $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'inspection_type_id' => ['required', 'exists:inspection_types,id'],
            'inspected_by_user_id' => ['required', 'exists:users,id'],
            'inspection_date' => ['required', 'date'],
            'odometer_reading_km' => ['nullable', 'integer', 'min:0'],
            'overall_status' => ['required', 'string', Rule::in(['OK', 'Con Novedades', 'Rechazado'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'checklist_items' => ['required', 'array'],
            'checklist_items.*.item_id' => ['required', 'exists:master_inspection_items,id'],
            'checklist_items.*.status' => ['required', 'string', Rule::in(['Sí', 'No', 'OK', 'Defecto Leve', 'Defecto Mayor', 'No Aplica'])], // Actualizado para incluir 'Sí'/'No'
            'checklist_items.*.comments' => ['nullable', 'string', 'max:500'],
        ]);

        $inspection->update([
            'vehicle_id' => $request->vehicle_id,
            'inspection_type_id' => $request->inspection_type_id,
            'inspected_by_user_id' => $request->inspected_by_user_id,
            'inspection_date' => $request->inspection_date,
            'odometer_reading_km' => $request->odometer_reading_km,
            'overall_status' => $request->overall_status,
            'notes' => $request->notes,
        ]);

        // Eliminar detalles existentes y recrearlos o actualizarlos
        $inspection->details()->delete(); // Opcional: para simplificar la lógica de actualización
        foreach ($request->checklist_items as $itemData) {
            $inspection->details()->create([
                'master_inspection_item_id' => $itemData['item_id'],
                'status' => $itemData['status'],
                'comments' => $itemData['comments'],
            ]);
        }

        return redirect()->route('inspections.index')->with('success', 'Inspección actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inspection $inspection)
    {
        $inspection->delete();
        return redirect()->route('inspections.index')->with('success', 'Inspección eliminada exitosamente.');
    }
}
