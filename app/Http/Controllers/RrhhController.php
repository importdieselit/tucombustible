<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Personal;
use App\Models\Cargo;
use App\Http\Requests\StoreEmpleadoRequest;
use Illuminate\Support\Facades\DB;

class RrhhController extends Controller
{
    public function store(StoreEmpleadoRequest $request)
    {
        // Iniciar transacción para garantizar que Persona y Personal se guarden juntos o ninguno
        DB::beginTransaction();

        try {
            // 1. Crear registro en tabla 'personas' (Datos Básicos)
            $persona = Persona::create([
                'nombre'        => $request->nombre . ' ' . $request->apellidos, // O separarlos si agregamos 'apellidos' a la BD
                'dni'           => $request->dni,
                'telefono'      => $request->telefono,
                'address'       => $request->direccion,
                'date_of_birth' => $request->fecha_nacimiento,
                'gender'        => $request->genero,
                'cargo_id'      => $request->cargo_id,
            ]);

            // 2. Crear registro en tabla 'personal' (Datos Contractuales)
            $personal = Personal::create([
                'id_persona'    => $persona->id,
                'cargo_id'      => $request->cargo_id,
                'dependencia'   => $request->dependencia, // ID del departamento
                'id_sede'       => $request->sede_id,
                'fecha_in'      => $request->fecha_in,
                'estatus'       => 1, // Ej: 1 = ACTIVO / PREINGRESO
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Empleado registrado correctamente.',
                'data'    => $personal->load('persona', 'cargo', 'sede') // Devolvemos la data relacionada para la vista
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al registrar empleado: ' . $e->getMessage()
            ], 500);
        }
    }
}