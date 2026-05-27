<?php

namespace App\Http\Controllers\RRHH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RrhhEvaluacionForm;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EvaluacionesController extends Controller
{
   public function create()
    {
        $usuario = User::find(auth()->user()->id);
        $personalData = $usuario->personalData();
        if (!$personalData) {
            return redirect()->back()->with('error', 'No se encontró información de personal para el usuario.');
        }

        $formulario = $personalData->getEvaluacionForm();
        if (!$formulario) {
            return redirect()->back()->with('error', 'No hay un formulario de evaluación activo para tu cargo.');
        }


        return view('rrhh.evaluaciones.index', compact('formulario'));
    }

    public function store()
    {
        $usuario = User::find(auth()->user()->id);
        $personalData = $usuario->personalData();

        if (!$personalData) {
            return redirect()->back()->with('error', 'No se encontró información de personal para el usuario.');
        }

        $formulario = $personalData->getEvaluacionForm();
        if (!$formulario) {
            return redirect()->back()->with('error', 'No hay un formulario de evaluación activo para tu cargo.');
        }
        return redirect()->back()->with('success', 'Formulario de evaluación enviado correctamente.');
    }

        public function edit($id)
        {
            $formulario = RrhhEvaluacionForm::findOrFail($id);
            return view('rrhh.evaluaciones.edit', compact('formulario'));
        }

        public function update()
        {
            $id = request()->input('id');
            $formulario = RrhhEvaluacionForm::findOrFail($id);

            $formulario->nombre = request()->input('nombre');
            $formulario->google_form_url = request()->input('google_form_url');
            $formulario->activo = request()->input('activo');

            $formulario->save();   
        }
}
