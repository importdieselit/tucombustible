<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo; // Asegúrate de usar el namespace correcto de tu modelo
use App\Models\User; // Asegúrate de que el modelo User esté correctamente importado
use App\Models\Marca; // Asegúrate de que el modelo Marca esté correctamente importado
use App\Models\Modelo; // Asegúrate de que el modelo Modelo esté correctamente imported
use App\Models\TipoVehiculo; // Asegúrate de que el modelo Tipo
// use App\Vehiculo; // Si tus modelos están directamente en 'App\'

class VehiculoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Obtener todos los vehículos de la base de datos
        $vehiculos = Vehiculo::all();

        // Pasar los vehículos a la vista
        return view('vehiculos.index', compact('vehiculos'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Buscar un vehículo por su ID
        $vehiculo = Vehiculo::findOrFail($id);

        // Pasar el vehículo a la vista
        return view('vehiculos.show', compact('vehiculo'));
    }
     public function create()
    {
        $marcas = Marca::pluck('marca', 'id'); // Para el select de marcas
        $modelos = Modelo::pluck('modelo', 'id'); // Para el select de modelos
        $usuarios = User::pluck('name', 'id'); // Para el select de usuarios
        $tiposVehiculo = TipoVehiculo::pluck('tipo', 'id'); // Para el select de tipos de vehículo

        return view('vehiculos.create', compact('marcas', 'modelos', 'usuarios', 'tiposVehiculo'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'placa' => 'required|string|max:20|unique:vehiculos,placa',
            'id_usuario' => 'required|exists:users,id',
            'marca' => 'required|exists:marcas,id',
            'modelo' => 'required|exists:modelos,id',
            'anno' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'kilometraje' => 'nullable|integer|min:0',
            'estatus' => 'required|integer', // Ajusta según tus valores de estatus (ej. 0 para inactivo, 1 para activo)
            // Agrega validaciones para todos los campos fillable
        ]);

        $vehiculo = Vehiculo::create($request->all());

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo creado exitosamente.');
    }

    // Puedes añadir métodos para create, store, edit, update, destroy si los necesitas

 public function edit($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $marcas = Marca::pluck('nombre', 'id');
        $modelos = Modelo::pluck('nombre', 'id');
        $usuarios = User::pluck('name', 'id');
        $tiposVehiculo = TipoVehiculo::pluck('nombre', 'id');

        return view('vehiculos.edit', compact('vehiculo', 'marcas', 'modelos', 'usuarios', 'tiposVehiculo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'placa' => 'required|string|max:20|unique:vehiculos,placa,' . $id, // Ignora la placa actual al actualizar
            'id_usuario' => 'required|exists:users,id',
            'marca' => 'required|exists:marcas,id',
            'modelo' => 'required|exists:modelos,id',
            'anno' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'kilometraje' => 'nullable|integer|min:0',
            'estatus' => 'required|integer',
            // Agrega validaciones para todos los campos fillable
        ]);

        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->update($request->all());

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->delete();

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo eliminado exitosamente.');
    }

}