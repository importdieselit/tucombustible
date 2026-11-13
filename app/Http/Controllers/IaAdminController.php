<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IaSetting;
use App\Models\IaTool;

class IaAdminController extends Controller
{
    public function index()
    {
        // Traemos todas las configuraciones y herramientas
        $settings = IaSetting::all();
        $tools = IaTool::all();
        
        return view('admin.ia.index', compact('settings', 'tools'));
    }

    public function updateSettings(Request $request)
    {
        // Recibe un array asociativo [ 'system_prompt' => '...', 'temperature' => '0.2' ]
        foreach ($request->input('settings', []) as $key => $value) {
            IaSetting::where('key', $key)->update(['value' => $value]);
        }
        
        return redirect()->back()->with('success', 'Cerebro de la IA actualizado correctamente.');
    }

    public function toggleTool($id)
    {
        $tool = IaTool::findOrFail($id);
        $tool->is_active = !$tool->is_active;
        $tool->save();

        return redirect()->back()->with('success', "Herramienta {$tool->name} " . ($tool->is_active ? 'activada' : 'desactivada') . ".");
    }
    
    // (Opcional: Agrega aquí los métodos storeTool y updateTool para crear nuevas desde cero)
}