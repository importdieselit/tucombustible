<?php

namespace App\Http\Controllers;

use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametroController extends Controller
{
    /**
     * Muestra el panel de control tabulado por grupos.
     * Corresponde a tu vista admin/parametros/index.blade.php
     */
    public function index()
    {
        // Traemos solo los activos y los agrupamos por la columna 'grupo'
        $parametrosAgrupados = Parametro::where('activo', 1)
                                        ->orderBy('grupo')
                                        ->orderBy('clave')
                                        ->get()
                                        ->groupBy('grupo');

        // Si no hay parámetros, mandamos un array vacío para que el foreach del blade no explote
        if ($parametrosAgrupados->isEmpty()) {
            $parametrosAgrupados = collect([]);
        }

        return view('admin.parametros.index', compact('parametrosAgrupados'));
    }

    /**
     * Actualiza los valores de contenido de los parámetros editados desde la UI.
     */
    public function update(Request $request)
    {
        // Validamos que por lo menos venga el array de parámetros
        $request->validate([
            'parametros' => 'required|array',
        ]);

        $data = $request->input('parametros');

        // Usamos una transacción por si falla algo a la mitad, no dejar la BD inconsistente
        DB::beginTransaction();

        try {
            foreach ($data as $id => $nuevoContenido) {
                $parametro = Parametro::find($id);
                
                if ($parametro) {
                    // Recuperamos el array completo del JSON (schema + content)
                    $valorActual = $parametro->valor;

                    // Si es un 'number' o 'text' simple, el $nuevoContenido es un string/int directo.
                    // Si es un 'group', $nuevoContenido será un array asociativo (ej. ['peaje' => 7, 'desayuno' => 10])
                    
                    // Nos aseguramos de mantener el 'schema' intacto y solo reemplazar el 'content'
                    $valorActual['content'] = $nuevoContenido;

                    // Laravel (gracias al cast 'array' en tu modelo) se encarga de volverlo JSON al guardar
                    $parametro->valor = $valorActual;
                    $parametro->save();
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Configuración de parámetros actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Si estalla, mostramos el error para que puedas debuggear en lugar de quedarte mirando la pantalla
            return redirect()->back()->with('error', 'Error al guardar la configuración: ' . $e->getMessage());
        }
    }
}