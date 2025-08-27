<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Perfil; // CAMBIADO: Usar tu modelo Perfil
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('perfil')->paginate(10); // CAMBIADO: Cargar la relación 'perfil'
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $perfiles = Perfil::all(); // CAMBIADO: Obtener perfiles
        return view('users.create', compact('perfiles')); // CAMBIADO: Pasar 'perfiles'
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'perfil_id' => ['required', 'exists:perfiles,id'], // CAMBIADO: a perfil_id y perfiles
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'perfil_id' => $request->perfil_id, // CAMBIADO: a perfil_id
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $perfiles = Perfil::all(); // CAMBIADO: Obtener perfiles
        return view('users.edit', compact('user', 'perfiles')); // CAMBIADO: Pasar 'perfiles'
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'perfil_id' => ['required', 'exists:perfiles,id'], // CAMBIADO: a perfil_id y perfiles
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->perfil_id = $request->perfil_id; // CAMBIADO: a perfil_id
        $user->save();

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente.');
    }
}
