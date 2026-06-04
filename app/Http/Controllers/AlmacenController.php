<?php
namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\AlmacenEstructuraGrid;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlmacenController extends BaseController
{
    /**
     * Muestra una lista de todos los almacenes.
     * Retorna una vista de Blade.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $almacenes = Almacen::all();
        return view('almacen.index', compact('almacenes'));
    }


    /**
     * Almacena un nuevo almacén en la base de datos.
     * Redirige al usuario.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:almacenes',
            'direccion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('almacenes.create')
                ->withErrors($validator)
                ->withInput();
        }

        Almacen::create($request->all());

        return redirect()->route('almacenes.list')
            ->with('success', 'Almacén creado exitosamente.');
    }

    // /**
    //  * Muestra un almacén específico.
    //  * Retorna una vista de Blade.
    //  * @param int $id
    //  * @return \Illuminate\View\View
    //  */
    // public function show($id)
    // {
    //     $almacen = Almacen::find($id);
    //     if (!$almacen) {
    //         abort(404, 'Almacén no encontrado.');
    //     }
    //     return view('almacen.show', compact('almacen'));
    // }


    /**
     * Actualiza un almacén existente en la base de datos.
     * Redirige al usuario.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $almacen = Almacen::find($id);
        if (!$almacen) {
            abort(404, 'Almacén no encontrado.');
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255|unique:almacenes,nombre,' . $id,
            'direccion' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('almacenes.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $almacen->update($request->all());

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén actualizado exitosamente.');
    }


    public function disenar($id)
    {
        $almacen = Almacen::findOrFail($id);
        
        // Obtener las estructuras ya mapeadas organizadas por coordenadas
        $estructuras = AlmacenEstructuraGrid::where('almacen_id', $id)
            ->get()
            ->keyBy(function($item) {
                return $item->coord_y . '-' . $item->coord_x;
            });

        return view('logistica.almacen_croquis', compact('almacen', 'estructuras'));
    }

    // Guardar una celda del mapa vía AJAX y autogenerar sus ubicaciones
    public function guardarEstructura(Request $request)
    {
        $request->validate([
            'almacen_id'       => 'required|exists:almacenes,id',
            'coord_x'          => 'required|integer',
            'coord_y'          => 'required|integer',
            'tipo_estructura'  => 'required|in:ESTANTE,GRANEL_LUBRICANTE,PASILLO',
            'codigo_bloque'    => 'required|string|max:20',
            'cantidad_niveles' => 'required_if:tipo_estructura,ESTANTE|integer|min:1',
            'cantidad_secciones'=> 'required_if:tipo_estructura,ESTANTE|integer|min:1',
        ]);

        try {
            $resultado = DB::transaction(function () use ($request) {
                
                // 1. Guardar o actualizar la celda en la grilla
                $estructura = AlmacenEstructuraGrid::updateOrCreate(
                    [
                        'almacen_id' => $request->almacen_id,
                        'coord_x'    => $request->coord_x,
                        'coord_y'    => $request->coord_y
                    ],
                    [
                        'tipo_estructura'   => $request->tipo_estructura,
                        'codigo_bloque'     => strtoupper($request->codigo_bloque),
                        'cantidad_niveles'  => $request->tipo_estructura === 'ESTANTE' ? $request->cantidad_niveles : 1,
                        'cantidad_secciones'=> $request->tipo_estructura === 'ESTANTE' ? $request->cantidad_secciones : 1,
                    ]
                );

                // Limpiar ubicaciones físicas previas de esta celda si se está reconfigurando
                Ubicacion::where('estructura_grid_id', $estructura->id)->delete();

                // 2. Generación automática del desglose de ubicaciones
                if ($request->tipo_estructura === 'ESTANTE') {
                    // Iterar niveles (Hacia arriba) y secciones (Hacia los lados)
                    for ($n = 1; $n <= $request->cantidad_niveles; $n++) {
                        for ($s = 1; $s <= $request->cantidad_secciones; $s++) {
                            
                            $codigoUnico = sprintf("ALM%d-%s-N%d-S%d", 
                                $request->almacen_id, 
                                strtoupper($request->codigo_bloque), 
                                $n, 
                                $s
                            );

                            Ubicacion::create([
                                'almacen_id'         => $request->almacen_id,
                                'estructura_grid_id' => $estructura->id,
                                'codigo_unico'       => $codigoUnico,
                                'nivel_num'          => $n,
                                'seccion_num'        => $s,
                                'tipo_almacenamiento'=> 'RACK'
                            ]);
                        }
                    }
                } else if ($request->tipo_estructura === 'GRANEL_LUBRICANTE') {
                    // Para lubricantes a granel es una relación 1 a 1 de espacio físico directo en el suelo
                    Ubicacion::create([
                        'almacen_id'         => $request->almacen_id,
                        'estructura_grid_id' => $estructura->id,
                        'codigo_unico'       => "ALM" . $request->almacen_id . "-" . strtoupper($request->codigo_bloque) . "-GRANEL",
                        'nivel_num'          => 1,
                        'seccion_num'        => 1,
                        'tipo_almacenamiento'=> 'PISO_GRANEL'
                    ]);
                }

                return $estructura;
            });

            return response()->json([
                'success' => true, 
                'message' => 'Celda configurada y ubicaciones generadas con éxito.',
                'html_clase' => $this->getClaseColor($resultado->tipo_estructura),
                'texto' => $resultado->codigo_bloque
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getClaseColor($tipo) {
        return match($tipo) {
            'ESTANTE' => 'bg-primary text-white',
            'GRANEL_LUBRICANTE' => 'bg-warning text-dark',
            default => 'bg-light text-muted'
        };
    }

}