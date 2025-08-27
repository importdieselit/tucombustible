<?php

namespace App\Http\Controllers;

use App\Models\Perfil; // CAMBIADO: Usar tu modelo Perfil
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // Añadir si no está

class PerfilController extends Controller // CAMBIADO: Nombre de la clase
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perfiles = Perfil::paginate(10); // CAMBIADO: a $perfiles
        return view('perfiles.index', compact('perfiles')); // CAMBIADO: a perfiles.index
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('perfiles.create'); // CAMBIADO: a perfiles.create
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:perfiles,name'], // CAMBIADO: a perfiles
        ]);

        Perfil::create([ // CAMBIADO: a Perfil
            'name' => $request->name,
            // Si tu tabla 'perfiles' tiene otras columnas como 'description', 'activo', etc., añádelas aquí.
            'description' => $request->description ?? null, // Asumiendo que puedes tener una descripción
            'activo' => $request->activo ?? true, // Asumiendo que puedes tener un campo activo
        ]);

        return redirect()->route('perfiles.index')->with('success', 'Perfil creado exitosamente.'); // CAMBIADO: a perfiles.index
    }

    /**
     * Display the specified resource.
     */
    public function show(Perfil $perfil) // CAMBIADO: a Perfil $perfil
    {
        return redirect()->route('perfiles.edit', $perfil); // CAMBIADO: a perfiles.edit
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Perfil $perfil) // CAMBIADO: a Perfil $perfil
    {
        return view('perfiles.edit', compact('perfil')); // CAMBIADO: a perfiles.edit
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Perfil $perfil) // CAMBIADO: a Perfil $perfil
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('perfiles', 'name')->ignore($perfil->id)], // CAMBIADO: a perfiles
        ]);

        $perfil->name = $request->name;
        $perfil->description = $request->description ?? $perfil->description; // Actualizar descripción si se envía
        $perfil->activo = $request->activo ?? $perfil->activo; // Actualizar activo si se envía
        $perfil->save();

        return redirect()->route('perfiles.index')->with('success', 'Perfil actualizado exitosamente.'); // CAMBIADO: a perfiles.index
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Perfil $perfil) // CAMBIADO: a Perfil $perfil
    {
        $perfil->delete();
        return redirect()->route('perfiles.index')->with('success', 'Perfil eliminado exitosamente.'); // CAMBIADO: a perfiles.index
    }
}
