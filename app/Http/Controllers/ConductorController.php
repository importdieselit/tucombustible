<?php

namespace App\Http\Controllers;

use App\Models\Conductor;
use App\Models\Persona; // Necesitamos el modelo Persona para crear y editar conductores
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConductorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $conductores = Conductor::with('persona')->paginate(10);
        return view('conductores.index', compact('conductores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener personas que aún no son conductores
        $personas = Persona::doesntHave('conductor')->get();
        return view('conductores.create', compact('personas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'persona_id' => ['required', 'exists:personas,id', Rule::unique('conductores', 'persona_id')],
            'license_number' => ['nullable', 'string', 'max:50', 'unique:conductores,license_number'],
            'license_exp_at' => ['nullable', 'date'],
            'medical_cert_exp_at' => ['nullable', 'date'],
            'hazardous_materials_cert_exp_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Conductor::create($request->all());

        return redirect()->route('conductores.index')->with('success', 'Conductor registrado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Conductor $conductor)
    {
        $conductor->load('persona', 'vehicles'); // Cargar la persona y los vehículos asignados
        return view('conductores.show', compact('conductor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Conductor $conductor)
    {
        $persona = $conductor->persona; // La persona ya asociada al conductor
        return view('conductores.edit', compact('conductor', 'persona'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Conductor $conductor)
    {
        $request->validate([
            'license_number' => ['nullable', 'string', 'max:50', Rule::unique('conductores', 'license_number')->ignore($conductor->id)],
            'license_exp_at' => ['nullable', 'date'],
            'medical_cert_exp_at' => ['nullable', 'date'],
            'hazardous_materials_cert_exp_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $conductor->update($request->all());

        return redirect()->route('conductores.index')->with('success', 'Conductor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conductor $conductor)
    {
        $conductor->delete();
        return redirect()->route('conductores.index')->with('success', 'Conductor eliminado exitosamente.');
    }
}
