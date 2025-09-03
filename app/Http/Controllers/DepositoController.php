<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deposito;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Log;



class DepositoController extends BaseController
{
     public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'capacidad_litros' => 'required|numeric|min:0',
            'nivel_actual_litros' => 'required|numeric|min:0|lte:capacidad_litros',
            'ubicacion' => 'nullable|string|max:255',
            'serial' => 'required|string|max:255|unique:depositos,serial',
            'producto' => 'required|string|max:255'

        ]);

        if ($validator->fails()) {
            return redirect()->route('depositos.index')
                             ->withErrors($validator)
                             ->withInput()
                             ->with('error', 'Error al crear el depósito. Revisa los datos ingresados.');
        }

        Deposito::create($validator->validated());

        Session::flash('success', 'Depósito creado exitosamente.');
        return redirect()->route('depositos.list');
    }


     public function actualizar(Request $request, Deposito $deposito)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'capacidad_litros' => 'required|numeric|min:0',
            'nivel_actual_litros' => 'required|numeric|min:0|lte:capacidad_litros',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('depositos.index')
                             ->withErrors($validator)
                             ->withInput()
                             ->with('error', 'Error al actualizar el depósito. Revisa los datos ingresados.');
        }

        $deposito->update($validator->validated());

        Session::flash('success', 'Depósito actualizado exitosamente.');
        return redirect()->route('depositos.index');
    }

}
