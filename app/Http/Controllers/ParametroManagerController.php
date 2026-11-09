<?php

namespace App\Http\Controllers;

use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ParametroManagerController extends Controller
{
    // =====================================================================
    // 1. PANEL DE CONSUMO (El que usa el usuario normal)
    // Rutas: GET /admin/parametros y PUT /admin/parametros/update
    // =====================================================================

    public function panelIndex()
    {
        // Traemos solo los activos y los agrupamos por la columna 'grupo'
        $parametrosAgrupados = Parametro::where('activo', 1)
                                        ->orderBy('grupo')
                                        ->orderBy('clave')
                                        ->get()
                                        ->groupBy('grupo');

        if ($parametrosAgrupados->isEmpty()) {
            $parametrosAgrupados = collect([]);
        }

        // Esta va a la vista de consumo: index.blade.php
        return view('admin.parametros.index', compact('parametrosAgrupados'));
    }

    public function update(Request $request)    
    {
        $request->validate([
            'parametros' => 'required|array',
        ]);

        $data = $request->input('parametros');
        
        DB::beginTransaction();
        try {
            foreach ($data as $id => $nuevoContenido) {
                $parametro = Parametro::find($id);
                if ($parametro) {
                    $valorActual = $parametro->valor;
                    $valorActual['content'] = $nuevoContenido;
                    $parametro->valor = $valorActual;
                    $parametro->save();
                }
            }
            DB::commit();
            return redirect()->back()->with('success', 'Configuración actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // 2. BUILDER / MANAGER (El que usa el SuperAdmin para crear esquemas)
    // Rutas: GET /admin/parametros/manager, GET .../create, POST .../store, etc.
    // =====================================================================

    public function managerIndex()
    {
        // Aquí NO se agrupa. Se mandan todos planos para la tabla del Manager.
        $parametros = Parametro::orderBy('grupo')->orderBy('clave')->get();
        
        // Esta va a la vista del Manager: manager/index.blade.php
        return view('admin.parametros.manager.index', compact('parametros'));
    }

    public function create()
    {
        $tablas = $this->obtenerTablasDelSistema();
        return view('admin.parametros.manager.create', compact('tablas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grupo' => 'required|string',
            'clave' => 'required|string|unique:parametros,clave',
            'ui_type' => 'required|string',
            'label' => 'required|string'
        ]);

        $schema = [
            'label' => $request->label,
            'ui_type' => $request->ui_type,
            'help' => $request->help ?? null,
            'rules' => $request->rules ?? '',
        ];

        if (in_array($request->ui_type, ['db_select', 'db_matrix'])) {
            $schema['source'] = [
                'table' => $request->db_table,
                'value_field' => $request->db_value_field,
                'label_field' => $request->db_label_field,
            ];
        }

        Parametro::create([
            'grupo' => strtoupper($request->grupo),
            'clave' => strtoupper(str_replace(' ', '_', $request->clave)),
            'descripcion' => $request->descripcion,
            'valor' => [
                'schema' => $schema,
                'content' => null 
            ],
            'activo' => true
        ]);

        return redirect()->route('parametros.manager.index')->with('success', 'Parámetro creado.');
    }

    public function destroy(Parametro $parametro)
    {
        $parametro->delete();
        return redirect()->route('parametros.manager.index')->with('success', 'Parámetro eliminado.');
    }

    // --- MÉTODOS DE APOYO (API JS) ---

    public function obtenerColumnas($tabla)
    {
        if (!Schema::hasTable($tabla)) {
            return response()->json([]);
        }
        return response()->json(Schema::getColumnListing($tabla));
    }

    private function obtenerTablasDelSistema()
    {
        return collect(DB::select('SHOW TABLES'))->map(function ($tabla) {
            return array_values((array)$tabla)[0];
        })->filter(function($tabla) {
            return !in_array($tabla, ['migrations', 'failed_jobs', 'personal_access_tokens', 'password_resets', 'users']);
        });
    }
}