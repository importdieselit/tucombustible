<?php

namespace App\Http\Controllers;

use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ParametroManagerController extends Controller
{
    public function index()
    {
        $parametros = Parametro::orderBy('grupo')->orderBy('clave')->get();
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
                'content' => null // Listo para ser llenado por el usuario
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
            // Ignorar tablas base de Laravel
            return !in_array($tabla, ['migrations', 'failed_jobs', 'personal_access_tokens', 'password_resets', 'users']);
        });
    }
}